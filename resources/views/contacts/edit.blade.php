@php
    $input = 'mt-1 block w-full text-sm rounded-lg bg-paper border-border text-ink placeholder:text-muted focus:border-accent focus:ring-accent';
    $label = 'block text-xs font-bold uppercase tracking-wider text-muted';
@endphp

<x-app-layout>
    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div>
                <a href="{{ route('contacts.index') }}" class="text-sm font-semibold text-muted hover:text-ink">&larr; Audience</a>
                <h1 class="text-3xl font-extrabold tracking-tight text-ink mt-1">Edit contact</h1>
                <p class="text-sm font-mono text-muted mt-1">{{ $contact->email }}</p>
            </div>

            @if ($errors->any())
                <div class="p-4 bg-danger-tint border border-danger/30 text-danger rounded-lg text-sm">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <div class="bg-card border border-border rounded-xl shadow-soft p-6">
                <form method="POST" action="{{ route('contacts.update', $contact->id) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="{{ $label }}">First name</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $contact->first_name) }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="last_name" class="{{ $label }}">Last name</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $contact->last_name) }}" class="{{ $input }}">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="{{ $label }}">Email address *</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $contact->email) }}" required class="{{ $input }}">
                    </div>

                    <div>
                        <label for="status" class="{{ $label }}">Subscription status</label>
                        <select name="status" id="status" class="{{ $input }}">
                            @foreach (['subscribed' => 'Subscribed', 'unsubscribed' => 'Unsubscribed', 'bounced' => 'Bounced'] as $value => $text)
                                <option value="{{ $value }}" @selected(old('status', $contact->status) === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="p-4 bg-paper-tint border border-border rounded-lg">
                        <label for="tags" class="{{ $label }}">Tags</label>
                        <p class="text-xs text-muted my-1.5">Separate multiple tags with a comma (e.g. <code>VIP, customer, newsletter</code>)</p>
                        <input type="text" name="tags" id="tags" value="{{ old('tags', $contact->tags) }}" placeholder="e.g. VIP, customer" class="block w-full text-sm rounded-lg bg-paper border-border text-ink placeholder:text-muted focus:border-accent focus:ring-accent">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-accent text-white text-sm font-bold hover:opacity-90 transition">Save changes</button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-border">
                    <h3 class="text-sm font-bold text-danger">Delete contact</h3>
                    <p class="text-xs text-muted mt-1 mb-3">Permanently removes this contact. Only owners and admins can do this.</p>
                    <form method="POST" action="{{ route('contacts.destroy', $contact->id) }}" onsubmit="return confirm('Permanently delete this contact?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 rounded-lg border border-danger/40 text-danger text-sm font-bold hover:bg-danger-tint transition">Delete contact</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
