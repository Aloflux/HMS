@props(['value'])

@php
    $code = $value instanceof \BackedEnum ? $value->value : (string) $value;

    $labels = [
        'checked_in' => 'Checked in',
        'no_show' => 'No-show',
        'in_progress' => 'In progress',
        'sent' => 'Ordered',
        'svd' => 'Normal delivery',
        'live' => 'Live birth',
    ];

    $tone = match ($code) {
        'cancelled', 'rejected', 'missed', 'refused', 'no_show', 'died', 'stillbirth' => 'bg-red-50 text-red-700',
        'pending', 'waiting', 'due', 'booked', 'ordered', 'submitted', 'sent', 'draft', 'open', 'partial', 'cleaning', 'resulted', 'in_progress' => 'bg-amber-50 text-amber-800',
        'serving', 'admitted', 'occupied', 'consulting', 'triaged', 'registered', 'checked_in' => 'bg-sky-50 text-sky-800',
        default => 'bg-brand-50 text-brand-800',
    };
@endphp

<span {{ $attributes->class(['inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide', $tone]) }}>{{ $labels[$code] ?? ucfirst(str_replace('_', ' ', $code)) }}</span>
