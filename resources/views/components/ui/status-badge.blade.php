@props([
    'variant' => 'neutral',
    'label' => '',
])

@php
    $classes = match ($variant) {
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'warning' => 'bg-[#FFD166]/40 text-amber-900 ring-amber-300/60',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-200/70',
        'info' => 'bg-[#8DCCF1]/25 text-[#0101B7] ring-[#8DCCF1]/50',
        'primary' => 'bg-[#0101B7] text-white ring-[#0101B7]',
        'gold' => 'bg-[#FFD166] text-slate-900 ring-[#FFD166]',
        default => 'bg-slate-100 text-slate-600 ring-slate-200/70',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset '.$classes]) }}>
    {{ $label !== '' ? $label : $slot }}
</span>
