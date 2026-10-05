{{--
    Email Builder: shared by templates/create and templates/edit.
    Expects: $action (form URL), $method ('POST'|'PUT'), $template (Template|null), $senderLabel (string).

    Three panes: block palette / envelope (left), canvas (centre), block properties (right).
    The canvas is an editing view; "Preview" asks the server to compile the same HTML a
    campaign would send (TemplateBlockRenderer), so what you preview is what gets sent.
--}}
@php
    $starter = [
        ['type' => 'header', 'title' => 'Your headline here', 'subtitle' => 'A short supporting line', 'align' => 'center'],
        ['type' => 'text', 'content' => "Hello {{first_name}},\n\nWrite your message here.", 'font_size' => 'medium', 'align' => 'left'],
        ['type' => 'button', 'label' => 'Learn more', 'url' => 'https://', 'color' => '#B85D33', 'align' => 'center'],
        ['type' => 'footer', 'text' => 'You are receiving this because you subscribed.', 'social_text' => ''],
    ];

    $oldBlocks = old('blocks_json') ? json_decode(old('blocks_json'), true) : null;

    if (is_array($oldBlocks)) {
        $initialBlocks = $oldBlocks;
    } elseif ($template && !empty($template->blocks)) {
        $initialBlocks = $template->blocks;
    } elseif ($template) {
        $initialBlocks = []; // legacy raw-HTML template
    } else {
        $initialBlocks = $starter; // brand-new template
    }

    $initialMode = old('blocks_json') ? 'builder' : (old('body') ? 'html' : ($template && empty($template->blocks) ? 'html' : 'builder'));

    $nameParts = preg_split('/\s+/', trim((string) Auth::user()->name), 2);

    $config = [
        'mode' => $initialMode,
        'blocks' => $initialBlocks,
        'name' => old('name', $template->name ?? ''),
        'subject' => old('subject', $template->subject ?? ''),
        'previewText' => old('preview_text', $template->preview_text ?? ''),
        'htmlBody' => old('body', $template->body ?? ''),
        'senderLabel' => $senderLabel,
        'userEmail' => Auth::user()->email,
        'userFirst' => $nameParts[0] ?? '',
        'userLast' => $nameParts[1] ?? '',
        'previewUrl' => route('templates.preview'),
        'sendTestUrl' => route('templates.send-test'),
        'assetsListUrl' => route('assets.list'),
        'assetsStoreUrl' => route('assets.store'),
    ];

    $palette = [
        ['header', 'Header', 'M5 6v12M19 6v12M5 12h14'],
        ['text', 'Text', 'M5 6h14M12 6v13'],
        ['image', 'Image', 'M3 5h18v14H3zM3 16l5-5 4 4 3-3 6 6'],
        ['button', 'Button', 'M4 8h16v8H4zM8 12h8'],
        ['divider', 'Divider', 'M4 12h16'],
        ['spacer', 'Spacer', 'M12 4v16M8 8l4-4 4 4M8 16l4 4 4-4'],
        ['columns', '2 Columns', 'M4 5h7v14H4zM13 5h7v14h-7z'],
        ['social', 'Social', 'M9 12a3 3 0 11-6 0 3 3 0 016 0zM21 6a3 3 0 11-6 0 3 3 0 016 0zM21 18a3 3 0 11-6 0 3 3 0 016 0zM8.6 10.7l6.8-3.9M8.6 13.3l6.8 3.9'],
        ['footer', 'Footer', 'M4 18h16M8 14h8'],
    ];

    // [key, label, kind, extra]
    $fields = [
        'header' => [['title', 'Title', 'input'], ['subtitle', 'Subtitle', 'input'], ['align', 'Alignment', 'align']],
        'text' => [['content', 'Text', 'textarea'], ['font_size', 'Font size', 'size'], ['align', 'Alignment', 'align']],
        'image' => [['url', 'Image', 'imageurl', 'https://...'], ['alt', 'Alt text', 'input'], ['link', 'Link when clicked (optional)', 'input', 'https://...']],
        'button' => [['label', 'Button label', 'input'], ['url', 'Button link', 'input', 'https://...'], ['color', 'Button colour', 'color'], ['align', 'Alignment', 'align']],
        'spacer' => [['height', 'Height (px, 4 to 120)', 'number']],
        'columns' => [['left', 'Left column', 'textarea'], ['right', 'Right column', 'textarea']],
        'social' => [['twitter', 'Twitter link', 'input', 'https://...'], ['facebook', 'Facebook link', 'input', 'https://...'], ['instagram', 'Instagram link', 'input', 'https://...'], ['linkedin', 'LinkedIn link', 'input', 'https://...']],
        'footer' => [['text', 'Footer text', 'input'], ['social_text', 'Extra line (optional)', 'input']],
    ];

    $input = 'w-full text-sm rounded-lg bg-paper border-border text-ink placeholder:text-muted focus:border-accent focus:ring-accent';
    $label = 'block text-xs font-bold uppercase tracking-wider text-muted mb-1.5';
