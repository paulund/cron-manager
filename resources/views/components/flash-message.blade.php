@if(session('success') || session('error'))
@php $isSuccess = (bool) session('success'); @endphp
<div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 6000)"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="mb-6 flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium
        {{ $isSuccess ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
    <span>{{ $isSuccess ? '✓' : '✕' }}</span>
    <span class="flex-1">{{ session('success') ?? session('error') }}</span>
    <button @click="show = false" class="opacity-50 hover:opacity-100 transition-opacity leading-none text-lg">&times;</button>
</div>
@endif
