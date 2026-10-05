<?php

namespace App\Http\Controllers;

use App\Models\Automation;
use App\Models\Organization;
use App\Models\Template;
use App\Models\User;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Create and manage automations (journeys).
 *
 * Permissions: anyone in the organization can look; editors and above can edit the
 * steps; only managers and above can switch one on (it sends email); only owners
 * and admins can delete.
 */
class AutomationController extends Controller
{
    use EnsuresTeamPermission;

    protected const MAX_STEPS = 30;

    protected function organization(): Organization
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        abort_if($organization === null, 403, 'No active organization found for your account.');

        return $organization;
    }

    protected function find(Organization $organization, int $id): Automation
    {
        return $organization->automations()->findOrFail($id);
    }

    public function index(): View
    {
        $automations = $this->organization()->automations()
            ->withCount([
                'runs as total_runs',
                'runs as active_runs' => fn ($q) => $q->where('status', 'active'),
                'runs as completed_runs' => fn ($q) => $q->where('status', 'completed'),
                'nodes as email_steps' => fn ($q) => $q->where('type', 'email'),
            ])
            ->latest()
            ->get();

        /** @var User $user */
        $user = Auth::user();

        return view('automations.index', [
            'automations' => $automations,
            'canEdit' => in_array($user->currentRole(), ['owner', 'admin', 'manager', 'editor'], true),
            'canActivate' => in_array($user->currentRole(), ['owner', 'admin', 'manager'], true),
            'canDelete' => in_array($user->currentRole(), ['owner', 'admin'], true),
        ]);
    }

    public function create(): View
    {
        return view('automations.create', $this->editorData($this->organization(), null));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $this->organization();

        $data = $this->validateSettings($request, $organization);
        $steps = $this->cleanSteps($request->input('steps_json', '[]'), $organization);

        $automation = DB::transaction(function () use ($organization, $data, $steps) {
            $automation = $organization->automations()->create($data + ['status' => 'draft']);
            $this->createChain($automation, $steps, null, null);

            return $automation;
        });

        return redirect()->route('automations.edit', $automation->id)
            ->with('status', 'Automation saved as a draft. Switch it on when you are ready.');
    }

    public function edit(int $id): View
    {
        $organization = $this->organization();

        return view('automations.edit', $this->editorData($organization, $this->find($organization, $id)));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $this->organization();
        $automation = $this->find($organization, $id);

        $data = $this->validateSettings($request, $organization);

        // Once contacts are travelling through a journey its steps are locked: editing
        // the tree under them could strand people mid-journey. (The emails themselves
        // still update, because they come from the templates.)
        $locked = $automation->runs()->exists();
        $steps = $locked ? null : $this->cleanSteps($request->input('steps_json', '[]'), $organization);

        DB::transaction(function () use ($automation, $data, $steps) {
            $automation->update($data);

            if ($steps !== null) {
                $automation->nodes()->delete();
                $this->createChain($automation, $steps, null, null);
            }
        });

        return redirect()->route('automations.edit', $automation->id)
            ->with('status', $locked ? 'Settings saved. Steps are locked because contacts are already in this journey.' : 'Automation saved.');
    }

    public function activate(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManageContacts($user);
        $organization = $this->organization();
        $automation = $this->find($organization, $id);

        $problem = $this->readinessProblem($automation, $organization);

        if ($problem) {
            return back()->withErrors(['activate' => $problem]);
        }

        $automation->update(['status' => 'active']);

        return redirect()->route('automations.index')
            ->with('status', "\"{$automation->name}\" is now live. Contacts who match the trigger from now on will enter it.");
    }

    public function pause(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManageContacts($user);
        $automation = $this->find($this->organization(), $id);

        $automation->update(['status' => 'paused']);

        return redirect()->route('automations.index')
            ->with('status', "\"{$automation->name}\" is paused. Contacts already inside stay where they are until you resume it.");
    }

    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $automation = $this->find($this->organization(), $id);

        $automation->delete();

        return redirect()->route('automations.index')->with('status', 'Automation deleted.');
    }

    // ------------------------------------------------------------------ helpers

    protected function editorData(Organization $organization, ?Automation $automation): array
    {
        return [
            'automation' => $automation,
            'templates' => $organization->templates()->orderBy('name')->get(['id', 'name']),
            'identities' => $organization->sendingIdentities()->where('verification_status', 'verified')->orderBy('from_email')->get(['id', 'from_name', 'from_email']),
            'locked' => $automation ? $automation->runs()->exists() : false,
            'steps' => $automation ? $automation->stepTree() : [],
        ];
    }

    protected function validateSettings(Request $request, Organization $organization): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => ['required', Rule::in(array_keys(Automation::TRIGGERS))],
            'tag' => ['nullable', 'required_if:trigger_type,tag_added', 'string', 'max:50', 'not_regex:/,/'],
            'sending_identity_id' => [
                'nullable',
                Rule::exists('sending_identities', 'id')->where('organization_id', $organization->id),
            ],
        ], [
            'tag.required_if' => 'Enter the tag that should start this automation.',
            'tag.not_regex' => 'Enter a single tag (no commas).',
        ]);

        return [
            'name' => trim($data['name']),
            'trigger_type' => $data['trigger_type'],
            'trigger_config' => $data['trigger_type'] === 'tag_added'
                ? ['tag' => mb_strtolower(trim((string) $data['tag']))]
                : null,
            'sending_identity_id' => $data['sending_identity_id'] ?? null,
        ];
    }

    /**
     * Why this automation cannot be switched on yet, or null if it is ready.
     */
    protected function readinessProblem(Automation $automation, Organization $organization): ?string
    {
        $identity = $automation->sendingIdentity;

        if (!$identity || $identity->verification_status !== 'verified') {
            return 'Choose a verified sending identity before switching this automation on.';
        }

        $templateIds = $automation->nodes()->where('type', 'email')->get()
            ->map(fn ($node) => (int) ($node->config['template_id'] ?? 0));

        if ($templateIds->isEmpty()) {
            return 'Add at least one email step before switching this automation on.';
        }

        $existing = Template::where('organization_id', $organization->id)->whereIn('id', $templateIds->unique())->count();

        if ($existing !== $templateIds->unique()->count()) {
            return 'One of the email steps uses a template that no longer exists.';
        }

        return null;
    }

    /**
     * Turn the editor's JSON into a clean list of steps, or throw a validation error.
     * Nothing from the browser is trusted: types, numbers and template ownership are
     * all re-checked here.
     *
     * @return list<array<string, mixed>>
     */
    protected function cleanSteps(mixed $raw, Organization $organization): array
    {
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        if (!is_array($decoded)) {
            $this->fail('The steps could not be read. Please reload the page and try again.');
        }

        $templateIds = $organization->templates()->pluck('id')->all();
        $count = 0;

        return $this->cleanChain(array_values($decoded), $templateIds, false, $count);
    }

    /**
     * @param list<int> $templateIds
     * @return list<array<string, mixed>>
     */
    protected function cleanChain(array $steps, array $templateIds, bool $inBranch, int &$count): array
    {
        $clean = [];
        $last = count($steps) - 1;

        foreach (array_values($steps) as $i => $step) {
            if (!is_array($step)) {
                $this->fail('One of the steps is not valid.');
            }

            if (++$count > self::MAX_STEPS) {
                $this->fail('An automation can have at most '.self::MAX_STEPS.' steps.');
            }

            switch ($step['type'] ?? null) {
                case 'email':
                    $id = (int) ($step['template_id'] ?? 0);

                    if (!in_array($id, $templateIds, true)) {
                        $this->fail('Choose a template for every email step.');
                    }

                    $clean[] = ['type' => 'email', 'template_id' => $id];
                    break;

                case 'wait':
                    $amount = (int) ($step['amount'] ?? 0);
                    $unit = $step['unit'] ?? '';

                    if ($amount < 1 || $amount > 365 || !in_array($unit, ['hours', 'days'], true)) {
                        $this->fail('Each wait must be between 1 and 365 hours or days.');
                    }

                    $clean[] = ['type' => 'wait', 'amount' => $amount, 'unit' => $unit];
                    break;

                case 'condition':
                    if ($inBranch) {
                        $this->fail('A condition cannot be placed inside a Yes/No branch.');
                    }

                    if ($i !== $last) {
                        $this->fail('A condition must be the last step; the Yes and No branches continue from it.');
                    }

                    if (!collect($clean)->contains(fn ($s) => $s['type'] === 'email')) {
                        $this->fail('A condition needs an email step before it (it checks that email).');
                    }

                    $check = $step['check'] ?? '';

                    if (!in_array($check, ['opened', 'clicked'], true)) {
                        $this->fail('Choose what each condition should check.');
                    }

                    $clean[] = [
                        'type' => 'condition',
                        'check' => $check,
                        'yes' => $this->cleanChain((array) ($step['yes'] ?? []), $templateIds, true, $count),
                        'no' => $this->cleanChain((array) ($step['no'] ?? []), $templateIds, true, $count),
                    ];
                    break;

                default:
                    $this->fail('One of the steps has an unknown type.');
            }
        }

        return $clean;
    }

    /**
     * Save a chain of steps: each one hangs under the previous one. A condition's
     * Yes and No lists hang under the condition with branch = yes / no.
     *
     * @param list<array<string, mixed>> $steps
     */
    protected function createChain(Automation $automation, array $steps, ?int $parentId, ?string $branch): void
    {
        foreach ($steps as $step) {
            $config = $step;
            unset($config['type'], $config['yes'], $config['no']);

            $node = $automation->nodes()->create([
                'parent_id' => $parentId,
                'branch' => $branch,
                'type' => $step['type'],
                'config' => $config,
            ]);

            if ($step['type'] === 'condition') {
                $this->createChain($automation, $step['yes'], $node->id, 'yes');
                $this->createChain($automation, $step['no'], $node->id, 'no');
            }

            $parentId = $node->id;
            $branch = null;
        }
    }

    protected function fail(string $message): never
    {
        throw ValidationException::withMessages(['steps_json' => $message]);
    }
}
