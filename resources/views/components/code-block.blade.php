@props(['copyable' => false])

<div class="relative group">
    <pre class="font-mono text-sm bg-stone-900 text-stone-100 rounded-xl px-4 py-3 overflow-x-auto whitespace-pre-wrap break-all leading-relaxed">{{ $slot }}</pre>
    @if($copyable)
    <button
        x-data
        @click="
            navigator.clipboard.writeText($el.previousElementSibling.textContent.trim());
            $el.textContent = 'Copied!';
            setTimeout(() => $el.textContent = 'Copy', 1500);
        "
        class="absolute top-2 right-2 px-2 py-1 text-xs font-medium bg-stone-700 hover:bg-stone-600 text-stone-300 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
        Copy
    </button>
    @endif
</div>
