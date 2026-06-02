@props(['disabled' => false])

<textarea @disabled($disabled) {{ $attributes->merge(['class' => 'silat-field']) }}>{{ $slot }}</textarea>
