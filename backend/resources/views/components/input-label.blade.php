@props(['value'])

<label {{ $attributes->merge(['class' => 'silat-label']) }}>
    {{ $value ?? $slot }}
</label>
