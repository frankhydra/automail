<?php

namespace App\Http\Controllers;

use App\Exceptions\AiException;
use App\Models\AiGeneration;
use App\Models\Organization;
use App\Models\User;
use App\Services\AiAssistantService;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * JSON endpoints behind the Email Builder's AI features. Anyone who can edit
 * campaigns may use them, within the organization's monthly allowance.
 */
class AiController extends Controller
{
    use EnsuresTeamPermission;

    public function __construct(protected AiAssistantService $ai)
    {
    }

    protected function organization(): Organization
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        abort_if($organization === null, 403, 'No active organization found for your account.');

        return $organization;
    }

    protected function authorizeUse(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
    }

    public function status(): JsonResponse
    {
        $this->authorizeUse();
        $organization = $this->organization();

        return response()->json([
            'available' => $this->ai->available(),
            'remaining' => $this->ai->remaining($organization),
            'limit' => $this->ai->monthlyLimit($organization),
        ]);
    }

    public function draft(Request $request): JsonResponse
    {
        $this->authorizeUse();

        $data = $request->validate([
            'brief' => 'required|string|min:5|max:1000',
            'tone' => ['nullable', Rule::in(AiAssistantService::TONES)],
            'language' => ['nullable', 'string', 'max:40', 'regex:/^[\pL\s\-]+$/u'],
        ]);

        return $this->run('draft', fn () => $this->ai->draft(
            trim($data['brief']),
            $data['tone'] ?? 'friendly',
            isset($data['language']) ? trim($data['language']) : null
        ));
    }

    public function rewrite(Request $request): JsonResponse
    {
        $this->authorizeUse();

        $data = $request->validate([
            'text' => 'required|string|max:3000',
            'action' => ['required', Rule::in(array_keys(AiAssistantService::REWRITE_ACTIONS))],
            'language' => ['nullable', 'required_if:action,translate', 'string', 'max:40', 'regex:/^[\pL\s\-]+$/u'],
        ], [
            'language.required_if' => 'Say which language to translate into.',
        ]);

        return $this->run('rewrite', fn () => $this->ai->rewrite(
            $data['text'],
            $data['action'],
            isset($data['language']) ? trim($data['language']) : null
        ));
    }

    public function subjects(Request $request): JsonResponse
    {
        $this->authorizeUse();

        $data = $request->validate([
            'context' => 'required|string|min:5|max:3000',
            'current' => 'nullable|string|max:255',
        ]);

        return $this->run('subjects', fn () => $this->ai->subjectLines($data['context'], $data['current'] ?? null));
    }

    /**
     * The shared flow: permission, "is it set up", monthly allowance, call, log, answer.
     * Only successful calls use up the allowance.
     *
     * @param callable(): array{data: array<string, mixed>, usage: array{0: int, 1: int}} $call
     */
    protected function run(string $kind, callable $call): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $this->organization();

        if (!$this->ai->available()) {
            return response()->json(['error' => 'The AI assistant is not set up yet. An admin needs to add an AI API key to the server settings.'], 503);
        }

        if ($this->ai->remaining($organization) <= 0) {
            return response()->json(['error' => 'Your organization has used its AI allowance for this month. It resets on the 1st, or you can upgrade your plan.'], 429);
        }

        try {
            $result = $call();
        } catch (AiException $e) {
            $this->record($organization, $user, $kind, 'error', [0, 0]);

            return response()->json(['error' => $e->getMessage()], $e->status);
        }

        $this->record($organization, $user, $kind, 'ok', $result['usage']);

        return response()->json($result['data'] + ['remaining' => $this->ai->remaining($organization)]);
    }

    /**
     * @param array{0: int, 1: int} $usage
     */
    protected function record(Organization $organization, ?User $user, string $kind, string $status, array $usage): void
    {
        AiGeneration::create([
            'organization_id' => $organization->id,
            'user_id' => $user?->id,
            'kind' => $kind,
            'status' => $status,
            'input_tokens' => $usage[0],
            'output_tokens' => $usage[1],
            'created_at' => now(),
        ]);
    }
}
