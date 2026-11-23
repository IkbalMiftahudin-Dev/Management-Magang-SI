@props([
    'value' => 0,
    'label' => '',
])

<div class="flex items-center gap-4 rounded-3xl bg-white p-5 shadow-[0_6px_24px_rgba(1,1,183,0.04)] ring-1 ring-slate-100">
    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#F2F7FF] text-[#0101B7]">
        {{ $icon ?? '' }}
    </div>
    <div class="min-w-0">
        <p class="font-heading text-2xl font-bold leading-tight text-slate-900">{{ $value }}</p>
        <p class="mt-0.5 truncate text-xs font-medium text-slate-500">{{ $label }}</p>
    </div>
</div>
