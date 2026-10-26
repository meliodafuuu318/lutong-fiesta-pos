@props(['status'])

@php
    $labels = [
        'REFUNDED' => 'Returned / Refunded',
    ];
    $styles = [
        'COMPLETED' => 'bg-teal-100 text-teal-900',
        'PAID' => 'bg-teal-100 text-teal-900',
        'DELIVERED' => 'bg-teal-100 text-teal-900',
        'PENDING' => 'bg-amber-400/30 text-ink',
        'CANCELLED' => 'bg-danger/10 text-danger',
        'REFUNDED' => 'bg-ink/10 text-ink',
    ];
@endphp

<span {{ $attributes->class([
    'inline-block whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold',
    $styles[$status] ?? 'bg-ink/10 text-ink',
]) }}>{{ $labels[$status] ?? \Illuminate\Support\Str::title(strtolower($status)) }}</span>
