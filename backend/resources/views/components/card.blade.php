@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'silat-card']) }}>
    @if ($title || $description || isset($header))
        <div class="silat-section-header">
            <div>
                @if ($title)
                    <h3 class="silat-section-title">{{ $title }}</h3>
                @endif
                @if ($description)
                    <p class="silat-section-description">{{ $description }}</p>
                @endif
            </div>
            @isset($header)
                {{ $header }}
            @endisset
        </div>
    @endif
    <div class="p-5">
        {{ $slot }}
    </div>
</section>
