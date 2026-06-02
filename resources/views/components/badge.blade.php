@props([
    'variant' => 'info',
])

@php
    $classes = [
        'success' => 'silat-badge-success',
        'warning' => 'silat-badge-warning',
        'danger' => 'silat-badge-danger',
        'error' => 'silat-badge-danger',
        'info' => '',
        'neutral' => 'border-gray-200 bg-gray-50 text-gray-700',
    ][$variant] ?? '';
@endphp

<span {{ $attributes->merge(['class' => trim('silat-badge '.$classes)]) }}>
    {{ $slot }}
</span>
