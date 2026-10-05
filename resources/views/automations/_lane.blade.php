{{--
    One vertical list of steps ("lane"). Used for the main journey and, under a condition,
    for its Yes and No branches. Expects the Alpine loop variable `lane`:
    { id, label, steps, canAdd, allowCondition }.
--}}
<div class="w-full flex flex-col items-center">
    <div x-show="lane.label" x-cloak class="mb-3 px-3 py-1 rounded-full text-xs font-bold border"
         :class="lane.id === 'yes' ? 'bg-success-tint text-success border-success/30' : 'bg-warning-tint text-warning border-warning/30'"
         x-text="lane.id === 'yes' ? 'YES: ' + lane.label : 'NO: ' + lane.label"></div>

    <template x-for="(step, i) in lane.steps" :key="step.uid">
        <div class="w-full flex flex-col items-center">
            <div x-show="i > 0 || lane.id === 'main'" class="w-px h-5 bg-border"></div>

            <div class="w-full max-w-md bg-card border rounded-xl shadow-soft p-4"
                 :class="step.type === 'condition' ? 'border-sun' : 'border-border'">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                          :class="{ 'bg-info-tint text-info': step.type === 'email', 'bg-paper-tint text-muted': step.type === 'wait', 'bg-warning-tint text-warning': step.type === 'condition' }"
                          x-text="step.type === 'email' ? 'Action: send email' : (step.type === 'wait' ? 'Delay' : 'Condition: if / else')"></span>
                    <div x-show="!locked" class="flex items-center gap-1 text-muted">
                        <button type="button" @click="moveStep(lane, i, -1)" :disabled="i === 0" class="px-1.5 hover:text-ink disabled:opacity-30" title="Move up">&uarr;</button>
                        <button type="button" @click="moveStep(lane, i, 1)" :disabled="i === lane.steps.length - 1" class="px-1.5 hover:text-ink disabled:opacity-30" title="Move down">&darr;</button>
                        <button type="button" @click="removeStep(lane, i)" class="px-1.5 hover:text-danger" title="Remove step">&times;</button>
                    </div>
                </div>

                <template x-if="step.type === 'email'">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Template</label>
                        <select x-model="step.template_id" :disabled="locked" class="w-full text-sm rounded-lg bg-paper border-border text-ink focus:border-accent focus:ring-accent disabled:opacity-60">
                            <option value="">Choose a template...</option>
                            <template x-for="t in templates" :key="t.id"><option :value="t.id" x-text="t.name"></option></template>
                        </select>
                        <p class="text-[11px] text-muted mt-1">The subject and content come from the template, so editing it updates this step.</p>
                    </div>
                </template>

                <template x-if="step.type === 'wait'">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-ink">Wait</span>
                        <input type="number" min="1" max="365" x-model="step.amount" :disabled="locked" class="w-20 text-sm rounded-lg bg-paper border-border text-ink focus:border-accent focus:ring-accent disabled:opacity-60">
                        <select x-model="step.unit" :disabled="locked" class="text-sm rounded-lg bg-paper border-border text-ink focus:border-accent focus:ring-accent disabled:opacity-60">
                            <option value="hours">hours</option>
                            <option value="days">days</option>
                        </select>
                    </div>
                </template>

                <template x-if="step.type === 'condition'">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Check the previous email</label>
                        <select x-model="step.check" :disabled="locked" class="w-full text-sm rounded-lg bg-paper border-border text-ink focus:border-accent focus:ring-accent disabled:opacity-60">
                            <option value="opened">Did the contact open it?</option>
                            <option value="clicked">Did the contact click a link in it?</option>
                        </select>
                        <p class="text-[11px] text-muted mt-1">Put a Wait before the condition, so people have time to open the email.</p>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <div x-show="lane.canAdd && !locked" x-cloak class="mt-4 flex flex-col items-center">
        <div x-show="lane.steps.length > 0 || lane.id === 'main'" class="w-px h-4 bg-border mb-2"></div>
        <div class="flex flex-wrap justify-center gap-2">
            <button type="button" @click="addStep(lane, 'email')" class="px-3 py-1.5 rounded-lg border border-dashed border-border text-xs font-bold text-ink hover:border-accent hover:text-accent">+ Email</button>
            <button type="button" @click="addStep(lane, 'wait')" class="px-3 py-1.5 rounded-lg border border-dashed border-border text-xs font-bold text-ink hover:border-accent hover:text-accent">+ Wait</button>
            <button type="button" x-show="lane.allowCondition" @click="addStep(lane, 'condition')" class="px-3 py-1.5 rounded-lg border border-dashed border-sun text-xs font-bold text-ink hover:border-accent hover:text-accent">+ Condition</button>
        </div>
    </div>
</div>
