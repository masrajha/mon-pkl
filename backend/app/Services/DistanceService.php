<?php

namespace App\Services;

class DistanceService
{
    public function meters(float $fromLat, float $fromLng, float $toLat, float $toLng, ?int $earthRadius = null): int
    {
        $earthRadius ??= (int) config('monpkl.distance.earth_radius_meters');

        $fromLat = deg2rad($fromLat);
        $toLat = deg2rad($toLat);
        $deltaLat = $toLat - $fromLat;
        $deltaLng = deg2rad($toLng - $fromLng);

        $a = sin($deltaLat / 2) ** 2
            + cos($fromLat) * cos($toLat) * sin($deltaLng / 2) ** 2;

        return (int) round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
