<?php

namespace App\Services;

use App\Models\InternshipPlace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class MapRouteService
{
    public function __construct(private readonly DistanceService $distance)
    {
    }

    public function route(Collection $places, float $startLat, float $startLng): array
    {
        $places = $places
            ->filter(fn (InternshipPlace $place): bool => $place->latitude !== null && $place->longitude !== null)
            ->values();

        $limit = max(1, (int) config('monpkl.routing.max_places', 25));
        $selectedPlaces = $places
            ->sortBy(fn (InternshipPlace $place): int => $this->distance->meters($startLat, $startLng, (float) $place->latitude, (float) $place->longitude))
            ->take($limit)
            ->values();

        if ($selectedPlaces->isEmpty()) {
            return $this->emptyRoute();
        }

        $fallback = fn (?string $reason = null): array => $this->fallbackRoute($selectedPlaces, $startLat, $startLng, $places->count() - $selectedPlaces->count(), $reason);

        if (! config('monpkl.routing.osrm.enabled', true)) {
            return $fallback('OSRM dinonaktifkan.');
        }

        try {
            return Cache::remember(
                $this->cacheKey($selectedPlaces, $startLat, $startLng),
                now()->addMinutes((int) config('monpkl.routing.cache_minutes', 60)),
                fn (): array => $this->osrmRoute($selectedPlaces, $startLat, $startLng, $places->count() - $selectedPlaces->count())
            );
        } catch (Throwable $exception) {
            return $fallback('OSRM belum tersedia, rute garis lurus dipakai sementara.');
        }
    }

    private function osrmRoute(Collection $places, float $startLat, float $startLng, int $omittedCount): array
    {
        $baseUrl = rtrim((string) config('monpkl.routing.osrm.base_url'), '/');
        $profile = trim((string) config('monpkl.routing.osrm.profile', 'driving'), '/');
        $coordinates = collect([[null, $startLng, $startLat]])
            ->merge($places->map(fn (InternshipPlace $place): array => [$place->id, (float) $place->longitude, (float) $place->latitude]))
            ->map(fn (array $point): string => $point[1].','.$point[2])
            ->implode(';');

        $response = Http::acceptJson()
            ->timeout((int) config('monpkl.routing.osrm.timeout_seconds', 5))
            ->get("{$baseUrl}/trip/v1/{$profile}/{$coordinates}", [
                'source' => 'first',
                'roundtrip' => 'false',
                'geometries' => 'geojson',
                'overview' => 'full',
                'steps' => 'false',
            ]);

        if (! $response->ok()) {
            throw new \RuntimeException('OSRM request failed.');
        }

        $payload = $response->json();
        $trip = $payload['trips'][0] ?? null;
        $waypoints = collect($payload['waypoints'] ?? []);

        if (! is_array($trip) || $waypoints->isEmpty()) {
            throw new \RuntimeException('OSRM response is incomplete.');
        }

        $placeByInputIndex = $places->values()->mapWithKeys(fn (InternshipPlace $place, int $index): array => [$index + 1 => $place]);
        $orderedWaypoints = $waypoints
            ->map(function (array $waypoint, int $inputIndex): array {
                $waypoint['input_index'] = $inputIndex;

                return $waypoint;
            })
            ->filter(fn (array $waypoint): bool => isset($waypoint['waypoint_index'], $waypoint['location']))
            ->sortBy('waypoint_index')
            ->values();
        $legs = collect($trip['legs'] ?? []);
        $stops = [];
        $runningDistance = 0;
        $runningDuration = 0;

        foreach ($orderedWaypoints as $routeIndex => $waypoint) {
            $inputIndex = (int) ($waypoint['input_index'] ?? -1);

            if ($inputIndex === 0) {
                continue;
            }

            $place = $placeByInputIndex->get($inputIndex);

            if (! $place) {
                continue;
            }

            $leg = $legs->get(max(0, $routeIndex - 1), []);
            $segmentDistance = (int) round((float) ($leg['distance'] ?? 0));
            $segmentDuration = (int) round((float) ($leg['duration'] ?? 0));
            $runningDistance += $segmentDistance;
            $runningDuration += $segmentDuration;

            $stops[] = $this->stopPayload($place, count($stops) + 1, $segmentDistance, $runningDistance, $segmentDuration, $runningDuration);
        }

        if ($stops === []) {
            throw new \RuntimeException('OSRM route contains no stops.');
        }

        return [
            'source' => 'osrm',
            'fallback' => false,
            'message' => null,
            'omitted_count' => max(0, $omittedCount),
            'total_distance_meters' => (int) round((float) ($trip['distance'] ?? $runningDistance)),
            'total_duration_seconds' => (int) round((float) ($trip['duration'] ?? $runningDuration)),
            'geometry' => $trip['geometry']['coordinates'] ?? [],
            'stops' => $stops,
        ];
    }

    private function fallbackRoute(Collection $places, float $startLat, float $startLng, int $omittedCount, ?string $reason): array
    {
        $remaining = $places->values();
        $currentLat = $startLat;
        $currentLng = $startLng;
        $stops = [];
        $geometry = [[$startLng, $startLat]];
        $runningDistance = 0;

        while ($remaining->isNotEmpty()) {
            $nearest = $remaining
                ->sortBy(fn (InternshipPlace $place): int => $this->distance->meters($currentLat, $currentLng, (float) $place->latitude, (float) $place->longitude))
                ->first();

            $segmentDistance = $this->distance->meters($currentLat, $currentLng, (float) $nearest->latitude, (float) $nearest->longitude);
            $runningDistance += $segmentDistance;
            $stops[] = $this->stopPayload($nearest, count($stops) + 1, $segmentDistance, $runningDistance, null, null);
            $geometry[] = [(float) $nearest->longitude, (float) $nearest->latitude];
            $currentLat = (float) $nearest->latitude;
            $currentLng = (float) $nearest->longitude;
            $remaining = $remaining->reject(fn (InternshipPlace $place): bool => $place->id === $nearest->id)->values();
        }

        return [
            'source' => 'haversine',
            'fallback' => true,
            'message' => $reason,
            'omitted_count' => max(0, $omittedCount),
            'total_distance_meters' => $runningDistance,
            'total_duration_seconds' => null,
            'geometry' => $geometry,
            'stops' => $stops,
        ];
    }

    private function stopPayload(InternshipPlace $place, int $order, int $segmentDistance, int $cumulativeDistance, ?int $segmentDuration, ?int $cumulativeDuration): array
    {
        return [
            'order' => $order,
            'id' => $place->id,
            'name' => $place->name,
            'city' => $place->city?->name,
            'address' => $place->address,
            'lat' => (float) $place->latitude,
            'lng' => (float) $place->longitude,
            'segment_distance_meters' => $segmentDistance,
            'cumulative_distance_meters' => $cumulativeDistance,
            'segment_duration_seconds' => $segmentDuration,
            'cumulative_duration_seconds' => $cumulativeDuration,
        ];
    }

    private function emptyRoute(): array
    {
        return [
            'source' => 'none',
            'fallback' => false,
            'message' => null,
            'omitted_count' => 0,
            'total_distance_meters' => 0,
            'total_duration_seconds' => null,
            'geometry' => [],
            'stops' => [],
        ];
    }

    private function cacheKey(Collection $places, float $startLat, float $startLng): string
    {
        return 'map-route:'.md5(json_encode([
            'base' => config('monpkl.routing.osrm.base_url'),
            'profile' => config('monpkl.routing.osrm.profile'),
            'start' => [round($startLat, 6), round($startLng, 6)],
            'places' => $places->pluck('id')->values()->all(),
        ]));
    }
}
