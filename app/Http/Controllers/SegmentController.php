<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use App\Models\User;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SegmentController extends Controller
{
    use EnsuresTeamPermission;

    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $segments = $organization ? $organization->segments()->latest()->get() : collect();

        // Computed once per request for the list view - fine at this scale (see
        // Segment::matchingContacts, which is a small number of simple queries).
        $counts = $segments->mapWithKeys(fn (Segment $segment) => [
            $segment->id => $organization ? $segment->matchingContacts($organization)->count() : 0,
        ]);

        return view('segments.index', compact('segments', 'counts'));
    }

    public function create(): View
    {
        return view('segments.create', ['operators' => Segment::availableOperators()]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManageContacts($user);
        $organization = $user->currentOrganization();

        if (!$organization) {
            return redirect()->back()->withErrors(['error' => 'No active organization found.']);
        }

        $data = $this->validateSegment($request);

        $segment = $organization->segments()->create([
            'name' => $data['name'],
            'rules' => $data['rules'],
        ]);

        $count = $segment->matchingContacts($organization)->count();

        return redirect()->route('segments.index')
            ->with('status', "Segment \"{$segment->name}\" created - it currently matches {$count} contact(s).");
    }

    public function edit(int $id): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $segment = $organization ? $organization->segments()->find($id) : null;

        if (!$segment) {
            return redirect()->route('segments.index')->withErrors(['error' => 'Segment not found.']);
        }

        return view('segments.edit', [
            'segment' => $segment,
            'operators' => Segment::availableOperators(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManageContacts($user);
        $organization = $user->currentOrganization();

        $segment = $organization ? $organization->segments()->find($id) : null;

        if (!$segment) {
            return redirect()->route('segments.index')->withErrors(['error' => 'Segment not found.']);
        }

        $data = $this->validateSegment($request);

        $segment->update([
            'name' => $data['name'],
            'rules' => $data['rules'],
        ]);

        $count = $segment->matchingContacts($organization)->count();

        return redirect()->route('segments.index')
            ->with('status', "Segment \"{$segment->name}\" updated - it now matches {$count} contact(s).");
    }

    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        // A campaign that already used this segment keeps its snapshot -
        // segment_id on that campaign just becomes null (see the migration).
        if ($organization) {
            $organization->segments()->where('id', $id)->delete();
        }

        return redirect()->route('segments.index')->with('status', 'Segment deleted.');
    }

    /**
     * Validate the "name" plus the parallel rule_field[]/rule_operator[]/rule_value[]
     * arrays the form submits, and zip them into the {field, operator, value}[]
     * shape Segment::rules expects. Rejects any field/operator pair the model
     * doesn't understand, so a segment can never silently do nothing.
     *
     * @return array{name: string, rules: list<array{field: string, operator: string, value: string}>}
     */
    protected function validateSegment(Request $request): array
    {
        $allowed = Segment::availableOperators();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rule_field' => 'required|array|min:1',
            'rule_field.*' => ['required', Rule::in(array_keys($allowed))],
            'rule_operator' => 'required|array|min:1',
            'rule_operator.*' => 'required|string',
            'rule_value' => 'required|array|min:1',
            'rule_value.*' => 'required|string|max:255',
        ]);

        $rules = [];
        foreach ($validated['rule_field'] as $index => $field) {
            $operator = $validated['rule_operator'][$index] ?? null;
            $value = $validated['rule_value'][$index] ?? null;

            if (!in_array($operator, $allowed[$field] ?? [], true)) {
                abort(422, "Invalid operator \"{$operator}\" for field \"{$field}\".");
            }

            $rules[] = ['field' => $field, 'operator' => $operator, 'value' => trim((string) $value)];
        }

        return ['name' => trim($validated['name']), 'rules' => $rules];
    }
}
