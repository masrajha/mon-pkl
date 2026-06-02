@props([
    'title' => null,
    'description' => null,
    'searchPlaceholder' => 'Cari data...',
    'showSearch' => true,
])

@php
    $query = request()->except(['page']);
@endphp

<form method="GET" {{ $attributes->merge(['class' => 'silat-table-toolbar']) }}>
    @foreach ($query as $key => $value)
        @continue(in_array($key, ['q', 'period_id', 'study_program_id', 'status', 'program_id'], true))
        @if (! is_array($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="min-w-0">
        @if ($title)
            <h3 class="silat-section-title">{{ $title }}</h3>
        @endif
        @if ($description)
            <p class="silat-section-description">{{ $description }}</p>
        @endif
    </div>

    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-start">
        @if ($showSearch)
            <div class="silat-table-search">
                <x-icon name="fa-magnifying-glass" class="silat-table-search-icon" />
                <input name="q" value="{{ request('q') }}" placeholder="{{ $searchPlaceholder }}" class="silat-table-search-input" data-table-search-input>
            </div>
        @endif

        @isset($filters)
            <details class="silat-filter-menu">
                <summary class="silat-btn-secondary select-none">
                    <x-icon name="fa-filter" />
                    Filter
                </summary>
                <div class="silat-filter-panel">
                    {{ $filters }}
                    <div class="mt-4 flex justify-end gap-2">
                        <a href="{{ url()->current() }}" class="silat-btn-secondary px-3 py-2">Reset</a>
                        <button class="silat-btn px-3 py-2">Terapkan</button>
                    </div>
                </div>
            </details>
        @endisset

        <button class="silat-btn" type="submit">
            <x-icon name="fa-magnifying-glass" />
            Cari
        </button>
    </div>
</form>
