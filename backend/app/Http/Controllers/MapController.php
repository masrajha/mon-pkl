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
        return view('maps.places', $this->filterData($request));
    }

    public function monitoring(Request $request): View
    {
        return view('maps.monitoring', $this->filterData($request));
    }

    public function placesData(Request $request): JsonResponse
    {
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

        $checkIns = CheckIn::query()
            ->with([
                'enrollment.student',
                'enrollment.studyProgram',
                'enrollment.internshipPeriod',
                'enrollment.internshipPlace.city',
            ])
            ->whereNotNull('student_latitude')
            ->whereNotNull('student_longitude')
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
                'period' => $checkIn->enrollment?->internshipPeriod?->name,
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

    private function filterData(Request $request): array
    {
        return [
            'periods' => InternshipPeriod::query()->orderByDesc('is_active')->orderByDesc('id')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedPeriod' => $request->integer('period_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'mapConfig' => $this->configurations->frontendMapConfig($request->integer('period_id') ?: null),
        ];
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

        if ($user?->hasRole('dosen')) {
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
}
