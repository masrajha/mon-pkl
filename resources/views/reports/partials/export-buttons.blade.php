@props([
    'type',
    'query' => null,
])

@php
    $exportQuery = $query ?? request()->query();
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
    <a class="silat-btn-secondary" href="{{ route('reports.export', array_merge($exportQuery, ['type' => $type, 'format' => 'csv'])) }}">
        <x-icon name="fa-file-csv" /> CSV
    </a>
    <a class="silat-btn-secondary" href="{{ route('reports.export', array_merge($exportQuery, ['type' => $type, 'format' => 'xls'])) }}">
        <x-icon name="fa-file-excel" /> Excel
    </a>
</div>
