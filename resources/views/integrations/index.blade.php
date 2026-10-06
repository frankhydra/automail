@php
    $input = 'w-full text-sm rounded-lg bg-paper border-border text-ink placeholder:text-muted focus:border-accent focus:ring-accent';
    $label = 'block text-xs font-bold uppercase tracking-wider text-muted mb-1.5';
    $webhookLive = $webhook && $webhook->enabled;
    $utmLive = $utm && $utm->enabled;
    $utmSettings = $utm ? (array) $utm->settings : [];
    $soon = [
        ['Shopify', 'Sync customers, order history and product catalogues.'],
        ['WooCommerce', 'Turn abandoned carts into email flows.'],
        ['Stripe', 'Win back customers after failed subscription payments.'],
        ['Salesforce', 'Sync CRM leads and closed-revenue attribution.'],
    ];
@endphp

<x-app-layout>
    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-ink">Integrations &amp; Connected Apps</h1>
                <p class="text-sm text-muted mt-1">Unify your marketing stack by connecting AutoMail to the tools you already use.</p>
            </div>

            <x-flash-messages />

            @if ($errors->any())
                <div class="p-4 bg-danger-tint border border-danger/30 text-danger rounded-lg text-sm">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <!-- Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="bg-card border border-border rounded-xl shadow-soft p-6 flex flex-col">
                    <div class="font-extrabold text-ink">Webhooks &amp; Zapier</div>
                    <p class="text-sm text-muted mt-1 flex-1">Add contacts from a website form, Zapier, Make or any tool that can send a web request.</p>
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-border">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $webhookLive ? 'bg-success-tint text-success' : 'bg-paper-tint text-muted' }}">{{ $webhookLive ? 'Connected' : ($webhook ? 'Switched off' : 'Not configured') }}</span>
                        <a href="#webhook" class="text-sm font-bold text-accent hover:underline">{{ $webhook ? 'Configure' : 'Connect' }}</a>
                    </div>
                </div>

                <div class="bg-card border border-border rounded-xl shadow-soft p-6 flex flex-col">
                    <div class="font-extrabold text-ink">Google Analytics tags</div>
                    <p class="text-sm text-muted mt-1 flex-1">Add UTM parameters to the links in your emails, so visits show up under their campaign in Google Analytics.</p>
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-border">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $utmLive ? 'bg-success-tint text-success' : 'bg-paper-tint text-muted' }}">{{ $utmLive ? 'Connected' : 'Not configured' }}</span>
                        <a href="#utm" class="text-sm font-bold text-accent hover:underline">{{ $utm ? 'Configure' : 'Connect' }}</a>
                    </div>
                </div>

                @foreach ($soon as [$name, $text])
                    <div class="bg-card border border-border rounded-xl p-6 flex flex-col opacity-80">
                        <div class="font-extrabold text-ink">{{ $name }}</div>
                        <p class="text-sm text-muted mt-1 flex-1">{{ $text }}</p>
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-border">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-paper-tint text-muted">Coming soon</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Webhook panel -->
            <section id="webhook" class="bg-card border border-border rounded-xl shadow-soft p-6 space-y-5 scroll-mt-24">
                <div>
                    <h2 class="text-xl font-extrabold text-ink">Webhooks &amp; Zapier: add contacts from other tools</h2>
                    <p class="text-sm text-muted mt-1">Anything that sends a web request to your private URL adds a subscriber. Contacts added this way also start your automations.</p>
                </div>

                @if ($newUrl)
                    <div x-data="{ copied: false }" class="p-4 bg-success-tint border border-success/30 rounded-lg">
                        <div class="text-sm font-bold text-ink mb-2">Your intake URL (shown once, copy it now)</div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" readonly value="{{ $newUrl }}" onclick="this.select()" class="flex-1 font-mono text-xs rounded-lg bg-card border-border text-ink">
                            <button type="button" @click="navigator.clipboard.writeText(@js($newUrl)); copied = true" class="px-4 py-2 rounded-lg bg-sidebar text-white text-sm font-bold" x-text="copied ? 'Copied!' : 'Copy'"></button>
                        </div>
                    </div>
                @endif

                @if (!$webhook)
                    <p class="text-sm text-ink">You have not created an intake URL yet.</p>
                @else
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-muted">Status</dt><dd class="font-semibold text-ink">{{ $webhook->enabled ? 'On' : 'Off' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-muted">Requests received</dt><dd class="font-semibold text-ink">{{ number_format($webhook->uses) }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-muted">Last used</dt><dd class="font-semibold text-ink">{{ $webhook->last_used_at ? $webhook->last_used_at->diffForHumans() : 'Never' }}</dd></div>
                    </dl>
                @endif

                @if ($canManage)
                    @if ($webhook)
                        <form method="POST" action="{{ route('integrations.webhook.update') }}" class="space-y-4">
                            @csrf @method('PUT')
                            <label class="flex items-center gap-2 text-sm font-semibold text-ink">
                                <input type="checkbox" name="enabled" value="1" @checked($webhook->enabled) class="rounded border-border text-accent focus:ring-accent"> Accept new contacts through this URL
                            </label>
                            <div>
                                <label class="{{ $label }}" for="default_tags">Tags to add to every contact received</label>
                                <input id="default_tags" type="text" name="default_tags" value="{{ old('default_tags', $webhook->setting('default_tags', '')) }}" placeholder="e.g. website, newsletter" class="{{ $input }}">
                            </div>
                            <button type="submit" class="px-4 py-2 rounded-lg bg-accent text-white text-sm font-bold hover:opacity-90">Save</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('integrations.webhook.generate') }}" @if ($webhook) onsubmit="return confirm('Create a new URL? The current one stops working immediately, so update any tool that uses it.');" @endif>
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg border border-border bg-card text-sm font-semibold text-ink hover:border-accent">{{ $webhook ? 'Create a new URL (revokes the old one)' : 'Create intake URL' }}</button>
                    </form>
                @else
                    <p class="text-xs text-muted">Only owners and admins can change this.</p>
                @endif

                <div class="pt-4 border-t border-border space-y-3">
                    <div class="{{ $label }}">How to use it</div>
                    <p class="text-sm text-ink">Send a POST request to your URL (in Zapier use the <em>Webhooks</em> action; in Make use <em>HTTP</em>). Only <code>email</code> is required:</p>
