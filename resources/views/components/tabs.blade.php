@props([
    'tabs' => [],
    'active' => null,
])

<nav {{ $attributes->merge(['class' => 'flex gap-6 border-b border-gray-200']) }} aria-label="Tabs">
    @foreach ($tabs as $key => $tab)
        @php
            $label = is_array($tab) ? ($tab['label'] ?? $key) : $tab;
            $href = is_array($tab) ? ($tab['href'] ?? '#') : '#';
            $isActive = (string) $active === (string) $key;
        @endphp
        <a href="{{ $href }}" class="{{ $isActive ? 'silat-tab silat-tab-active' : 'silat-tab' }}" @if($isActive) aria-current="page" @endif>
            {{ $label }}
        </a>
    @endforeach
</nav>
