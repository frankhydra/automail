{{--
    Journey editor, shared by automations/create and automations/edit.
    Expects: $action, $method, $automation (nullable), $templates, $identities, $locked, $steps.
--}}
@php
    $config = [
        'name' => old('name', $automation->name ?? ''),
        'triggerType' => old('trigger_type', $automation->trigger_type ?? 'contact_added'),
        'tag' => old('tag', $automation ? $automation->triggerTag() : ''),
        'identityId' => (string) old('sending_identity_id', $automation->sending_identity_id ?? ''),
        'locked' => $locked,
        'templates' => $templates,
        'main' => old('steps_json') ? (json_decode(old('steps_json'), true) ?: []) : $steps,
    ];

    $input = 'w-full text-sm rounded-lg bg-paper border-border text-ink placeholder:text-muted focus:border-accent focus:ring-accent';
    $label = 'block text-xs font-bold uppercase tracking-wider text-muted mb-1.5';
@endphp

<form x-data="automationEditor(@js($config))" method="POST" action="{{ $action }}" class="py-8">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    <input type="hidden" name="steps_json" :value="JSON.stringify(main)" :disabled="locked">

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <a href="{{ route('automations.index') }}" class="text-sm font-semibold text-muted hover:text-ink">&larr; Journeys</a>
                <h1 class="text-3xl font-extrabold tracking-tight text-ink mt-1">{{ $automation ? 'Edit journey' : 'New journey' }}</h1>
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-accent text-white text-sm font-bold hover:opacity-90 transition">Save</button>
        </div>

        <x-flash-messages />

        @if ($errors->any())
            <div class="p-4 bg-danger-tint border border-danger/30 text-danger rounded-lg text-sm">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        @if ($locked)
            <div class="p-4 bg-warning-tint border border-warning/30 text-ink rounded-lg text-sm">
                Contacts are already travelling through this journey, so its steps are locked. You can still change the name, trigger and sender. To change the steps, duplicate the journey by creating a new one.
            </div>
        @endif

        <!-- Settings -->
        <div class="bg-card border border-border rounded-xl shadow-soft p-6 space-y-5">
            <div>
                <label class="{{ $label }}" for="name">Journey name</label>
                <input id="name" type="text" name="name" x-model="name" required maxlength="255" placeholder="e.g. Welcome series" class="{{ $input }}">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}" for="trigger_type">Starts when</label>
                    <select id="trigger_type" name="trigger_type" x-model="triggerType" class="{{ $input }}">
                        @foreach (\App\Models\Automation::TRIGGERS as $value => $text)
                            <option value="{{ $value }}">{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="triggerType === 'tag_added'" x-cloak>
                    <label class="{{ $label }}" for="tag">Tag</label>
                    <input id="tag" type="text" name="tag" x-model="tag" maxlength="50" placeholder="e.g. vip" class="{{ $input }}">
                </div>
            </div>

            <div>
                <label class="{{ $label }}" for="sending_identity_id">Send from</label>
                <select id="sending_identity_id" name="sending_identity_id" x-model="identityId" class="{{ $input }}">
                    <option value="">Choose a verified sending identity...</option>
                    @foreach ($identities as $identity)
                        <option value="{{ $identity->id }}">{{ $identity->from_name }} &lt;{{ $identity->from_email }}&gt;</option>
                    @endforeach
                </select>
                @if ($identities->isEmpty())
                    <p class="text-xs text-warning mt-1">You have no verified sending identity yet. Add and verify one under Sending Identities.</p>
                @endif
            </div>

            <p class="text-[11px] text-muted">Only contacts who join or are tagged <strong>after</strong> you switch the journey on enter it, and each contact goes through a journey once. Unsubscribed contacts are removed automatically.</p>
        </div>

        @if ($templates->isEmpty())
            <div class="p-4 bg-paper-tint border border-border rounded-lg text-sm text-ink">
                You need at least one email template first. <a href="{{ route('templates.create') }}" class="font-bold text-accent underline">Create one in the Email Builder</a>, then come back.
            </div>
        @endif

        <!-- Flow -->
        <div class="bg-paper-tint border border-border rounded-xl p-6 sm:p-8">
            <div class="flex flex-col items-center">
                <div class="w-full max-w-md bg-card border-2 border-ink rounded-xl shadow-soft p-4 text-center">
                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-sun text-sidebar">Journey trigger</span>
                    <div class="mt-2 font-bold text-ink" x-text="triggerType === 'tag_added' ? 'Tag \'' + (tag || '...') + '\' is added' : 'A new contact is added'"></div>
                </div>

                <template x-for="lane in [mainLane]" :key="lane.id">
                    @include('automations._lane')
                </template>

                <div x-show="branchLanes.length" x-cloak class="w-full mt-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <template x-for="lane in branchLanes" :key="lane.id">
                            @include('automations._lane')
                        </template>
                    </div>
                </div>

                <p x-show="main.length === 0" x-cloak class="mt-6 text-sm text-muted text-center">Add your first step: usually an email.</p>
            </div>
        </div>
    </div>
</form>

@verbatim
<script>
    document.addEventListener('alpine:init', function () {
        Alpine.data('automationEditor', function (cfg) {
            var counter = 0;

            // Every step gets a throw-away id so the list can be re-ordered safely.
            function withIds(steps) {
                (steps || []).forEach(function (step) {
                    step.uid = ++counter;
                    if (step.type === 'condition') {
                        step.yes = withIds(step.yes);
                        step.no = withIds(step.no);
                    }
                });
                return steps || [];
            }

            function newStep(type) {
                var step = { uid: ++counter, type: type };
                if (type === 'email') { step.template_id = ''; }
                if (type === 'wait') { step.amount = 2; step.unit = 'days'; }
                if (type === 'condition') { step.check = 'opened'; step.yes = []; step.no = []; }
                return step;
            }

            return {
                name: cfg.name,
                triggerType: cfg.triggerType,
                tag: cfg.tag,
                identityId: cfg.identityId,
                locked: cfg.locked,
                templates: cfg.templates,
                main: withIds(cfg.main),

                get lastIsCondition() {
                    var last = this.main[this.main.length - 1];
                    return !!last && last.type === 'condition';
                },

                // The main list. Once it ends in a condition, nothing can follow it:
                // the Yes and No branches continue from there.
                get mainLane() {
                    return {
                        id: 'main',
                        label: null,
                        steps: this.main,
                        canAdd: !this.lastIsCondition,
                        allowCondition: this.main.some(function (s) { return s.type === 'email'; })
                    };
                },

                get branchLanes() {
                    if (!this.lastIsCondition) { return []; }
                    var condition = this.main[this.main.length - 1];
                    return [
                        { id: 'yes', label: condition.check === 'clicked' ? 'clicked' : 'opened', steps: condition.yes, canAdd: true, allowCondition: false },
                        { id: 'no', label: condition.check === 'clicked' ? 'did not click' : 'did not open', steps: condition.no, canAdd: true, allowCondition: false }
                    ];
                },

                addStep: function (lane, type) {
                    if (this.locked) { return; }
                    lane.steps.push(newStep(type));
                },

                removeStep: function (lane, i) {
                    if (this.locked) { return; }
                    lane.steps.splice(i, 1);
                },

                moveStep: function (lane, i, dir) {
                    var j = i + dir;
                    if (this.locked || j < 0 || j >= lane.steps.length) { return; }
                    // A condition always stays last.
                    if (lane.steps[i].type === 'condition' || lane.steps[j].type === 'condition') { return; }
                    var moved = lane.steps.splice(i, 1)[0];
                    lane.steps.splice(j, 0, moved);
                }
            };
        });
    });
</script>
@endverbatim
