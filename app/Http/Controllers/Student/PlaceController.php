<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Services\PeriodConfigurationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaceController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function index(Request $request): View
    {
        $activePeriod = $this->activePeriod();

        return view('student.places.index', [
            'activePeriod' => $activePeriod,
            'mapConfig' => $this->configurations->frontendMapConfig($activePeriod),
            'dataUrl' => route('student.places.data', $request->query()),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $places = $this->query($request)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $places->map(fn (InternshipPlace $place): array => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $place->longitude, (float) $place->latitude],
                ],
                'properties' => [
                    'id' => $place->id,
                    'name' => $place->name,
                    'address' => $place->address,
                    'city' => $place->city?->name,
                    'visited' => $place->visited,
                    'enrollments_count' => $place->active_period_enrollments_count,
                    'field_supervisor_name' => $place->field_supervisor_name,
                    'field_supervisor_phone' => $place->field_supervisor_phone,
                    'register_url' => route('student.enrollments.create', ['internship_place_id' => $place->id]),
                ],
            ])->values(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $search = trim($request->string('q')->toString());

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        return response()->json(
            $this->query($request)
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'city_id', 'name', 'address'])
                ->map(fn (InternshipPlace $place): array => [
                    'id' => $place->id,
                    'name' => $place->name,
                    'address' => $place->address,
                    'city' => $place->city?->name,
                    'label' => $place->name.($place->city?->name ? ' - '.$place->city->name : ''),
                ])
                ->values(),
        );
    }


    private function query(Request $request): Builder
    {
        $activePeriod = $this->activePeriod();

        $query = InternshipPlace::query()
            ->with('city')
            ->withCount([
                'enrollments as active_period_enrollments_count' => fn (Builder $query) => $query
                    ->when($activePeriod, fn (Builder $query) => $query->where('internship_period_id', $activePeriod->id))
                    ->when(! $activePeriod, fn (Builder $query) => $query->whereRaw('1 = 0')),
            ])
            ->where('is_active', true);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn (Builder $query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('address', 'like', '%'.$search.'%')
                ->orWhereHas('city', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%')));
        }

        return $query;
    }

    private function activePeriod(): ?InternshipPeriod
    {
        return InternshipPeriod::query()
            ->with('program')
            ->where('is_active', true)
            ->where('is_locked', false)
            ->orderByDesc('id')
            ->first();
    }
}
