<?php

namespace App\Http\Controllers;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\CheckIn;
use App\Models\Program;
use App\Models\StudyProgram;
use App\Services\PeriodConfigurationService;
use App\Services\ParticipantRiskScoringService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly PeriodConfigurationService $configurations,
        private readonly ParticipantRiskScoringService $riskScoring,
    )
    {
    }

    public function monitoring(Request $request): View
    {
        [$periods, $selectedPeriod] = $this->periodOptions($request);
        [$startDate, $endDate] = $this->dateRange($request, $selectedPeriod);

        $query = InternshipEnrollment::query()
            ->with([
                'student.user',
                'studyProgram',
                'internshipPeriod.program',
                'internshipPlace',
                'checkIns' => fn ($query) => $query
                    ->whereBetween('checked_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                    ->orderBy('checked_at'),
            ])
            ->whereHas('checkIns', fn ($query) => $query->whereBetween('checked_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]));

        $this->scopeEnrollments($query, $request);

        $enrollments = $query->get();

        $rows = $enrollments
            ->map(fn (InternshipEnrollment $enrollment) => $this->summarizeEnrollment($enrollment, $request, $startDate, $endDate))
            ->filter()
            ->sortBy('name')
            ->values();

        return view('reports.monitoring', [
            'rows' => $rows,
            'periods' => $periods,
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedPeriod' => $selectedPeriod?->id,
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'includeSaturday' => $request->boolean('include_saturday'),
            'includeSunday' => $request->boolean('include_sunday'),
            'includeHolidays' => $request->boolean('include_holidays'),
            'totals' => [
                'students' => $rows->count(),
                'attendance_days' => $rows->sum('attendance_days'),
                'duration_hours' => round($rows->sum('duration_hours'), 2),
            ],
        ]);
    }

    public function progressFunnel(Request $request): View
    {
        [$periods, $selectedPeriod] = $this->periodOptions($request);

        $baseQuery = InternshipEnrollment::query()
            ->with(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace'])
            ->whereIn('status', ['active', 'completed']);

        $this->scopeEnrollments($baseQuery, $request);

        $total = (clone $baseQuery)->count();
        $stageDefinitions = $this->progressFunnelStages();
        $previousCount = null;

        $stages = collect($stageDefinitions)
            ->map(function (array $stage) use ($baseQuery, $total, &$previousCount): array {
                $query = clone $baseQuery;
                ($stage['constraint'])($query);

                $count = $query->count();
                $dropFromPrevious = $previousCount === null ? 0 : max(0, $previousCount - $count);
                $conversionFromPrevious = $previousCount === null || $previousCount === 0
                    ? 100
                    : round(($count / $previousCount) * 100, 1);
                $previousCount = $count;

                return [
                    'key' => $stage['key'],
                    'label' => $stage['label'],
                    'description' => $stage['description'],
                    'count' => $count,
                    'percent_of_total' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                    'conversion_from_previous' => $conversionFromPrevious,
                    'drop_from_previous' => $dropFromPrevious,
                ];
            })
            ->values();

        $bottleneck = $stages
            ->skip(1)
            ->sortByDesc('drop_from_previous')
            ->first();

        $breakdownRows = StudyProgram::query()
            ->whereIn('id', (clone $baseQuery)->select('study_program_id')->distinct())
            ->orderBy('name')
            ->get()
            ->map(function (StudyProgram $studyProgram) use ($baseQuery): array {
                $studyProgramQuery = (clone $baseQuery)->where('study_program_id', $studyProgram->id);
                $total = (clone $studyProgramQuery)->count();

                return [
                    'name' => $studyProgram->name,
                    'total' => $total,
                    'active_attendance' => $this->countStage($studyProgramQuery, 'active_attendance'),
                    'full_report' => $this->countStage($studyProgramQuery, 'full_report'),
                    'seminar' => $this->countStage($studyProgramQuery, 'seminar'),
                    'lecturer_score' => $this->countStage($studyProgramQuery, 'lecturer_score'),
                    'field_supervisor_score' => $this->countStage($studyProgramQuery, 'field_supervisor_score'),
                    'final_score' => $this->countStage($studyProgramQuery, 'final_score'),
                ];
            })
            ->filter(fn (array $row): bool => $row['total'] > 0)
            ->values();

        return view('reports.progress-funnel', [
            'periods' => $periods,
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedPeriod' => $selectedPeriod?->id,
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'stages' => $stages,
            'total' => $total,
            'bottleneck' => $bottleneck,
            'breakdownRows' => $breakdownRows,
        ]);
    }

    public function riskScoring(Request $request): View
    {
        [$periods, $selectedPeriod] = $this->periodOptions($request);

        $query = InternshipEnrollment::query()
            ->with([
                'student.user',
                'studyProgram',
                'internshipPeriod.program',
                'internshipPlace',
                'lecturer',
                'lecturerSupervisor',
                'checkIns',
                'forgottenAttendanceRequests',
                'submissionProgress',
                'seminarRequests',
                'fieldSupervisorAssessment',
                'finalAssessment',
            ])
            ->whereIn('status', ['active', 'completed']);

        $this->scopeEnrollments($query, $request);

        $allRows = $query->get()
            ->map(fn (InternshipEnrollment $enrollment): array => $this->riskScoring->score($enrollment))
            ->sortByDesc('score')
            ->values();

        $summary = collect(['safe', 'watch', 'risky', 'critical'])
            ->mapWithKeys(fn (string $key): array => [$key => $allRows->where('category_key', $key)->count()])
            ->all();
        $rows = $request->filled('risk')
            ? $allRows->where('category_key', $request->string('risk')->toString())->values()
            : $allRows->reject(fn (array $row): bool => $row['category_key'] === 'safe')->values();

        return view('reports.risk-scoring', [
            'periods' => $periods,
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedPeriod' => $selectedPeriod?->id,
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'selectedRisk' => $request->string('risk')->toString(),
            'rows' => $rows,
            'totalRows' => $allRows->count(),
            'summary' => $summary,
        ]);
    }

    public function attendanceHeatmap(Request $request): View
    {
        [$periods, $selectedPeriod] = $this->periodOptions($request);
        [$startDate, $endDate] = $this->heatmapDateRange($request, $selectedPeriod, $periods);
        $dates = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn (Carbon $date): Carbon => $date->copy())
            ->values();

        $query = InternshipEnrollment::query()
            ->with([
                'student.user',
                'studyProgram',
                'internshipPeriod.program',
                'internshipPlace',
                'checkIns' => fn ($query) => $query
                    ->whereBetween('checked_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                    ->orderBy('checked_at'),
                'forgottenAttendanceRequests' => fn ($query) => $query
                    ->whereBetween('requested_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orderBy('requested_date'),
            ])
            ->whereIn('status', ['active', 'completed']);

        $this->scopeEnrollments($query, $request);

        $rows = $query->get()
            ->sortBy(fn (InternshipEnrollment $enrollment): string => $enrollment->student?->full_name ?? '')
            ->map(fn (InternshipEnrollment $enrollment): array => $this->heatmapRow($enrollment, $dates))
            ->values();
        $summary = $rows
            ->flatMap(fn (array $row): Collection => $row['cells'])
            ->countBy('status')
            ->all();

        return view('reports.attendance-heatmap', [
            'periods' => $periods,
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedPeriod' => $selectedPeriod?->id,
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'dates' => $dates,
            'rows' => $rows,
            'summary' => $summary,
            'legend' => $this->heatmapLegend(),
        ]);
    }

    private function periodOptions(Request $request): array
    {
        $user = $request->user();

        if (! $user?->hasRole('mahasiswa')) {
            $periods = InternshipPeriod::query()
                ->with('program')
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->get();
            $selectedPeriod = $request->integer('period_id')
                ? $periods->firstWhere('id', $request->integer('period_id'))
                : null;

            return [$periods, $selectedPeriod];
        }

        $enrollments = InternshipEnrollment::query()
            ->with('internshipPeriod.program')
            ->whereHas('student', fn (Builder $query) => $query->where('user_id', $user->id))
            ->get()
            ->sortByDesc(fn (InternshipEnrollment $enrollment) => (
                ($enrollment->status === 'active' ? 1_000_000 : 0)
                + ($enrollment->internshipPeriod?->is_active ? 10_000 : 0)
                + $enrollment->id
            ));

        $periods = $enrollments
            ->pluck('internshipPeriod')
            ->filter()
            ->unique('id')
            ->values();

        $requestedPeriod = $request->integer('period_id');
        $selectedPeriod = $requestedPeriod
            ? $periods->firstWhere('id', $requestedPeriod)
            : null;

        $selectedPeriod ??= $enrollments
            ->firstWhere('status', 'active')
            ?->internshipPeriod;

        $selectedPeriod ??= $periods->first();

        if ($selectedPeriod) {
            $request->merge(['period_id' => $selectedPeriod->id]);
        }

        return [$periods, $selectedPeriod];
    }

    private function summarizeEnrollment(InternshipEnrollment $enrollment, Request $request, Carbon $startDate, Carbon $endDate): ?array
    {
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);

        $allowedDates = collect(CarbonPeriod::create($startDate, $endDate))
            ->filter(fn (Carbon $date) => $this->isIncludedDate($date, $request, $settings))
            ->mapWithKeys(fn (Carbon $date) => [$date->toDateString() => true]);

        $daily = $enrollment->checkIns
            ->filter(fn ($checkIn) => isset($allowedDates[$checkIn->checked_at->toDateString()]))
            ->groupBy(fn ($checkIn) => $checkIn->checked_at->toDateString())
            ->map(fn ($items) => $this->summarizeDay($items->values(), $settings))
            ->filter();

        if ($daily->isEmpty()) {
            return null;
        }

        $checkIns = $enrollment->checkIns->filter(fn ($checkIn) => isset($allowedDates[$checkIn->checked_at->toDateString()]));
        $distances = $daily->pluck('distance_meters')->filter(fn ($distance) => $distance !== null);

        return [
            'photo_url' => $enrollment->student?->user?->avatar_url,
            'name' => $enrollment->student?->full_name,
            'npm' => $enrollment->student?->npm,
            'email' => $enrollment->student?->user?->email,
            'study_program' => $enrollment->studyProgram?->name,
            'period' => $enrollment->internshipPeriod?->display_name,
            'place' => $enrollment->internshipPlace?->name,
            'attendance_days' => $daily->count(),
            'check_ins_count' => $checkIns->count(),
            'average_distance_meters' => $distances->isEmpty() ? null : round($distances->avg(), 2),
            'duration_hours' => round($daily->sum('duration_hours'), 2),
            'min_check_in' => $this->secondsToTime($daily->pluck('check_in_seconds')->filter()->min()),
            'max_check_in' => $this->secondsToTime($daily->pluck('check_in_seconds')->filter()->max()),
            'min_check_out' => $this->secondsToTime($daily->pluck('check_out_seconds')->filter()->min()),
            'max_check_out' => $this->secondsToTime($daily->pluck('check_out_seconds')->filter()->max()),
        ];
    }

    private function summarizeDay($checkIns, array $settings): array
    {
        $checkInRow = $checkIns->firstWhere('action', 'check_in');
        $checkOutRow = $checkIns->firstWhere('action', 'check_out');

        if (! $checkInRow || ! $checkOutRow) {
            if ($checkIns->whereNull('action')->count() >= 2) {
                $checkInRow = $checkIns->first();
                $checkOutRow = $checkIns->last();
            } else {
                return [];
            }
        }

        $checkIn = $checkInRow->checked_at;
        $checkOut = $checkOutRow->checked_at;

        return [
            'check_in_seconds' => $this->secondsOfDay($checkIn),
            'check_out_seconds' => $this->secondsOfDay($checkOut),
            'duration_hours' => round($checkIn->diffInMinutes($checkOut) / 60, 2),
            'distance_meters' => $checkIns->pluck('distance_meters')->filter(fn ($distance) => $distance !== null)->avg(),
        ];
    }

    private function heatmapDateRange(Request $request, ?InternshipPeriod $selectedPeriod, $periods): array
    {
        $referencePeriod = $selectedPeriod
            ?? $periods->firstWhere('is_active', true)
            ?? $periods->first();
        $today = now()->copy()->startOfDay();
        $start = $request->date('start_date')
            ?? $referencePeriod?->starts_at?->copy()
            ?? $today->copy()->subDays(13);
        $end = $request->date('end_date')
            ?? $referencePeriod?->ends_at?->copy()
            ?? $today->copy();

        if (! $request->filled('end_date') && $end->greaterThan($today)) {
            $end = $today->copy();
        }

        if ($end->lessThan($start)) {
            $end = $start->copy();
        }

        return [$start->startOfDay(), $end->startOfDay()];
    }

    private function heatmapRow(InternshipEnrollment $enrollment, Collection $dates): array
    {
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);
        $holidays = collect($settings['calendar']['holidays'] ?? [])->filter()->flip();
        $checkInsByDate = $enrollment->checkIns
            ->filter(fn (CheckIn $checkIn): bool => filled($checkIn->checked_at))
            ->groupBy(fn (CheckIn $checkIn): string => $checkIn->checked_at->toDateString());
        $forgottenByDate = $enrollment->forgottenAttendanceRequests
            ->where('status', 'approved')
            ->groupBy(fn ($request): string => $request->requested_date?->toDateString() ?? (string) $request->requested_date);

        $cells = $dates->map(function (Carbon $date) use ($checkInsByDate, $forgottenByDate, $holidays): array {
            $dateKey = $date->toDateString();
            $checkIns = $checkInsByDate->get($dateKey, collect());
            $status = $this->heatmapStatus($date, $checkIns, $forgottenByDate->has($dateKey), $holidays->has($dateKey));
            $meta = $this->heatmapLegend()[$status];

            return [
                'date' => $dateKey,
                'day' => $date->format('d'),
                'status' => $status,
                'label' => $meta['label'],
                'class' => $meta['class'],
                'check_in' => $checkIns->firstWhere('action', 'check_in')?->checked_at?->format('H:i'),
                'check_out' => $checkIns->firstWhere('action', 'check_out')?->checked_at?->format('H:i'),
            ];
        });

        return [
            'enrollment' => $enrollment,
            'cells' => $cells,
            'valid_days' => $cells->where('status', 'present')->count() + $cells->where('status', 'forgotten_approved')->count(),
            'problem_days' => $cells->whereIn('status', ['incomplete', 'absent'])->count(),
        ];
    }

    private function heatmapStatus(Carbon $date, Collection $checkIns, bool $hasApprovedForgottenAttendance, bool $isHoliday): string
    {
        if ($isHoliday) {
            return 'holiday';
        }

        if ($date->isWeekend()) {
            return 'weekend';
        }

        if ($hasApprovedForgottenAttendance || $checkIns->contains('source_type', 'forgotten_request')) {
            return 'forgotten_approved';
        }

        $hasCheckIn = $checkIns->contains('action', 'check_in');
        $hasCheckOut = $checkIns->contains('action', 'check_out');

        if (($hasCheckIn && $hasCheckOut) || (! $hasCheckIn && ! $hasCheckOut && $checkIns->whereNull('action')->count() >= 2)) {
            return 'present';
        }

        if ($checkIns->isNotEmpty()) {
            return 'incomplete';
        }

        return 'absent';
    }

    private function heatmapLegend(): array
    {
        return [
            'present' => ['label' => 'Hadir valid', 'class' => 'bg-emerald-500 text-white ring-emerald-600'],
            'incomplete' => ['label' => 'Presensi satu sisi/tidak valid', 'class' => 'bg-amber-400 text-amber-950 ring-amber-500'],
            'absent' => ['label' => 'Tidak hadir', 'class' => 'bg-red-100 text-red-800 ring-red-200'],
            'forgotten_approved' => ['label' => 'Lupa Presensi disetujui', 'class' => 'bg-sky-500 text-white ring-sky-600'],
            'weekend' => ['label' => 'Sabtu/Minggu', 'class' => 'bg-slate-200 text-slate-600 ring-slate-300'],
            'holiday' => ['label' => 'Hari libur', 'class' => 'bg-violet-100 text-violet-800 ring-violet-200'],
        ];
    }

    private function scopeEnrollments(Builder $query, Request $request): Builder
    {
        $user = $request->user();

        if ($request->filled('period_id')) {
            $query->where('internship_period_id', $request->integer('period_id'));
        }

        if ($request->filled('program_id')) {
            $query->whereHas('internshipPeriod', fn (Builder $period) => $period->where('program_id', $request->integer('program_id')));
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
                $hasCondition = false;

                if ($user->role === 'dosen') {
                    $query->where(function (Builder $query) use ($user): void {
                        if ($user->lecturer?->id) {
                            $query->where('lecturer_supervisor_id', $user->lecturer->id);
                        }

                        $query->orWhere('lecturer_supervisor_user_id', $user->id)
                            ->orWhere('lecturer_supervisor', $user->name)
                            ->orWhere('lecturer_supervisor', $user->email);
                    });

                    $hasCondition = true;
                }

                $coordinatorAssignments->each(function ($assignment) use ($query, &$hasCondition): void {
                    $method = $hasCondition ? 'orWhere' : 'where';

                    $query->{$method}(function (Builder $query) use ($assignment): void {
                        $query->where('internship_period_id', $assignment->internship_period_id)
                            ->where('study_program_id', $assignment->study_program_id);
                    });

                    $hasCondition = true;
                });

                if (! $hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            });
        }

        return $query->whereHas('student', fn (Builder $query) => $query->where('user_id', $user?->id));
    }

    private function dateRange(Request $request, ?InternshipPeriod $selectedPeriod = null): array
    {
        $start = $request->date('start_date');
        $end = $request->date('end_date');

        if (! $start || ! $end) {
            $checkIns = CheckIn::query();

            if ($selectedPeriod) {
                $checkIns->whereHas('enrollment', fn (Builder $query) => $query->where('internship_period_id', $selectedPeriod->id));
            }

            $firstCheckIn = (clone $checkIns)->min('checked_at');
            $lastCheckIn = (clone $checkIns)->max('checked_at');

            $start ??= $selectedPeriod?->starts_at?->copy()
                ?? ($firstCheckIn ? Carbon::parse($firstCheckIn) : now()->startOfYear());
            $end ??= $selectedPeriod?->ends_at?->copy()
                ?? ($lastCheckIn ? Carbon::parse($lastCheckIn) : now());
        }

        return [$start->startOfDay(), $end->startOfDay()];
    }

    private function isIncludedDate(Carbon $date, Request $request, array $settings): bool
    {
        if (! $request->boolean('include_saturday') && $date->isSaturday()) {
            return false;
        }

        if (! $request->boolean('include_sunday') && $date->isSunday()) {
            return false;
        }

        if (! $request->boolean('include_holidays') && in_array($date->toDateString(), $settings['calendar']['holidays'] ?? [], true)) {
            return false;
        }

        return true;
    }

    private function secondsOfDay(Carbon $date): int
    {
        return ((int) $date->format('H')) * 3600 + ((int) $date->format('i')) * 60 + (int) $date->format('s');
    }

    private function secondsToTime(null|int|float $seconds): string
    {
        if (! $seconds) {
            return '--:--:--';
        }

        return gmdate('H:i:s', (int) $seconds);
    }

    private function progressFunnelStages(): array
    {
        return [
            [
                'key' => 'approved',
                'label' => 'Pendaftaran Disetujui',
                'description' => 'Enrollment berstatus aktif atau selesai.',
                'constraint' => fn (Builder $query) => $query,
            ],
            [
                'key' => 'active_attendance',
                'label' => 'Presensi Aktif',
                'description' => 'Peserta sudah memiliki minimal satu data presensi.',
                'constraint' => fn (Builder $query) => $query->whereHas('checkIns'),
            ],
            [
                'key' => 'full_report',
                'label' => 'Laporan Lengkap',
                'description' => 'Laporan akhir sudah disetujui.',
                'constraint' => fn (Builder $query) => $query->whereHas('submissionProgress', fn (Builder $progress) => $progress
                    ->where('deadline_type', 'full_report')
                    ->where('status', 'approved')),
            ],
            [
                'key' => 'seminar',
                'label' => 'Seminar Dijadwalkan/Selesai',
                'description' => 'Seminar sudah dijadwalkan atau selesai.',
                'constraint' => fn (Builder $query) => $query->whereHas('seminarRequests', fn (Builder $seminar) => $seminar
                    ->where(function (Builder $seminar): void {
                        $seminar->whereIn('status', ['scheduled', 'completed'])
                            ->orWhereNotNull('scheduled_at')
                            ->orWhereNotNull('completed_at');
                    })),
            ],
            [
                'key' => 'lecturer_score',
                'label' => 'Nilai Dosen Masuk',
                'description' => 'Nilai seminar/laporan dari dosen sudah tersimpan.',
                'constraint' => fn (Builder $query) => $query->whereHas('seminarRequests', fn (Builder $seminar) => $seminar->whereNotNull('seminar_score')),
            ],
            [
                'key' => 'field_supervisor_score',
                'label' => 'Nilai Pembimbing Lapangan Masuk',
                'description' => 'Form nilai pembimbing lapangan sudah dikirim.',
                'constraint' => fn (Builder $query) => $query->whereHas('fieldSupervisorAssessment', fn (Builder $assessment) => $assessment->whereNotNull('final_score')),
            ],
            [
                'key' => 'final_score',
                'label' => 'Nilai Final',
                'description' => 'Finalisasi nilai sudah disahkan.',
                'constraint' => fn (Builder $query) => $query->whereHas('finalAssessment', fn (Builder $assessment) => $assessment
                    ->whereNotNull('final_score')
                    ->whereNotNull('finalized_at')),
            ],
        ];
    }

    private function countStage(Builder $query, string $stageKey): int
    {
        $stage = collect($this->progressFunnelStages())->firstWhere('key', $stageKey);
        $stageQuery = clone $query;
        ($stage['constraint'])($stageQuery);

        return $stageQuery->count();
    }
}
