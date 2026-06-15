<?php

namespace App\Support;

use Carbon\Carbon;

class LocalClock
{
    public static function timezone(): string
    {
        return (string) config('monpkl.timezone', config('app.timezone', 'UTC'));
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function today(): Carbon
    {
        return Carbon::today(self::timezone());
    }
}
