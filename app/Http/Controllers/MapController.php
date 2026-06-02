<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\StudyProgram;
use App\Services\PeriodConfigurationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function places(Request $request): View
    {
        return view('maps.places', $this->filterData($request, false));
    }

    public function monitoring(Request $request): View
    {
        return view('maps.monitoring', $this->filterData($request, true));
    }

    public function placesData(Request $request): JsonResponse
    {
        $request = $this->requestWithDefaultPlacesPeriod($request);

        $query = InternshipPlace::query()
            ->with('city')
            ->withCount(['enrollments' => fn (Builder $query) => $this->scopeEnrollments($query, $request)])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('enrollments', fn (Builder $query) => $this->scopeEnrollments($query, $request));

        $features = $query
            ->orderBy('name')
            ->get()
            ->map(fn (InternshipPlace $place): array => [
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
                    'enrollments_count' => $place->enrollments_count,
                    'field_supervisor_name' => $place->field_supervisor_name,
                    'field_supervisor_phone' => $place->field_supervisor_phone,
                ],
            ]);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function monitoringData(Request $request): JsonResponse
    {
        $settings = $this->configurations->forPeriod($request->integer('period_id') ?: null);
        $limit = min(max((int) $request->integer('limit', $settings['map']['monitoring_limit_default']), 1), (int) $settings['map']['monitoring_limit_max']);
        [$startDate, $endDate] = $this->monitoringDateRange($request);

        $checkIns = CheckIn::query()
            ->with([
                'enrollment.student',
                'enrollment.studyProgram',
                'enrollment.internshipPeriod.program',
                'enrollment.internshipPlace.city',
            ])
            ->whereNotNull('student_latitude')
            ->whereNotNull('student_longitude')
            ->whereBetween('checked_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->whereHas('enrollment', fn (Builder $query) => $this->scopeEnrollments($query, $request))
            ->latest('checked_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'check_ins' => $checkIns->map(fn (CheckIn $checkIn): array => [
                'id' => $checkIn->id,
                'type' => $checkIn->type,
                'note' => $checkIn->note,
                'checked_at' => $checkIn->checked_at?->toIso8601String(),
                'distance_meters' => $checkIn->distance_meters,
                'student' => [
                    'npm' => $checkIn->enrollment?->student?->npm,
                    'name' => $checkIn->enrollment?->student?->full_name,
                ],
                'study_program' => $checkIn->enrollment?->studyProgram?->name,
                'period' => $checkIn->enrollment?->internshipPeriod?->display_name,
                'place' => [
                    'name' => $checkIn->enrollment?->internshipPlace?->name,
                    'city' => $checkIn->enrollment?->internshipPlace?->city?->name,
                ],
                'office' => [
                    'lat' => $checkIn->office_latitude !== null ? (float) $checkIn->office_latitude : null,
                    'lng' => $checkIn->office_longitude !== null ? (float) $checkIn->office_longitude : null,
                ],
                'student_location' => [
                    'lat' => (float) $checkIn->student_latitude,
                    'lng' => (float) $checkIn->student_longitude,
                ],
            ]),
        ]);
    }

    private function filterData(Request $request, bool $forMonitoring): array
    {
        $periods = $this->periodOptions($request);
        $selectedPeriod = $this->selectedPeriod($request, $periods, $forMonitoring);
        [$startDate, $endDate] = $this->monitoringDateRange($request, $selectedPeriod);

        if (($forMonitoring || $this->shouldDefaultPlacesToActivePeriod($request)) && $selectedPeriod && ! $request->filled('period_id')) {
            $request->merge(['period_id' => $selectedPeriod->id]);
        }

        return [
            'periods' => $periods,
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedPeriod' => $selectedPeriod?->id,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'todayOnly' => $request->boolean('today_only'),
            'mapConfig' => $this->configurations->frontendMapConfig($selectedPeriod ?: null),
        ];
    }

    private function periodOptions(Request $request)
    {
        $user = $request->user();

        if ($user?->hasRole('mahasiswa')) {
            return InternshipEnrollment::query()
                ->with('internshipPeriod.program')
                ->whereHas('student', fn (Builder $query) => $query->where('user_id', $user->id))
                ->get()
                ->pluck('internshipPeriod')
                ->filter()
                ->unique('id')
                ->sortByDesc(fn (InternshipPeriod $period) => ($period->is_active ? 1_000_000 : 0) + $period->id)
                ->values();
        }

        return InternshipPeriod::query()->with('program')->orderByDesc('is_active')->orderByDesc('id')->get();
    }

    private function selectedPeriod(Request $request, $periods, bool $forMonitoring): ?InternshipPeriod
    {
        if ($request->filled('period_id')) {
            return $periods->firstWhere('id', $request->integer('period_id'));
        }

        if (! $forMonitoring) {
            if ($this->shouldDefaultPlacesToActivePeriod($request)) {
                return $periods->firstWhere('is_active', true) ?? $periods->first();
            }

            return null;
        }

        return $periods->firstWhere('is_active', true) ?? $periods->first();
    }

    private function monitoringDateRange(Request $request, ?InternshipPeriod $selectedPeriod = null): array
    {
        if ($request->boolean('today_only')) {
            return [today(), today()];
        }

        $start = $request->date('start_date');
        $end = $request->date('end_date');

        $start ??= $selectedPeriod?->starts_at?->copy() ?? today();
        $end ??= $selectedPeriod?->ends_at?->copy() ?? today();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function scopeEnrollments(Builder $query, Request $request): Builder
    {
        $user = $request->user();

        if ($request->filled('period_id')) {
            $query->where('internship_period_id', $request->integer('period_id'));
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        if ($user?->hasRole('admin')) {
            return $query;
        }

        if ($user?->hasRole(['dosen', 'koordinator'])) {
            $coordinatorAssignments = $user->lecturer?->coordinatorAssignments()
                ->where('status', 'active')
                ->get(['internship_period_id', 'study_program_id']) ?? collect();

            return $query->where(function (Builder $query) use ($user, $coordinatorAssignments): void {
                $query->where(function (Builder $query) use ($user): void {
                    if ($user->lecturer?->id) {
                        $query->where('lecturer_supervisor_id', $user->lecturer->id);
                    }

                    $query->orWhere('lecturer_supervisor_user_id', $user->id)
                        ->orWhere('lecturer_supervisor', $user->name)
                        ->orWhere('lecturer_supervisor', $user->email);
                });

                $coordinatorAssignments->each(function ($assignment) use ($query): void {
                    $query->orWhere(function (Builder $query) use ($assignment): void {
                        $query->where('internship_period_id', $assignment->internship_period_id)
                            ->where('study_program_id', $assignment->study_program_id);
                    });
                });
            });
        }

        return $query->whereHas('student', fn (Builder $query) => $query->where('user_id', $user?->id));
    }

    private function requestWithDefaultPlacesPeriod(Request $request): Request
    {
        if ($request->filled('period_id') || ! $this->shouldDefaultPlacesToActivePeriod($request)) {
            return $request;
        }

        $activePeriod = $this->periodOptions($request)->firstWhere('is_active', true);

        if ($activePeriod) {
            $request->merge(['period_id' => $activePeriod->id]);
        }

        return $request;
    }

    private function shouldDefaultPlacesToActivePeriod(Request $request): bool
    {
        $user = $request->user();

        return (bool) $user
            && ! $user->hasRole('admin')
            && $user->hasRole(['mahasiswa', 'koordinator']);
    }
}
