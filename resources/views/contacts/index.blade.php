@php
    $input = 'w-full text-sm rounded-lg bg-paper border-border text-ink placeholder:text-muted focus:border-accent focus:ring-accent';
    $label = 'block text-xs font-bold uppercase tracking-wider text-muted mb-1.5';
    $keep = fn (array $extra) => array_filter(array_merge(['search' => $search, 'tag' => $activeTag, 'status' => $activeStatus], $extra), fn ($v) => $v !== null && $v !== '');
    $statusStyles = [
        'subscribed' => 'bg-success-tint text-success',
        'unsubscribed' => 'bg-warning-tint text-warning',
        'bounced' => 'bg-danger-tint text-danger',
    ];
@endphp

<x-app-layout>
    <div class="py-8" x-data="{ addOpen: {{ $errors->has('email') || $errors->has('first_name') || $errors->has('last_name') || $errors->has('tags') ? 'true' : 'false' }} }" @keydown.escape.window="addOpen = false">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Audience Directory</h1>
                    <p class="text-sm text-muted mt-1">Manage contacts, tags and opt-in status. {{ number_format($totalContacts) }} total.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($canManage)
                        <a href="{{ route('contacts.export', $keep([])) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-border bg-card text-sm font-semibold text-ink hover:border-accent transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            Export CSV
                        </a>
                        <a href="{{ route('contacts.import.show') }}" class="inline-flex items-center px-4 py-2.5 rounded-lg border border-border bg-card text-sm font-semibold text-ink hover:border-accent transition">Import CSV</a>
                        <button type="button" @click="addOpen = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-accent text-white text-sm font-bold shadow-soft hover:opacity-90 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add A Contact
                        </button>
                    @endif
                </div>
            </div>

            <x-flash-messages />

            <!-- Filters -->
            <div class="bg-card border border-border rounded-xl shadow-soft p-4 flex flex-col lg:flex-row lg:items-center gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-muted mr-1">Filter tag:</span>
                    <a href="{{ route('contacts.index', $keep(['tag' => null])) }}"
                       class="px-3 py-1 rounded-full text-xs font-bold border {{ $activeTag === '' ? 'bg-sidebar text-white border-sidebar' : 'bg-paper border-border text-ink hover:border-accent' }}">All</a>
                    @foreach ($tags as $tag)
                        <a href="{{ route('contacts.index', $keep(['tag' => $tag])) }}"
                           class="px-3 py-1 rounded-full text-xs font-bold border capitalize {{ $activeTag === $tag ? 'bg-sidebar text-white border-sidebar' : 'bg-paper border-border text-ink hover:border-accent' }}">{{ $tag }}</a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('contacts.index') }}" class="lg:ml-auto flex flex-wrap items-center gap-2">
                    @if ($activeTag !== '') <input type="hidden" name="tag" value="{{ $activeTag }}"> @endif
                    <select name="status" onchange="this.form.submit()" class="text-sm rounded-lg bg-paper border-border text-ink focus:border-accent focus:ring-accent">
                        <option value="">All statuses</option>
                        @foreach (['subscribed' => 'Subscribed', 'unsubscribed' => 'Unsubscribed', 'bounced' => 'Bounced'] as $value => $text)
                            <option value="{{ $value }}" @selected($activeStatus === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search email, name..." class="w-56 text-sm rounded-lg bg-paper border-border text-ink placeholder:text-muted focus:border-accent focus:ring-accent">
                    <button type="submit" class="px-3 py-2 rounded-lg bg-sidebar text-white text-sm font-semibold hover:bg-black transition">Search</button>
                    @if ($search || $activeTag !== '' || $activeStatus)
                        <a href="{{ route('contacts.index') }}" class="text-sm font-semibold text-muted hover:text-ink">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Table -->
            <div class="bg-card border border-border rounded-xl shadow-soft overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-muted border-b border-border">
                                <th class="px-6 py-3">Contact</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Tags</th>
                                <th class="px-4 py-3">Engagement (opens)</th>
                                <th class="px-4 py-3">Added</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($contacts as $contact)
                                @php
                                    $rate = $contact->sent_count > 0 ? (int) round($contact->opened_count / $contact->sent_count * 100) : null;
                                    $name = trim($contact->first_name.' '.$contact->last_name);
                                @endphp
                                <tr class="hover:bg-paper-tint transition">
                                    <td class="px-6 py-3">
                                        <div class="font-bold text-ink">{{ $name !== '' ? $name : '(no name)' }}</div>
                                        <div class="font-mono text-xs text-muted">{{ $contact->email }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold capitalize {{ $statusStyles[$contact->status] ?? 'bg-paper-tint text-muted' }}">{{ $contact->status }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse ($contact->tagList() as $tag)
                                                <span class="px-2 py-0.5 rounded bg-paper-tint border border-border text-xs font-semibold text-ink capitalize">{{ $tag }}</span>
                                            @empty
                                                <span class="text-xs italic text-muted">No tags</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($rate === null)
                                            <span class="text-xs italic text-muted">No sends yet</span>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <div class="w-20 h-1.5 rounded-full bg-paper-tint overflow-hidden"><div class="h-full rounded-full bg-accent" style="width: {{ $rate }}%"></div></div>
                                                <span class="text-xs font-bold text-ink">{{ $rate }}%</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted whitespace-nowrap">{{ $contact->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('contacts.edit', $contact->id) }}" class="text-xs font-bold text-accent hover:underline">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-10 text-center text-muted">No contacts match your filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-border text-xs text-muted flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <span>Showing {{ $contacts->count() }} of {{ number_format($contacts->total()) }} contact{{ $contacts->total() === 1 ? '' : 's' }}</span>
                    <div>{{ $contacts->links() }}</div>
                </div>
            </div>
        </div>

        <!-- Add contact modal -->
        @if ($canManage)
            <div x-show="addOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="addOpen = false">
                <form method="POST" action="{{ route('contacts.store') }}" class="w-full max-w-md bg-card border border-border rounded-2xl shadow-soft overflow-hidden">
                    @csrf
                    <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                        <h3 class="font-bold text-ink">Add a contact</h3>
                        <button type="button" @click="addOpen = false" class="text-muted hover:text-ink text-xl leading-none">&times;</button>
                    </div>
                    <div class="p-6 space-y-4">
                        @if ($errors->any())
                            <div class="p-3 rounded-lg bg-danger-tint border border-danger/30 text-sm text-danger">
                                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                            </div>
                        @endif
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="{{ $label }}">First name</label><input type="text" name="first_name" value="{{ old('first_name') }}" class="{{ $input }}"></div>
                            <div><label class="{{ $label }}">Last name</label><input type="text" name="last_name" value="{{ old('last_name') }}" class="{{ $input }}"></div>
                        </div>
                        <div><label class="{{ $label }}">Email address *</label><input type="email" name="email" value="{{ old('email') }}" required class="{{ $input }}"></div>
                        <div>
                            <label class="{{ $label }}">Tags</label>
                            <input type="text" name="tags" value="{{ old('tags') }}" placeholder="e.g. VIP, customer" class="{{ $input }}">
                            <p class="text-[11px] text-muted mt-1">Separate tags with commas. Only add people who agreed to receive your emails.</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-border bg-paper-tint">
                        <button type="button" @click="addOpen = false" class="text-sm font-semibold text-muted hover:text-ink">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-accent text-white text-sm font-bold hover:opacity-90">Add Contact</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
