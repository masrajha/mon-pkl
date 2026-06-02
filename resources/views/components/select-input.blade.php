@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'silat-field']) }}>
    {{ $slot }}
</select>
