@props([
    'device' => null,
])

@php
    $device = is_array($device) ? $device : null;
@endphp

@if ($device)
    <span
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-semibold text-gray-600']) }}
        title="{{ $device['user_agent'] ?? $device['label'] ?? 'Perangkat' }}"
    >
        <x-icon :name="$device['icon'] ?? 'fa-circle-question'" :style="$device['icon_style'] ?? 'solid'" class="text-[10px]" />
        <span>{{ $device['label'] ?? 'Perangkat' }}</span>
    </span>
@endif
