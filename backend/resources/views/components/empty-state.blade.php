@props([
    'title' => 'Belum ada data',
    'description' => null,
    'icon' => 'fa-inbox',
])

<div {{ $attributes->merge(['class' => 'silat-empty-state']) }}>
    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-blue-50 text-blue-700">
        <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
    </div>
    <h3 class="mt-3 text-sm font-semibold text-gray-900">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">{{ $description }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
