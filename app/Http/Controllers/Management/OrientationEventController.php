<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\OrientationEvent;
use App\Models\StudyProgram;
use App\Services\LocationSuggestionService;
use App\Services\PeriodConfigurationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrientationEventController extends Controller
{
    public function __construct(
        private readonly PeriodConfigurationService $configurations,
        private readonly LocationSuggestionService $locations,
    )
    {
    }

    public function index(Request $request): View
    {
        $selectedPeriod = $request->integer('period_id') ?: null;
        $selectedStudyProgram = $request->integer('study_program_id') ?: null;

        $events = OrientationEvent::query()
            ->with(['internshipPeriod.program', 'program', 'studyProgram'])
            ->withCount('attendances')
            ->when($selectedPeriod, fn (Builder $query) => $query->where('internship_period_id', $selectedPeriod))
            ->when($selectedStudyProgram, fn (Builder $query) => $query->where('study_program_id', $selectedStudyProgram))
            ->tap(fn (Builder $query) => $this->scopeEventQuery($query, $request))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('management.orientation-events.index', [
            'events' => $events,
            'periods' => $this->periodsFor($request),
            'studyPrograms' => $this->studyProgramsFor($request),
            'selectedPeriod' => $selectedPeriod,
            'selectedStudyProgram' => $selectedStudyProgram,
            'mapConfig' => $this->configurations->frontendMapConfig($selectedPeriod),
            'internalLocationSearchUrl' => route('locations.search'),
            'externalLocationSearchUrl' => 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&addressdetails=1&countrycodes=id&q={query}',
        ]);
    }

    public function locationSuggestions(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->locations->internal($request->string('q')->toString())->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'internship_period_id' => ['required', 'exists:internship_periods,id'],
            'study_program_id' => ['nullable', 'exists:study_programs,id'],
            'location_name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_distance_meters' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $period = InternshipPeriod::query()->with('program')->findOrFail($data['internship_period_id']);
        $this->authorizeEventScope($request, $period->id, $data['study_program_id'] ?? null);

        OrientationEvent::query()->create([
            ...$data,
            'program_id' => $period->program_id,
            'name' => $this->eventName($period),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Event pembekalan berhasil dibuat.');
    }

    public function show(Request $request, OrientationEvent $orientationEvent): View
    {
        $orientationEvent->load(['internshipPeriod.program', 'studyProgram', 'attendances.student']);
        $this->authorizeViewScope($request, $orientationEvent->internship_period_id, $orientationEvent->study_program_id);

        $participants = InternshipEnrollment::query()
            ->with(['student.studyProgram', 'studyProgram', 'internshipPeriod.program'])
            ->where('internship_period_id', $orientationEvent->internship_period_id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->when($orientationEvent->study_program_id, fn (Builder $query) => $query->where('study_program_id', $orientationEvent->study_program_id))
            ->when(! $request->user()?->hasRole('admin') && ! $orientationEvent->study_program_id, function (Builder $query) use ($request, $orientationEvent): void {
                $query->whereIn('study_program_id', $this->assignedStudyProgramIds($request, $orientationEvent->internship_period_id));
            })
            ->orderBy(StudentSort::expression())
            ->get();

        $attendances = $orientationEvent->attendances
            ->whereIn('student_id', $participants->pluck('student_id'))
            ->keyBy('student_id');

        return view('management.orientation-events.show', [
            'event' => $orientationEvent,
            'participants' => $participants,
            'attendances' => $attendances,
            'presentCount' => $attendances->count(),
            'absentCount' => max(0, $participants->count() - $attendances->count()),
        ]);
    }

    private function eventName(InternshipPeriod $period): string
    {
        $programName = $period->program?->name ?: 'Program';

        return trim('Pembekalan '.$programName.' '.$period->name);
    }

    private function periodsFor(Request $request)
    {
        if ($request->user()?->hasRole('admin')) {
            return InternshipPeriod::query()
                ->with('program')
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->get();
        }

        return $request->user()?->lecturer?->coordinatorAssignments()
            ->with('internshipPeriod.program')
            ->where('status', 'active')
            ->get()
            ->pluck('internshipPeriod')
            ->filter()
            ->unique('id')
            ->values() ?? collect();
    }

    private function studyProgramsFor(Request $request)
    {
        if ($request->user()?->hasRole('admin')) {
            return StudyProgram::query()->where('is_active', true)->orderBy('name')->get();
        }

        return $request->user()?->lecturer?->coordinatorAssignments()
            ->with('studyProgram')
            ->where('status', 'active')
            ->get()
            ->pluck('studyProgram')
            ->filter()
            ->unique('id')
            ->values() ?? collect();
    }

    private function scopeEventQuery(Builder $query, Request $request): void
    {
        if ($request->user()?->hasRole('admin')) {
            return;
        }

        $assignments = $request->user()?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();

        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $query) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $query->orWhere(function (Builder $query) use ($assignment): void {
                    $query->where('internship_period_id', $assignment->internship_period_id)
                        ->where(function (Builder $query) use ($assignment): void {
                            $query->whereNull('study_program_id')
                                ->orWhere('study_program_id', $assignment->study_program_id);
                        });
                });
            }
        });
    }

    private function authorizeEventScope(Request $request, int $periodId, ?int $studyProgramId): void
    {
        if ($request->user()?->hasRole('admin')) {
            return;
        }

        if (! $studyProgramId) {
            throw ValidationException::withMessages([
                'study_program_id' => 'Koordinator wajib memilih prodi sesuai scope tugasnya.',
            ]);
        }

        $allowed = $request->user()?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $periodId)
            ->where('study_program_id', $studyProgramId)
            ->exists();

        abort_unless($allowed, 403);
    }

    private function authorizeViewScope(Request $request, int $periodId, ?int $studyProgramId): void
    {
        if ($request->user()?->hasRole('admin')) {
            return;
        }

        $query = $request->user()?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $periodId);

        if ($studyProgramId) {
            $query?->where('study_program_id', $studyProgramId);
        }

        abort_unless((bool) $query?->exists(), 403);
    }

    private function assignedStudyProgramIds(Request $request, int $periodId)
    {
        return $request->user()?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $periodId)
            ->pluck('study_program_id') ?? collect();
    }
}

class StudentSort
{
    public static function expression(): \Illuminate\Database\Query\Expression
    {
        return \Illuminate\Support\Facades\DB::raw(
            '(select full_name from students where students.id = internship_enrollments.student_id)'
        );
    }
}
