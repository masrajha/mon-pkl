<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait InteractsWithTableControls
{
    protected function tablePerPage(Request $request, int $default = 10): int
    {
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : $default;
    }

    protected function applyTableSort(Builder $query, Request $request, array $allowed, string $defaultColumn, string $defaultDirection = 'asc'): Builder
    {
        $column = $request->string('sort_by')->toString();
        $direction = $request->string('sort_dir')->toString();

        if (! in_array($column, $allowed, true)) {
            $column = $defaultColumn;
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        return $query->orderBy($column, $direction);
    }
}
