<?php

namespace App\Services;

use App\Models\InternshipPlace;
use App\Models\InternshipPlaceProposal;
use App\Models\OrientationEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LocationSuggestionService
{
    public function internal(string $term, int $limit = 8): Collection
    {
        $term = Str::of($term)->lower()->squish()->toString();

        if (mb_strlen($term) < 3) {
            return collect();
        }

        $like = '%'.$term.'%';

        $places = InternshipPlace::query()
            ->with('city')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function (Builder $query) use ($like): void {
                $query->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(address) LIKE ?', [$like]);
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (InternshipPlace $place): array => $this->format(
                source: 'Mitra',
                name: $place->name,
                address: $place->address ?: $place->city?->name,
                latitude: $place->latitude,
                longitude: $place->longitude,
            ));

        $events = OrientationEvent::query()
            ->where(function (Builder $query) use ($like): void {
                $query->whereRaw('LOWER(location_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (OrientationEvent $event): array => $this->format(
                source: 'Pembekalan',
                name: $event->location_name,
                address: $event->name,
                latitude: $event->latitude,
                longitude: $event->longitude,
            ));

        $proposals = InternshipPlaceProposal::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', '!=', 'rejected')
            ->where(function (Builder $query) use ($like): void {
                $query->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(address) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(city_name) LIKE ?', [$like]);
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (InternshipPlaceProposal $proposal): array => $this->format(
                source: 'Usulan mitra',
                name: $proposal->name,
                address: $proposal->address ?: $proposal->city_name,
                latitude: $proposal->latitude,
                longitude: $proposal->longitude,
            ));

        return $places
            ->concat($events)
            ->concat($proposals)
            ->unique(fn (array $item): string => Str::lower($item['name']).'|'.$item['latitude'].'|'.$item['longitude'])
            ->values()
            ->take($limit);
    }

    private function format(string $source, string $name, ?string $address, mixed $latitude, mixed $longitude): array
    {
        return [
            'source' => $source,
            'name' => $name,
            'address' => $address,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }
}