<pre class="text-xs font-mono bg-paper-tint border border-border rounded-lg p-4 overflow-x-auto text-ink">curl -X POST "YOUR_INTAKE_URL" \
  -H "Content-Type: application/json" \
  -d '{"email":"jane@example.com","first_name":"Jane","last_name":"Doe","tags":["website","vip"]}'</pre>
                    <ul class="text-xs text-muted list-disc pl-5 space-y-1">
                        <li>A new contact answers <code>201</code> with <code>"status":"created"</code>. An address you already have answers <code>"exists"</code>, and one that unsubscribed or bounced is <code>"skipped"</code>, so retries are safe.</li>
                        <li>Only add people who agreed to receive your emails. Your plan's contact limit still applies.</li>
                        <li>Treat the URL like a password. If it leaks, create a new one.</li>
                    </ul>
                </div>
            </section>

            <!-- UTM panel -->
            <section id="utm" class="bg-card border border-border rounded-xl shadow-soft p-6 space-y-5 scroll-mt-24"
                     x-data="{ source: @js(old('source', $utmSettings['source'] ?? 'automail')), medium: @js(old('medium', $utmSettings['medium'] ?? 'email')) }">
                <div>
                    <h2 class="text-xl font-extrabold text-ink">Google Analytics tags (UTM)</h2>
                    <p class="text-sm text-muted mt-1">Adds <code>utm_source</code>, <code>utm_medium</code> and <code>utm_campaign</code> to links in every email you send, including automations. Tags you add to a link yourself are never overwritten.</p>
                </div>

                @if ($canManage)
                    <form method="POST" action="{{ route('integrations.utm.update') }}" class="space-y-4">
                        @csrf @method('PUT')
                        <label class="flex items-center gap-2 text-sm font-semibold text-ink">
                            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $utm?->enabled)) class="rounded border-border text-accent focus:ring-accent"> Tag links in my emails
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><label class="{{ $label }}" for="source">Source</label><input id="source" type="text" name="source" x-model="source" required class="{{ $input }}"></div>
                            <div><label class="{{ $label }}" for="medium">Medium</label><input id="medium" type="text" name="medium" x-model="medium" required class="{{ $input }}"></div>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="domains">Only tag links to these websites (optional)</label>
                            <input id="domains" type="text" name="domains" value="{{ old('domains', implode(', ', $utmSettings['domains'] ?? [])) }}" placeholder="e.g. mystore.com, blog.mystore.com" class="{{ $input }}">
                            <p class="text-[11px] text-muted mt-1">Leave empty to tag every link. Subdomains are included.</p>
                        </div>
                        <div class="text-xs text-muted">Example: <code class="text-ink break-all">https://mystore.com/sale?utm_source=<span x-text="source"></span>&amp;utm_medium=<span x-text="medium"></span>&amp;utm_campaign=your-campaign-name</code></div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-accent text-white text-sm font-bold hover:opacity-90">Save</button>
                    </form>
                @else
                    <p class="text-sm text-ink">{{ $utmLive ? 'Tagging is on: source "'.($utmSettings['source'] ?? '').'", medium "'.($utmSettings['medium'] ?? '').'".' : 'Tagging is off.' }}</p>
                    <p class="text-xs text-muted">Only owners and admins can change this.</p>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
