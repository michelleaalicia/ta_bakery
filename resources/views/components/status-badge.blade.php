@props(['status', 'type' => 'production'])

@php
    $styles = [
        'draft' => ['Draft', 'secondary'],
        'confirmed' => ['Dikonfirmasi', 'info'],
        'delivered' => ['Diserahkan', 'success'],
        'cancelled' => ['Dibatalkan', 'danger'],
        'planned' => ['Direncanakan', 'secondary'],
        'in_progress' => ['Berlangsung', 'warning'],
        'completed' => ['Selesai', 'success'],
    ];

    [$label, $color] = $styles[$status] ?? [ucfirst(str_replace('_', ' ', $status)), 'secondary'];
@endphp

<span class="badge bg-{{ $color }} {{ $color === 'warning' ? 'text-dark' : '' }}">{{ $label }}</span>
