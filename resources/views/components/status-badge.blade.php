@props(['status'])

@php
    $styles = [
        'pending' => 'bg-slate-100 text-slate-700',
        'processing' => 'bg-sky-100 text-sky-800',
        'background_removing' => 'bg-violet-100 text-violet-800',
        'vectorizing' => 'bg-indigo-100 text-indigo-800',
        'optimizing' => 'bg-teal-100 text-teal-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'failed' => 'bg-rose-100 text-rose-800',
        'cancelled' => 'bg-slate-200 text-slate-600',
    ];
    $bg = $styles[$status->value] ?? 'bg-slate-100 text-slate-700';
    $icons = [
        'completed' => 'check',
        'failed' => 'x',
        'pending' => 'clock',
        'processing' => 'clock',
        'vectorizing' => 'converter',
        'optimizing' => 'sparkles',
        'background_removing' => 'sparkles',
        'cancelled' => 'x',
    ];
@endphp

<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $bg }}">
    @if (isset($icons[$status->value]))
        <x-icon name="{{ $icons[$status->value] }}" class="h-3 w-3" />
    @endif
    {{ $status->label() }}
</span>