@props([
    'name',
    'style' => 'solid',
])

@php
    $prefix = [
        'solid' => 'fa-solid',
        'regular' => 'fa-regular',
        'brands' => 'fa-brands',
    ][$style] ?? 'fa-solid';
@endphp

<i {{ $attributes->merge(['class' => $prefix.' '.$name]) }} aria-hidden="true"></i>
