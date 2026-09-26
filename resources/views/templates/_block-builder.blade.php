{{-- Included by templates/create.blade.php and templates/edit.blade.php, INSIDE the
     <form id="template-form" ...> tag. Expects: $blockTypes (list<string>),
     $initialMode ('builder'|'html'), $initialBlocksJson (string, valid JSON array). --}}
<div x-data="automailTemplateBuilder('{{ $initialMode }}', {{ $initialBlocksJson }})" x-init="init()">

    <!-- Mode toggle -->
    <div class="flex items-center gap-4 mb-4">
        <button type="button" @click="mode = 'builder'"
            :class="mode === 'builder' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 rounded-md text-xs font-semibold uppercase tracking-wide">
            Visual Builder
        </button>
        <button type="button" @click="mode = 'html'"
            :class="mode === 'html' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 rounded-md text-xs font-semibold uppercase tracking-wide">
            Raw HTML
        </button>
    </div>

    <input type="hidden" name="blocks_json" x-ref="blocksJsonInput">

    <!-- Builder panel -->
    <div x-show="mode === 'builder'" x-cloak class="space-y-4">
        <div class="flex flex-wrap gap-2">
            @foreach ($blockTypes as $type)
                <button type="button" @click="addBlock('{{ $type }}')" class="px-3 py-1.5 text-xs font-semibold rounded-md border border-indigo-300 text-indigo-700 dark:text-indigo-300 dark:border-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-900">
                    + {{ ucfirst($type) }}
                </button>
            @endforeach
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400" x-show="blocks.length === 0">
            No blocks yet - click a button above to add your first one.
        </p>

        <div x-ref="blockList" class="space-y-3">
            <template x-for="(block, index) in blocks" :key="index">
                <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="automail-drag-handle cursor-move text-gray-400" title="Drag to reorder">&#9776;</span>
                            <span class="text-xs font-bold uppercase text-gray-500" x-text="block.type"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="moveUp(index)" x-show="index > 0" class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">&uarr; Up</button>
                            <button type="button" @click="moveDown(index)" x-show="index < blocks.length - 1" class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">&darr; Down</button>
                            <button type="button" @click="removeBlock(index)" class="text-xs font-semibold text-red-600 hover:text-red-900">Remove</button>
                        </div>
                    </div>

                    <!-- Header fields -->
                    <div x-show="block.type === 'header'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="text" x-model="block.title" placeholder="Title" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                        <input type="text" x-model="block.subtitle" placeholder="Subtitle" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                    </div>

                    <!-- Text fields -->
                    <div x-show="block.type === 'text'">
                        <textarea x-model="block.content" rows="3" placeholder="Write your message..." class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm"></textarea>
                    </div>

                    <!-- Image fields -->
                    <div x-show="block.type === 'image'" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="text" x-model="block.url" placeholder="Image URL" class="sm:col-span-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                        <input type="text" x-model="block.alt" placeholder="Alt text" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                        <input type="text" x-model="block.link" placeholder="Link when clicked (optional)" class="sm:col-span-3 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                    </div>

                    <!-- Button fields -->
                    <div x-show="block.type === 'button'" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="text" x-model="block.label" placeholder="Button label" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                        <input type="text" x-model="block.url" placeholder="Button URL" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                        <input type="text" x-model="block.color" placeholder="#4f46e5" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                    </div>

                    <!-- Divider: no fields -->
                    <p x-show="block.type === 'divider'" class="text-xs text-gray-400 italic">A plain horizontal line - no fields to set.</p>

                    <!-- Columns fields -->
                    <div x-show="block.type === 'columns'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <textarea x-model="block.left" rows="3" placeholder="Left column text" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm"></textarea>
                        <textarea x-model="block.right" rows="3" placeholder="Right column text" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm"></textarea>
                    </div>

                    <!-- Footer fields -->
                    <div x-show="block.type === 'footer'" class="grid grid-cols-1 gap-3">
                        <input type="text" x-model="block.text" placeholder="Footer text (e.g. company address)" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                        <input type="text" x-model="block.social_text" placeholder="e.g. Follow us: Twitter | Facebook" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm sm:text-sm">
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Raw HTML panel: unchanged from the previous Quill editor -->
    <div x-show="mode === 'html'" x-cloak>
        <div id="editor-container" class="bg-white text-gray-900" style="height: 350px;"></div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
    function automailTemplateBuilder(initialMode, initialBlocks) {
        return {
            mode: initialMode,
            blocks: Array.isArray(initialBlocks) ? initialBlocks : [],
            blockDefaults: {
                header: { title: 'Newsletter Title', subtitle: 'A short subtitle' },
                text: { content: 'Write your message here...' },
                image: { url: 'https://via.placeholder.com/552x200', alt: '', link: '' },
                button: { label: 'Shop Now', url: 'https://example.com', color: '#4f46e5' },
                divider: {},
                columns: { left: 'Left column text', right: 'Right column text' },
                footer: { text: 'You are receiving this because you subscribed.', social_text: '' },
            },
            addBlock(type) {
                this.blocks.push(Object.assign({ type: type }, this.blockDefaults[type] || {}));
                this.$nextTick(() => this.initSortable());
            },
            removeBlock(index) {
                this.blocks.splice(index, 1);
            },
            moveUp(index) {
                if (index > 0) {
                    var tmp = this.blocks[index - 1];
                    this.blocks[index - 1] = this.blocks[index];
                    this.blocks[index] = tmp;
                }
            },
            moveDown(index) {
                if (index < this.blocks.length - 1) {
                    var tmp = this.blocks[index + 1];
                    this.blocks[index + 1] = this.blocks[index];
                    this.blocks[index] = tmp;
                }
            },
            initSortable() {
                var el = this.$refs.blockList;
                if (!el || el.dataset.sortableInit || typeof Sortable === 'undefined') return;
                el.dataset.sortableInit = '1';
                var self = this;
                Sortable.create(el, {
                    handle: '.automail-drag-handle',
                    animation: 150,
                    onEnd: function (evt) {
                        var moved = self.blocks.splice(evt.oldIndex, 1)[0];
                        self.blocks.splice(evt.newIndex, 0, moved);
                    },
                });
            },
            // Called by the "submit" listener added on #template-form below.
            syncBeforeSubmit() {
                this.$refs.blocksJsonInput.value = this.mode === 'builder' ? JSON.stringify(this.blocks) : '';
            },
            init() {
                this.$nextTick(() => this.initSortable());
                document.getElementById('template-form').addEventListener('submit', () => this.syncBeforeSubmit());
            },
        };
    }
</script>
