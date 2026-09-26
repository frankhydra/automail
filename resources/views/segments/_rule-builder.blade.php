{{-- Included by segments/create.blade.php and segments/edit.blade.php.
     Expects: $operators (array<string,list<string>>), $initialRulesJson (string, valid JSON). --}}
<div x-data="automailSegmentBuilder({{ $initialRulesJson }})" class="space-y-4">
    <template x-for="(rule, index) in rules" :key="index">
        <div class="flex flex-wrap items-end gap-3 bg-gray-50 dark:bg-gray-700 p-3 rounded-md border border-gray-200 dark:border-gray-600">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Field</label>
                <select :name="'rule_field[' + index + ']'" x-model="rule.field" @change="onFieldChange(index)" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                    <option value="status">Status</option>
                    <option value="tag">Tag</option>
                    <option value="email_domain">Email domain</option>
                    <option value="created_at">Date added</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Operator</label>
                <select :name="'rule_operator[' + index + ']'" x-model="rule.operator" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                    <template x-for="op in operatorsFor(rule.field)" :key="op">
                        <option :value="op" x-text="operatorLabel(op)"></option>
                    </template>
                </select>
            </div>
            <div class="flex-1 min-w-[10rem]">
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="valueLabel(rule.field)"></label>
                <input :type="rule.field === 'created_at' ? 'date' : 'text'" :name="'rule_value[' + index + ']'" x-model="rule.value" :placeholder="valuePlaceholder(rule.field)" required class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <button type="button" @click="removeRule(index)" x-show="rules.length > 1" class="text-sm font-semibold text-red-600 hover:text-red-900 dark:text-red-400 px-2 py-2">
                    Remove
                </button>
            </div>
        </div>
    </template>

    <button type="button" @click="addRule()" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
        + Add another rule
    </button>

    <p class="text-xs text-gray-500 dark:text-gray-400">All rules must match (AND). A contact matching every rule above is included in this segment.</p>
</div>

<script>
    function automailSegmentBuilder(initialRules) {
        return {
            operators: @json($operators),
            rules: initialRules && initialRules.length ? initialRules : [{ field: 'status', operator: 'equals', value: 'subscribed' }],
            operatorsFor(field) {
                return this.operators[field] || [];
            },
            operatorLabel(op) {
                return ({
                    equals: 'is',
                    not_equals: 'is not',
                    has: 'has tag',
                    not_has: "doesn't have tag",
                    before: 'before',
                    after: 'after',
                })[op] || op;
            },
            valueLabel(field) {
                return ({
                    status: 'Status value',
                    tag: 'Tag name',
                    email_domain: 'Domain (e.g. gmail.com)',
                    created_at: 'Date',
                })[field] || 'Value';
            },
            valuePlaceholder(field) {
                return field === 'status' ? 'subscribed / unsubscribed / bounced'
                    : field === 'tag' ? 'e.g. vip'
                    : field === 'email_domain' ? 'e.g. gmail.com'
                    : '';
            },
            onFieldChange(index) {
                // Reset the operator to the first valid one for the newly chosen field.
                this.rules[index].operator = this.operatorsFor(this.rules[index].field)[0] || '';
                this.rules[index].value = '';
            },
            addRule() {
                this.rules.push({ field: 'status', operator: 'equals', value: '' });
            },
            removeRule(index) {
                this.rules.splice(index, 1);
            },
        };
    }
</script>
