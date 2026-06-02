@props([
    'column',
    'label',
    'align' => 'left',
])

@php
    $active = request('sort_by') === $column;
    $currentDirection = request('sort_dir') === 'desc' ? 'desc' : 'asc';
    $nextDirection = $active && $currentDirection === 'asc' ? 'desc' : 'asc';
    $icon = $active ? ($currentDirection === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down') : 'fa-sort';
    $query = array_merge(request()->except('page'), ['sort_by' => $column, 'sort_dir' => $nextDirection]);
@endphp

<a href="{{ url()->current().'?'.http_build_query($query) }}" class="silat-table-sort {{ $align === 'right' ? 'justify-end' : '' }}">
    <span>{{ $label }}</span>
    <x-icon :name="$icon" class="text-[10px]" />
</a>
