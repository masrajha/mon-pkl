<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasLocalDateTimes
{
    protected function localDateTimeAttribute(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => blank($value) ? null : Carbon::parse($value, $this->localDateTimeTimezone()),
            set: fn ($value) => blank($value) ? null : $this->formatLocalDateTime($value),
        );
    }

    protected function localDateTimeTimezone(): string
    {
        return (string) config('monpkl.timezone', config('app.timezone', 'UTC'));
    }

    private function formatLocalDateTime(mixed $value): string
    {
        $timezone = $this->localDateTimeTimezone();

        $date = $value instanceof DateTimeInterface
            ? Carbon::instance($value)->timezone($timezone)
            : Carbon::parse($value, $timezone);

        return $date->format($this->getDateFormat());
    }
}
