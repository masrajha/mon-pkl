@props([
    'variant' => 'info',
])

@php
    $classes = [
        'success' => 'silat-alert-success',
        'warning' => 'silat-alert-warning',
        'danger' => 'silat-alert-danger',
        'error' => 'silat-alert-danger',
        'info' => 'silat-alert-info',
    ][$variant] ?? 'silat-alert-info';
@endphp

<div {{ $attributes->merge(['class' => 'silat-alert '.$classes]) }}>
    {{ $slot }}
</div>
