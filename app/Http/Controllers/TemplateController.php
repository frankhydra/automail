<?php

namespace App\Http\Controllers;

use App\Contracts\EmailProviderInterface;
use App\Models\User;
use App\Services\TemplateBlockRenderer;
use App\Services\TemplateRendererService;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TemplateController extends Controller
{
    use EnsuresTeamPermission;

    protected TemplateRendererService $rendererService;

    public function __construct(TemplateRendererService $rendererService)
    {
        $this->rendererService = $rendererService;
    }

    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $templates = $organization ? $organization->templates()->latest()->get() : collect();

        return view('templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('templates.create', [
            'blockTypes' => TemplateBlockRenderer::types(),
            'senderLabel' => $this->senderLabel(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            // Exactly one of these arrives, depending on which editor mode was active.
            'blocks_json' => 'nullable|json',
            'body' => 'required_without:blocks_json|nullable|string',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $user->currentOrganization();

        if (!$organization) {
            return redirect()->back()->withErrors(['error' => 'No active organization found.']);
        }

        $content = $this->resolveContent($request);

        $organization->templates()->create([
            'name' => trim($request->input('name')),
            'subject' => $request->filled('subject') ? trim($request->input('subject')) : null,
            'preview_text' => $request->filled('preview_text') ? trim($request->input('preview_text')) : null,
            'body' => $content['body'],
            'blocks' => $content['blocks'],
        ]);

        return redirect()->route('templates.index')->with('status', 'Template created successfully!');
    }

    public function edit(int $id): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $template = $organization ? $organization->templates()->find($id) : null;

        if (!$template) {
            return redirect()->route('templates.index')->withErrors(['error' => 'Template not found.']);
        }

        return view('templates.edit', [
            'template' => $template,
            'blockTypes' => TemplateBlockRenderer::types(),
            'senderLabel' => $this->senderLabel(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'blocks_json' => 'nullable|json',
            'body' => 'required_without:blocks_json|nullable|string',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $user->currentOrganization();

        $template = $organization ? $organization->templates()->find($id) : null;

        if (!$template) {
            return redirect()->route('templates.index')->withErrors(['error' => 'Template not found.']);
        }

        $content = $this->resolveContent($request);

        $template->update([
            'name' => trim($request->input('name')),
            'subject' => $request->filled('subject') ? trim($request->input('subject')) : null,
            'preview_text' => $request->filled('preview_text') ? trim($request->input('preview_text')) : null,
            'body' => $content['body'],
            'blocks' => $content['blocks'],
        ]);

        return redirect()->route('templates.index')->with('status', 'Template updated successfully!');
    }

    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        if ($organization) {
            $organization->templates()->where('id', $id)->delete();
        }

        return redirect()->route('templates.index')->with('status', 'Template deleted successfully.');
    }

    /**
     * Live preview for the Email Builder. Takes the editor's CURRENT (unsaved)
     * content, compiles it with the same renderer used on save, fills the merge
     * tags with sample data, and returns the HTML for a sandboxed iframe.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'blocks_json' => 'nullable|json',
            'body' => 'nullable|string',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);

        $content = $this->resolveContent($request);

        $sample = ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@example.com'];

        return response()->json([
            'subject' => $this->rendererService->render((string) $request->input('subject', ''), $sample, false),
            'html' => $this->rendererService->render($content['body'], $sample),
        ]);
    }

    /**
     * Send the editor's current content as a test email.
     *
     * Safeguards: only team members of the current organization can receive a
     * test (so this can't be used to send arbitrary mail from a verified domain),
     * a verified sending identity is required, and the route is rate-limited.
     * Test emails carry no tracking and no unsubscribe link.
     */
    public function sendTest(Request $request, EmailProviderInterface $provider): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'blocks_json' => 'nullable|json',
            'body' => 'nullable|string',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json(['error' => 'No active organization found.'], 422);
        }

        $to = strtolower(trim($request->input('email')));
        $teamEmails = $organization->users()->pluck('users.email')->map(fn ($e) => strtolower($e))->all();

        if (!in_array($to, $teamEmails, true)) {
            return response()->json(['error' => 'Test emails can only be sent to members of your team.'], 422);
        }

        $identity = $organization->sendingIdentities()->where('verification_status', 'verified')->first();

        if (!$identity) {
            return response()->json(['error' => 'Add and verify a Sending Identity first - a test email needs a verified sender.'], 422);
        }

        $content = $this->resolveContent($request);

        $nameParts = preg_split('/\s+/', trim((string) $user->name), 2);
        $sample = [
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? '',
            'email' => $to,
        ];

        // Subject is a plain-text header: no HTML escaping, but no line breaks either.
        $subject = preg_replace('/[\r\n]+/', ' ', $this->rendererService->render((string) $request->input('subject', ''), $sample, false));
        $subject = '[Test] '.(trim($subject) !== '' ? $subject : '(no subject)');

        $html = $this->rendererService->render($content['body'], $sample);

        $result = $provider->send(
            $identity->from_email,
            $identity->from_name,
            $to,
            $subject,
            $html,
            $identity->reply_to
        );

        if (empty($result['success'])) {
            return response()->json(['error' => $result['error'] ?? 'The email provider rejected the test email.'], 422);
        }

        $loggedOnly = strtolower((string) env('EMAIL_PROVIDER', 'log')) === 'log';

        return response()->json([
            'message' => $loggedOnly
                ? "Test \"sent\" to {$to}, but EMAIL_PROVIDER is set to log, so it was only written to the log. Set EMAIL_PROVIDER to brevo, resend or smtp to deliver real mail."
                : "Test email sent to {$to}.",
        ]);
    }

    /**
     * "From" shown at the top of the builder's canvas. Real senders are chosen
     * per campaign via its Sending Identity; this just shows the first verified one.
     */
    protected function senderLabel(): string
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;
        $identity = $organization
            ? $organization->sendingIdentities()->where('verification_status', 'verified')->first()
            : null;

        return $identity ? "{$identity->from_name} <{$identity->from_email}>" : 'Your verified sending identity';
    }

    /**
     * If the visual builder was used (blocks_json present and valid), compile
     * it into HTML with TemplateBlockRenderer - this is the single place
     * blocks become the sendable "body". Otherwise falls back to the raw HTML
     * editor's "body" field and blocks stays null. Preview text is only
     * compiled in builder mode.
     *
     * @return array{body: string, blocks: ?array}
     */
    protected function resolveContent(Request $request): array
    {
        $rawBlocks = $request->input('blocks_json');

        if ($rawBlocks) {
            $decoded = json_decode($rawBlocks, true);
            $blocks = $this->sanitizeBlocks(is_array($decoded) ? $decoded : []);

            return [
                'body' => TemplateBlockRenderer::render($blocks, $request->input('preview_text')),
                'blocks' => $blocks,
            ];
        }

        return ['body' => (string) $request->input('body', ''), 'blocks' => null];
    }

    /**
     * Keep only blocks with a type TemplateBlockRenderer understands, and cast
     * every field to a length-capped string - the renderer itself escapes all
     * output, so this is a sanity check, not the security boundary.
     *
     * @return list<array<string, mixed>>
     */
    protected function sanitizeBlocks(array $blocks): array
    {
        $allowedTypes = TemplateBlockRenderer::types();
        $clean = [];

        foreach ($blocks as $block) {
            if (!is_array($block) || !in_array($block['type'] ?? null, $allowedTypes, true)) {
                continue;
            }

            $fields = ['type' => $block['type']];
            foreach ($block as $key => $value) {
                if ($key === 'type') {
                    continue;
                }
                $fields[$key] = mb_substr((string) $value, 0, 2000);
            }

            $clean[] = $fields;
        }

        return $clean;
    }
}