@endphp

<form x-data="automailEditor(@js($config))" x-init="init()" @submit="submitting = true"
      @keydown.escape.window="testOpen = false; previewOpen = false; pickerOpen = false"
      method="POST" action="{{ $action }}" class="flex flex-col lg:h-[calc(100vh-4rem)]">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    <input type="hidden" name="blocks_json" :value="mode === 'builder' ? JSON.stringify(blocks) : ''">

    <!-- Editor top bar -->
    <div class="flex flex-wrap items-center gap-3 bg-card border-b border-border px-4 sm:px-6 py-3 shrink-0">
        <a href="{{ route('templates.index') }}" class="text-sm font-semibold text-muted hover:text-ink">&larr; Templates</a>

        <input type="text" name="name" x-model="name" required maxlength="255" placeholder="Name this template"
               class="flex-1 min-w-[10rem] max-w-md text-base font-bold rounded-lg bg-transparent border-transparent hover:border-border focus:border-accent focus:ring-accent text-ink placeholder:text-muted">

        <span x-show="dirty" x-cloak class="text-xs font-semibold text-warning flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-warning"></span> Unsaved changes
        </span>

        <div class="ml-auto flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-lg border border-border overflow-hidden text-xs font-bold">
                <button type="button" @click="mode = 'builder'" :class="mode === 'builder' ? 'bg-accent text-white' : 'bg-card text-muted hover:text-ink'" class="px-3 py-2">Visual</button>
                <button type="button" @click="mode = 'html'" :class="mode === 'html' ? 'bg-accent text-white' : 'bg-card text-muted hover:text-ink'" class="px-3 py-2">HTML</button>
            </div>

            <div class="inline-flex rounded-lg border border-border overflow-hidden text-xs font-bold" x-show="mode === 'builder'">
                <button type="button" @click="device = 'desktop'" :class="device === 'desktop' ? 'bg-paper-tint text-ink' : 'bg-card text-muted'" class="px-3 py-2">Desktop</button>
                <button type="button" @click="device = 'mobile'" :class="device === 'mobile' ? 'bg-paper-tint text-ink' : 'bg-card text-muted'" class="px-3 py-2">Mobile</button>
            </div>

            <button type="button" @click="openPreview()" class="px-3 py-2 rounded-lg border border-border bg-card text-sm font-semibold text-ink hover:border-accent transition">Preview</button>
            <button type="button" @click="testOpen = true; testMsg = ''; testErr = ''" class="px-3 py-2 rounded-lg border border-border bg-card text-sm font-semibold text-ink hover:border-accent transition">Send Test&hellip;</button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-accent text-white text-sm font-bold hover:opacity-90 transition">Save Template</button>
        </div>
    </div>

    @if ($errors->any())
        <div class="px-4 sm:px-6 pt-3 shrink-0">
            <div class="p-3 bg-danger-tint border border-danger/30 text-danger rounded-lg text-sm">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row flex-1 min-h-0">

        <!-- LEFT: blocks / envelope -->
        <aside class="lg:w-72 shrink-0 bg-card border-b lg:border-b-0 lg:border-r border-border overflow-y-auto">
            <div class="grid grid-cols-2 p-3 gap-2 border-b border-border">
                <button type="button" @click="tab = 'blocks'" :class="tab === 'blocks' ? 'bg-accent text-white' : 'bg-paper-tint text-muted hover:text-ink'" class="py-2 rounded-lg text-sm font-bold">Blocks</button>
                <button type="button" @click="tab = 'envelope'" :class="tab === 'envelope' ? 'bg-accent text-white' : 'bg-paper-tint text-muted hover:text-ink'" class="py-2 rounded-lg text-sm font-bold">Envelope</button>
            </div>

            <!-- Blocks tab -->
            <div x-show="tab === 'blocks'" class="p-4 space-y-5">
                <div x-show="mode !== 'builder'" x-cloak class="text-xs text-muted bg-paper-tint border border-border rounded-lg p-3">
                    You are in HTML mode. Switch to <strong>Visual</strong> to use blocks. Saving in one mode replaces the other mode's content.
                </div>

                <div :class="mode !== 'builder' && 'opacity-40 pointer-events-none'">
                    <div class="{{ $label }}">Click to add a block</div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($palette as [$type, $text, $d])
                            <button type="button" @click="addBlock('{{ $type }}')"
                                    class="flex flex-col items-center gap-1.5 py-3 rounded-lg border border-border bg-paper text-ink hover:border-accent hover:text-accent transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $d }}"/></svg>
                                <span class="text-xs font-semibold">{{ $text }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="{{ $label }}">Merge tags <span class="normal-case font-medium">(click to insert)</span></div>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['first_name', 'last_name', 'email'] as $tag)
                            <button type="button" @click="insertTag('@{{'.$tag.'}}')" class="px-2 py-1 rounded-md bg-paper-tint border border-border text-[11px] font-mono text-ink hover:border-accent">@{{ {{ $tag }} }}</button>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-muted mt-2">Inserted where your cursor last was (a text field in the properties panel, or the subject).</p>
                </div>
            </div>

            <!-- Envelope tab -->
            <div x-show="tab === 'envelope'" x-cloak class="p-4 space-y-5">
                <div>
                    <label class="{{ $label }}" for="subject">Subject line</label>
                    <input id="subject" type="text" name="subject" x-model="subject" maxlength="255" @focusin="lastField = $event.target"
                           placeholder="e.g. Big news from our team" class="{{ $input }}">
                    <div class="text-[11px] text-muted mt-1"><span x-text="subject.length"></span> characters &middot; under 60 reads best on phones</div>
                </div>

                <div>
                    <label class="{{ $label }}" for="preview_text">Preview text</label>
                    <input id="preview_text" type="text" name="preview_text" x-model="previewText" maxlength="255" @focusin="lastField = $event.target"
                           placeholder="The grey snippet shown after the subject" class="{{ $input }}">
                    <div x-show="mode === 'html'" x-cloak class="text-[11px] text-warning mt-1">Preview text is only added in Visual mode.</div>
                </div>

                <div>
                    <div class="{{ $label }}">From</div>
                    <div class="text-sm text-ink bg-paper-tint border border-border rounded-lg px-3 py-2 break-words" x-text="senderLabel"></div>
                    <p class="text-[11px] text-muted mt-1">The sender and reply-to come from the Sending Identity you choose when you create a campaign.</p>
                </div>
            </div>
        </aside>

        <!-- CENTRE: canvas -->
        <section class="flex-1 min-w-0 overflow-y-auto bg-paper-tint p-4 sm:p-8" @click.self="selected = null">

            <!-- Visual mode -->
            <div x-show="mode === 'builder'" class="mx-auto transition-all duration-200" :style="{ maxWidth: device === 'mobile' ? '375px' : '640px' }">
                <div class="bg-card border border-border rounded-t-xl px-5 py-3 text-xs space-y-1">
                    <div class="text-muted"><span class="font-bold text-ink">From:</span> <span x-text="senderLabel"></span></div>
                    <div class="text-muted"><span class="font-bold text-ink">Subject:</span> <span x-text="subject || '(no subject yet)'"></span></div>
                    <div class="text-muted/80" x-show="previewText"><span class="font-bold text-ink">Snippet:</span> <span x-text="previewText"></span></div>
                </div>

                <div class="bg-white border border-border border-t-0 rounded-b-xl shadow-soft text-[#374151] font-sans" style="font-family: Arial, Helvetica, sans-serif;">
                    <template x-if="blocks.length === 0">
                        <div class="py-20 text-center text-sm text-[#9ca3af]">No blocks yet. Click a block on the left to add your first one.</div>
                    </template>

                    <template x-for="(block, i) in blocks" :key="i">
                        <div @click.stop="select(i)" :data-selected="selected === i"
                             class="relative cursor-pointer"
                             :class="selected === i ? 'ring-2 ring-accent ring-inset' : 'hover:ring-1 hover:ring-accent/50 hover:ring-inset'">

                            <div x-show="selected === i" class="absolute -top-3 right-3 z-10 flex items-center gap-0.5 bg-[#1D1E22] text-white rounded-md px-1 py-0.5 shadow">
                                <span class="px-1.5 text-[10px] uppercase tracking-wider text-[#D8C7B3]" x-text="block.type"></span>
                                <button type="button" @click.stop="move(i, -1)" :disabled="i === 0" class="px-1.5 hover:text-sun disabled:opacity-30" title="Move up">&uarr;</button>
                                <button type="button" @click.stop="move(i, 1)" :disabled="i === blocks.length - 1" class="px-1.5 hover:text-sun disabled:opacity-30" title="Move down">&darr;</button>
                                <button type="button" @click.stop="remove(i)" class="px-1.5 hover:text-red-400" title="Delete block">&times;</button>
                            </div>

                            <template x-if="block.type === 'header'">
                                <div class="px-6 pt-8 pb-4" :style="{ textAlign: block.align || 'center' }">
                                    <div class="text-2xl font-bold text-[#111827]" x-text="block.title || 'Headline'"></div>
                                    <div class="mt-2 text-sm text-[#6b7280]" x-text="block.subtitle"></div>
                                </div>
                            </template>

                            <template x-if="block.type === 'text'">
                                <div class="px-6 py-2 whitespace-pre-line leading-relaxed" :style="{ fontSize: textPx(block) + 'px', textAlign: block.align || 'left' }" x-text="block.content || 'Empty text block'"></div>
                            </template>

                            <template x-if="block.type === 'image'">
                                <div class="px-6 py-2">
                                    <template x-if="block.url">
                                        <img :src="block.url" :alt="block.alt" class="block w-full h-auto">
                                    </template>
                                    <template x-if="!block.url">
                                        <div class="h-40 border-2 border-dashed border-[#d1d5db] rounded-lg flex items-center justify-center text-sm text-[#9ca3af] text-center px-4">Add an image URL in the properties panel</div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="block.type === 'button'">
                                <div class="px-6 py-4" :style="{ textAlign: block.align || 'center' }">
                                    <span class="inline-block px-6 py-3 rounded-md text-sm font-bold text-white" :style="{ backgroundColor: block.color || '#B85D33' }" x-text="block.label || 'Button'"></span>
                                </div>
                            </template>

                            <template x-if="block.type === 'divider'">
                                <div class="px-6 py-2"><hr class="border-0 border-t border-[#e5e7eb]"></div>
                            </template>

                            <template x-if="block.type === 'spacer'">
                                <div class="relative" :style="{ height: Math.min(120, Math.max(4, Number(block.height) || 24)) + 'px' }">
                                    <span class="absolute inset-0 flex items-center justify-center text-[10px] uppercase tracking-wider text-[#d1d5db]">Spacer</span>
                                </div>
                            </template>

                            <template x-if="block.type === 'columns'">
                                <div class="px-6 py-2 grid gap-6 text-sm leading-relaxed whitespace-pre-line" :class="device === 'mobile' ? 'grid-cols-1' : 'grid-cols-2'">
                                    <div x-text="block.left || 'Left column'"></div>
                                    <div x-text="block.right || 'Right column'"></div>
                                </div>
                            </template>

                            <template x-if="block.type === 'social'">
                                <div class="px-6 py-3 text-center text-sm font-bold text-[#6b7280] space-x-4">
                                    <template x-for="n in ['twitter', 'facebook', 'instagram', 'linkedin']" :key="n">
                                        <span x-show="block[n]" x-text="n.charAt(0).toUpperCase() + n.slice(1)"></span>
                                    </template>
                                    <span x-show="!block.twitter && !block.facebook && !block.instagram && !block.linkedin" class="font-normal italic text-[#9ca3af]">Add social links in the properties panel</span>
                                </div>
                            </template>

                            <template x-if="block.type === 'footer'">
                                <div class="px-6 py-6 text-center text-xs text-[#9ca3af] border-t border-[#e5e7eb]">
                                    <div x-text="block.text"></div>
                                    <div x-text="block.social_text"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <p class="text-center text-[11px] text-muted mt-3">The canvas is an editing view. Use <strong>Preview</strong> to see the exact email that will be sent. An unsubscribe link is added automatically to campaigns.</p>
            </div>

            <!-- HTML mode -->
            <div x-show="mode === 'html'" x-cloak class="mx-auto max-w-4xl">
                <label class="{{ $label }}" for="body">Email HTML</label>
                <textarea id="body" name="body" x-model="htmlBody" :disabled="mode !== 'html'" rows="26" spellcheck="false" @focusin="lastField = $event.target"
                          class="w-full font-mono text-xs rounded-lg bg-card border-border text-ink focus:border-accent focus:ring-accent"></textarea>
                <p class="text-[11px] text-muted mt-2">Merge tags like <code>@{{first_name}}</code> work here too. Use inline styles for the best email-client support.</p>
            </div>
        </section>

        <!-- RIGHT: properties -->
        <aside class="lg:w-72 shrink-0 bg-card border-t lg:border-t-0 lg:border-l border-border overflow-y-auto" @focusin="if ($event.target.matches('input, textarea')) lastField = $event.target">
            <div class="px-4 py-4 border-b border-border">
                <div class="text-xs font-bold uppercase tracking-wider text-muted">Block properties</div>
                <div class="text-sm font-semibold text-ink mt-1" x-text="current ? 'Selected: ' + current.type : 'Nothing selected'"></div>
            </div>

            <div class="p-4">
                <p x-show="!current" class="text-sm text-muted">Click any block in the canvas to edit its content and style.</p>

                @foreach ($fields as $type => $defs)
                    <template x-if="current && current.type === '{{ $type }}'">
                        <div class="space-y-4">
                            @foreach ($defs as $def)
                                @php [$key, $text, $kind] = $def; $ph = $def[3] ?? ''; @endphp
                                <div>
                                    <label class="{{ $label }}">{{ $text }}</label>
                                    @if ($kind === 'input')
                                        <input type="text" x-model="current.{{ $key }}" placeholder="{{ $ph }}" class="{{ $input }}">
                                    @elseif ($kind === 'imageurl')
                                        <input type="text" x-model="current.{{ $key }}" placeholder="{{ $ph }}" class="{{ $input }}">
                                        <button type="button" @click="openPicker()" class="mt-2 w-full py-2 rounded-lg border border-border text-xs font-bold text-ink hover:border-accent hover:text-accent transition">Choose from Content Studio</button>
                                    @elseif ($kind === 'textarea')
                                        <textarea x-model="current.{{ $key }}" rows="5" class="{{ $input }}"></textarea>
                                    @elseif ($kind === 'number')
                                        <input type="number" min="4" max="120" x-model="current.{{ $key }}" class="{{ $input }}">
                                    @elseif ($kind === 'color')
                                        <div class="flex items-center gap-2">
                                            <input type="color" x-model="current.{{ $key }}" class="h-9 w-12 p-1 rounded-lg bg-paper border border-border cursor-pointer">
                                            <input type="text" x-model="current.{{ $key }}" maxlength="7" class="{{ $input }} font-mono">
                                        </div>
                                    @elseif ($kind === 'align')
                                        <select x-model="current.{{ $key }}" class="{{ $input }}">
                                            <option value="left">Left</option>
                                            <option value="center">Center</option>
                                            <option value="right">Right</option>
                                        </select>
                                    @elseif ($kind === 'size')
                                        <select x-model="current.{{ $key }}" class="{{ $input }}">
                                            <option value="small">Small (13px)</option>
                                            <option value="medium">Medium (15px)</option>
                                            <option value="large">Large (18px)</option>
                                        </select>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </template>
                @endforeach

                <template x-if="current && current.type === 'divider'">
                    <p class="text-sm text-muted">A plain horizontal line. Nothing to set.</p>
                </template>

                <div x-show="current" class="mt-6 pt-4 border-t border-border flex gap-2">
                    <button type="button" @click="move(selected, -1)" :disabled="selected === 0" class="flex-1 py-2 rounded-lg border border-border text-xs font-bold text-ink hover:border-accent disabled:opacity-40">&uarr; Up</button>
                    <button type="button" @click="move(selected, 1)" :disabled="selected === blocks.length - 1" class="flex-1 py-2 rounded-lg border border-border text-xs font-bold text-ink hover:border-accent disabled:opacity-40">&darr; Down</button>
                    <button type="button" @click="remove(selected)" class="flex-1 py-2 rounded-lg border border-danger/40 text-xs font-bold text-danger hover:bg-danger-tint">Delete</button>
                </div>
            </div>
        </aside>
    </div>

    <!-- Send test modal -->
    <div x-show="testOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="testOpen = false">
        <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-soft overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                <h3 class="font-bold text-ink">Send a test email</h3>
                <button type="button" @click="testOpen = false" class="text-muted hover:text-ink text-xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-sm text-muted">Check formatting, subject line and merge tags before sending to your audience. Test emails can go to members of your team only, and carry no tracking or unsubscribe link.</p>

                <div>
                    <label class="{{ $label }}">Destination email</label>
                    <input type="email" x-model="testEmail" class="{{ $input }}">
                </div>

                <div>
                    <div class="{{ $label }}">Merge tag preview</div>
                    <div class="bg-paper-tint border border-border rounded-lg p-3 font-mono text-xs text-ink space-y-1">
                        <div>@{{first_name}} &rarr; <span x-text="userFirst || '(empty)'"></span></div>
                        <div>@{{last_name}} &rarr; <span x-text="userLast || '(empty)'"></span></div>
                        <div>@{{email}} &rarr; <span x-text="testEmail"></span></div>
                    </div>
                </div>

                <div x-show="testErr" x-cloak class="p-3 rounded-lg bg-danger-tint border border-danger/30 text-sm text-danger" x-text="testErr"></div>
                <div x-show="testMsg" x-cloak class="p-3 rounded-lg bg-success-tint border border-success/30 text-sm text-success" x-text="testMsg"></div>
            </div>
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-border bg-paper-tint">
                <button type="button" @click="testOpen = false" class="text-sm font-semibold text-muted hover:text-ink">Dismiss</button>
                <button type="button" @click="sendTest()" :disabled="testBusy || !testEmail" class="px-4 py-2 rounded-lg bg-accent text-white text-sm font-bold hover:opacity-90 disabled:opacity-50">
                    <span x-text="testBusy ? 'Sending...' : 'Send Test Now'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Image picker (Content Studio) -->
    <div x-show="pickerOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="pickerOpen = false">
        <div class="w-full max-w-2xl max-h-[85vh] bg-card border border-border rounded-2xl shadow-soft flex flex-col overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-border shrink-0">
                <h3 class="font-bold text-ink">Choose an image</h3>
                <div class="flex items-center gap-3">
                    <label class="px-3 py-1.5 rounded-lg bg-accent text-white text-xs font-bold cursor-pointer hover:opacity-90">
                        <span x-text="pickerBusy ? 'Uploading...' : 'Upload new'"></span>
                        <input type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="hidden" @change="uploadAsset($event)">
                    </label>
                    <button type="button" @click="pickerOpen = false" class="text-muted hover:text-ink text-xl leading-none">&times;</button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                <div x-show="pickerErr" x-cloak class="mb-4 p-3 rounded-lg bg-danger-tint border border-danger/30 text-sm text-danger" x-text="pickerErr"></div>
                <p x-show="!pickerBusy && assets.length === 0 && !pickerErr" x-cloak class="py-10 text-center text-sm text-muted">No images yet. Use "Upload new" to add your first one.</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <template x-for="asset in assets" :key="asset.id">
                        <button type="button" @click="pickAsset(asset)" class="text-left rounded-lg border border-border bg-paper hover:border-accent overflow-hidden transition">
                            <div class="h-28 bg-paper-tint flex items-center justify-center overflow-hidden"><img :src="asset.url" :alt="asset.name" class="max-h-full max-w-full object-contain"></div>
                            <div class="px-3 py-2 text-xs font-semibold text-ink truncate" x-text="asset.name"></div>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview modal: the server-compiled email in a sandboxed iframe -->
    <div x-show="previewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60" @click.self="previewOpen = false">
        <div class="w-full max-w-4xl h-[88vh] bg-card border border-border rounded-2xl shadow-soft flex flex-col overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-border shrink-0">
                <div class="min-w-0">
                    <h3 class="font-bold text-ink">Preview</h3>
                    <div class="text-xs text-muted truncate">Subject: <span x-text="previewSubject || '(no subject)'"></span> &middot; sample contact John Doe</div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="inline-flex rounded-lg border border-border overflow-hidden text-xs font-bold">
                        <button type="button" @click="previewDevice = 'desktop'" :class="previewDevice === 'desktop' ? 'bg-paper-tint text-ink' : 'bg-card text-muted'" class="px-3 py-1.5">Desktop</button>
                        <button type="button" @click="previewDevice = 'mobile'" :class="previewDevice === 'mobile' ? 'bg-paper-tint text-ink' : 'bg-card text-muted'" class="px-3 py-1.5">Mobile</button>
                    </div>
                    <button type="button" @click="previewOpen = false" class="text-muted hover:text-ink text-xl leading-none">&times;</button>
                </div>
            </div>
            <div class="flex-1 min-h-0 bg-paper-tint overflow-auto p-4 flex justify-center">
                <div x-show="previewBusy" class="self-center text-sm text-muted">Building preview...</div>
                <div x-show="previewErr" x-cloak class="self-center text-sm text-danger" x-text="previewErr"></div>
                <iframe x-show="!previewBusy && !previewErr" sandbox :srcdoc="previewHtml" title="Email preview"
                        class="bg-white h-full rounded-lg border border-border transition-all"
                        :style="{ width: previewDevice === 'mobile' ? '375px' : '100%' }"></iframe>
            </div>
        </div>
    </div>
