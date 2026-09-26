{{--
    Replaces the repeated "@if(session('status')) ... @endif @if($errors->any()) ...
    @endif" boilerplate that used to be copy-pasted at the top of most pages.
    Usage: <x-flash-messages /> right after the page header.
--}}
@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="mb-4 p-4 bg-success-tint dark:bg-success/20 border border-success/30 text-success rounded-lg shadow-sm flex items-start justify-between gap-4">
        <p class="text-sm font-medium">{{ session('status') }}</p>
        <button type="button" @click="show = false" class="text-success/70 hover:text-success">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 p-4 bg-danger-tint dark:bg-danger/20 border border-danger/30 text-danger rounded-lg shadow-sm">
        <ul class="list-disc list-inside text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