</form>

@verbatim
<script>
    document.addEventListener('alpine:init', function () {
        Alpine.data('automailEditor', function (cfg) {
            var defaults = {
                header: { title: 'Your headline here', subtitle: 'A short supporting line', align: 'center' },
                text: { content: 'Hello {{first_name}},\n\nWrite your message here.', font_size: 'medium', align: 'left' },
                image: { url: '', alt: '', link: '' },
                button: { label: 'Learn more', url: 'https://', color: '#B85D33', align: 'center' },
                divider: {},
                spacer: { height: '24' },
                columns: { left: 'Left column', right: 'Right column' },
                social: { twitter: '', facebook: '', instagram: '', linkedin: '' },
                footer: { text: 'You are receiving this because you subscribed.', social_text: '' }
            };

            return {
                mode: cfg.mode,
                tab: 'blocks',
                device: 'desktop',
                blocks: cfg.blocks,
                selected: null,
                name: cfg.name,
                subject: cfg.subject,
                previewText: cfg.previewText,
                htmlBody: cfg.htmlBody,
                senderLabel: cfg.senderLabel,
                userFirst: cfg.userFirst,
                userLast: cfg.userLast,
                dirty: false,
                submitting: false,
                lastField: null,

                testOpen: false, testEmail: cfg.userEmail, testBusy: false, testMsg: '', testErr: '',
                pickerOpen: false, pickerBusy: false, pickerErr: '', assets: [],
                previewOpen: false, previewBusy: false, previewHtml: '', previewSubject: '', previewErr: '', previewDevice: 'desktop',

                init: function () {
                    var self = this;
                    this.$watch(function () {
                        return JSON.stringify([self.blocks, self.name, self.subject, self.previewText, self.htmlBody, self.mode]);
                    }, function () { self.dirty = true; });

                    window.addEventListener('beforeunload', function (e) {
                        if (self.dirty && !self.submitting) { e.preventDefault(); e.returnValue = ''; }
                    });
                },

                get current() {
                    return this.selected !== null && this.blocks[this.selected] ? this.blocks[this.selected] : null;
                },

                textPx: function (block) {
                    return { small: 13, medium: 15, large: 18 }[block.font_size] || 15;
                },

                select: function (i) { this.selected = i; },

                addBlock: function (type) {
                    var block = Object.assign({ type: type }, JSON.parse(JSON.stringify(defaults[type] || {})));
                    this.blocks.push(block);
                    this.selected = this.blocks.length - 1;
                    var self = this;
                    this.$nextTick(function () {
                        var el = document.querySelector('[data-selected="true"]');
                        if (el) { el.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
                    });
                },

                remove: function (i) {
                    this.blocks.splice(i, 1);
                    this.selected = null;
                },

                move: function (i, dir) {
                    var j = i + dir;
                    if (j < 0 || j >= this.blocks.length) { return; }
                    var moved = this.blocks.splice(i, 1)[0];
                    this.blocks.splice(j, 0, moved);
                    this.selected = j;
                },

                insertTag: function (tag) {
                    var el = this.lastField;
                    if (!el || !document.body.contains(el)) {
                        this.tab = 'envelope';
                        el = document.getElementById('subject');
                    }
                    if (!el) { return; }
                    var start = el.selectionStart == null ? el.value.length : el.selectionStart;
                    var end = el.selectionEnd == null ? start : el.selectionEnd;
                    el.value = el.value.slice(0, start) + tag + el.value.slice(end);
                    el.focus();
                    el.setSelectionRange(start + tag.length, start + tag.length);
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                },

                payload: function () {
                    return {
                        subject: this.subject,
                        preview_text: this.previewText,
                        blocks_json: this.mode === 'builder' ? JSON.stringify(this.blocks) : null,
                        body: this.mode === 'html' ? this.htmlBody : null
                    };
                },

                post: async function (url, data) {
                    var res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(data)
                    });
                    var json = await res.json().catch(function () { return {}; });
                    if (!res.ok) {
                        var msg = json.error || (json.errors ? Object.values(json.errors).flat().join(' ') : json.message) || 'Something went wrong.';
                        throw new Error(msg);
                    }
                    return json;
                },

                openPreview: async function () {
                    this.previewOpen = true;
                    this.previewBusy = true;
                    this.previewErr = '';
                    try {
                        var json = await this.post(cfg.previewUrl, this.payload());
                        this.previewHtml = json.html;
                        this.previewSubject = json.subject;
                    } catch (e) {
                        this.previewErr = e.message;
                    }
                    this.previewBusy = false;
                },

                openPicker: async function () {
                    this.pickerOpen = true;
                    this.pickerErr = '';
                    this.pickerBusy = true;
                    try {
                        var res = await fetch(cfg.assetsListUrl, { headers: { 'Accept': 'application/json' } });
                        var json = await res.json();
                        if (!res.ok) { throw new Error(json.message || 'Could not load your images.'); }
                        this.assets = json.assets;
                    } catch (e) {
                        this.pickerErr = e.message;
                    }
                    this.pickerBusy = false;
                },

                uploadAsset: async function (event) {
                    var file = event.target.files[0];
                    event.target.value = '';
                    if (!file) { return; }
                    this.pickerBusy = true;
                    this.pickerErr = '';
                    try {
                        var body = new FormData();
                        body.append('file', file);
                        var res = await fetch(cfg.assetsStoreUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: body
                        });
                        var json = await res.json().catch(function () { return {}; });
                        if (!res.ok) {
                            throw new Error(json.error || (json.errors ? Object.values(json.errors).flat().join(' ') : json.message) || 'Upload failed.');
                        }
                        this.assets.unshift(json.asset);
                    } catch (e) {
                        this.pickerErr = e.message;
                    }
                    this.pickerBusy = false;
                },

                pickAsset: function (asset) {
                    if (!this.current) { return; }
                    this.current.url = asset.url;
                    if (!this.current.alt) { this.current.alt = asset.name.replace(/\.[^.]+$/, ''); }
                    this.pickerOpen = false;
                },

                sendTest: async function () {
                    this.testBusy = true;
                    this.testMsg = '';
                    this.testErr = '';
                    try {
                        var data = this.payload();
                        data.email = this.testEmail;
                        var json = await this.post(cfg.sendTestUrl, data);
                        this.testMsg = json.message;
                    } catch (e) {
                        this.testErr = e.message;
                    }
                    this.testBusy = false;
                }
            };
        });
    });
</script>
@endverbatim
