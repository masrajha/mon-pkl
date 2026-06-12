<?php

namespace App\Http\Controllers;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\CheckIn;
use App\Models\Program;
use App\Models\StudyProgram;
use App\Services\PeriodConfigurationService;
use App\Services\ParticipantRiskScoringService;
use App\Services\ReportScopeService;
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
        private readonly ReportScopeService $reportScope,
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
                'checkIns' => fn ($query) => $query->orderBy('checked_at'),
            ])
            ->whereHas('checkIns', fn ($query) => $query->whereBetween('checked_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]));

        $this->onlyReportParticipants($query);
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
            'periodDateRanges' => $this->reportPeriodDateRanges($request, $periods),
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => $this->studyProgramOptions($request),
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
            ->with(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace']);

        $this->onlyReportParticipants($baseQuery);
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
                    'field_supervisor_score' => $this->countStage($studyProgramQuery, 'field_supervisor_score'),
                    'seminar' => $this->countStage($studyProgramQuery, 'seminar'),
                    'lecturer_score' => $this->countStage($studyProgramQuery, 'lecturer_score'),
                    'final_score' => $this->countStage($studyProgramQuery, 'final_score'),
                ];
            })
            ->filter(fn (array $row): bool => $row['total'] > 0)
            ->values();

        return view('reports.progress-funnel', [
            'periods' => $periods,
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => $this->studyProgramOptions($request),
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
            ]);

        $this->onlyReportParticipants($query);
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
            'studyPrograms' => $this->studyProgramOptions($request),
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
            ]);

        $this->onlyReportParticipants($query);
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
            'periodDateRanges' => $this->reportPeriodDateRanges($request, $periods),
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => $this->studyProgramOptions($request),
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

    public function operationalCharts(Request $request): View
    {
        [$periods, $selectedPeriod] = $this->periodOptions($request);
        [$startDate, $endDate] = $this->dateRange($request, $selectedPeriod);
        $dates = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn (Carbon $date): Carbon => $date->copy())
            ->values();

        $query = InternshipEnrollment::query()
            ->with([
                'student.user',
                'studyProgram',
                'internshipPeriod.program',
                'internshipPlace',
                'lecturerSupervisor',
                'checkIns' => fn ($query) => $query
                    ->whereBetween('checked_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                    ->orderBy('checked_at'),
                'submissionProgress',
                'seminarRequests',
                'fieldSupervisorAssessment',
                'finalAssessment',
            ]);

        $this->onlyReportParticipants($query);
        $this->scopeEnrollments($query, $request);
        $enrollments = $query->get();

        $attendanceTrend = $dates
            ->map(function (Carbon $date) use ($enrollments): array {
                $dateKey = $date->toDateString();
                $daily = $enrollments->flatMap(fn (InternshipEnrollment $enrollment): Collection => $enrollment->checkIns)
                    ->filter(fn (CheckIn $checkIn): bool => $checkIn->checked_at?->toDateString() === $dateKey);

                return [
                    'date' => $dateKey,
                    'label' => $date->translatedFormat('d M'),
                    'check_in' => $daily->where('action', 'check_in')->pluck('internship_enrollment_id')->unique()->count(),
                    'check_out' => $daily->where('action', 'check_out')->pluck('internship_enrollment_id')->unique()->count(),
                    'valid_pairs' => $daily->groupBy('internship_enrollment_id')
                        ->filter(fn (Collection $items): bool => $items->where('action', 'check_in')->isNotEmpty() && $items->where('action', 'check_out')->isNotEmpty())
                        ->count(),
                ];
            })
            ->all();

        $statusKeys = $this->reportParticipantStatuses();
        $statusLabels = [
            'active' => 'Aktif',
            'completed' => 'Selesai',
        ];
        $statusByStudyProgram = $enrollments
            ->groupBy(fn (InternshipEnrollment $enrollment): string => (string) ($enrollment->study_program_id ?: 'none'))
            ->map(function (Collection $items, string $id) use ($statusKeys): array {
                $first = $items->first();

                return [
                    'id' => $id === 'none' ? null : (int) $id,
                    'name' => $first?->studyProgram?->name ?: 'Tanpa Prodi',
                    'total' => $items->count(),
                    'statuses' => collect($statusKeys)
                        ->mapWithKeys(fn (string $status): array => [$status => $items->where('status', $status)->count()])
                        ->all(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $reportStatusLabels = [
            'not_uploaded' => 'Belum unggah',
            'pending' => 'Menunggu review',
            'revision_required' => 'Perlu revisi',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];
        $reportStatus = collect(array_keys($reportStatusLabels))
            ->mapWithKeys(fn (string $status): array => [$status => 0])
            ->all();
        foreach ($enrollments as $enrollment) {
            $fullReport = $enrollment->submissionProgress
                ->where('deadline_type', 'full_report')
                ->sortByDesc('uploaded_at')
                ->first();
            $status = $fullReport?->status ?: 'not_uploaded';
            $reportStatus[$status] = ($reportStatus[$status] ?? 0) + 1;
        }
        $reportColors = [
            'not_uploaded' => '#94a3b8',
            'pending' => '#f59e0b',
            'revision_required' => '#fb923c',
            'approved' => '#10b981',
            'rejected' => '#ef4444',
        ];
        $reportTotal = max(1, array_sum($reportStatus));
        $reportOffset = 0;
        $reportGradient = collect($reportStatus)
            ->map(function (int $value, string $key) use (&$reportOffset, $reportTotal, $reportColors): string {
                $start = $reportOffset;
                $reportOffset += $value / $reportTotal * 100;

                return ($reportColors[$key] ?? '#64748b').' '.$start.'% '.$reportOffset.'%';
            })
            ->implode(', ');

        $topSanctions = $enrollments
            ->filter(fn (InternshipEnrollment $enrollment): bool => (int) $enrollment->total_sanctions_points > 0)
            ->sortByDesc('total_sanctions_points')
            ->take(10)
            ->map(fn (InternshipEnrollment $enrollment): array => [
                'student' => $enrollment->student?->full_name ?: '-',
                'npm' => $enrollment->student?->npm ?: '-',
                'study_program' => $enrollment->studyProgram?->name ?: '-',
                'points' => (int) $enrollment->total_sanctions_points,
            ])
            ->values()
            ->all();

        $assessmentProgress = [
            [
                'key' => 'lecturer',
                'label' => 'Nilai Dosen',
                'done' => $enrollments->filter(fn (InternshipEnrollment $enrollment): bool => $enrollment->seminarRequests->whereNotNull('seminar_score')->isNotEmpty())->count(),
            ],
            [
                'key' => 'field_supervisor',
                'label' => 'Nilai Pembimbing Lapangan',
                'done' => $enrollments->filter(fn (InternshipEnrollment $enrollment): bool => (bool) $enrollment->fieldSupervisorAssessment?->final_score)->count(),
            ],
            [
                'key' => 'final',
                'label' => 'Nilai Final',
                'done' => $enrollments->filter(fn (InternshipEnrollment $enrollment): bool => (bool) $enrollment->finalAssessment?->finalized_at)->count(),
            ],
        ];
        $assessmentProgress = collect($assessmentProgress)
            ->map(fn (array $row): array => $row + [
                'missing' => max(0, $enrollments->count() - $row['done']),
                'percent' => $enrollments->count() > 0 ? round($row['done'] / $enrollments->count() * 100, 1) : 0,
            ])
            ->all();

        return view('reports.operational-charts', [
            'periods' => $periods,
            'periodDateRanges' => $this->reportPeriodDateRanges($request, $periods),
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => $this->studyProgramOptions($request),
            'selectedPeriod' => $selectedPeriod?->id,
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totalEnrollments' => $enrollments->count(),
            'attendanceTrend' => $attendanceTrend,
            'statusKeys' => $statusKeys,
            'statusLabels' => $statusLabels,
            'statusByStudyProgram' => $statusByStudyProgram,
            'reportStatus' => $reportStatus,
            'reportStatusLabels' => $reportStatusLabels,
            'reportColors' => $reportColors,
            'reportGradient' => $reportGradient,
            'topSanctions' => $topSanctions,
            'assessmentProgress' => $assessmentProgress,
        ]);
    }

    public function sanctions(Request $request): View
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
                    ->where('sanction_points', '>', 0)
                    ->orderBy('checked_at'),
                'submissionProgress' => fn ($query) => $query
                    ->where('sanction_points', '>', 0)
                    ->whereBetween('uploaded_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                    ->orderBy('uploaded_at'),
                'sanctions' => fn ($query) => $query
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orderBy('date'),
                'finalAssessment',
            ]);

        $this->onlyReportParticipants($query);
        $this->scopeEnrollments($query, $request);

        $rows = $query->get()
            ->map(fn (InternshipEnrollment $enrollment): array => $this->sanctionReportRow($enrollment, $startDate, $endDate))
            ->filter(fn (array $row): bool => $row['total'] > 0 || ! $request->boolean('only_with_sanctions', true))
            ->sortByDesc('total')
            ->values();

        $totals = [
            'students' => $rows->count(),
            'attendance' => $rows->sum('attendance_sanctions'),
            'reports' => $rows->sum('report_sanctions'),
            'final_deduction' => $rows->sum('final_deduction'),
            'total' => $rows->sum('total'),
        ];

        return view('reports.sanctions', [
            'periods' => $periods,
            'periodDateRanges' => $this->reportPeriodDateRanges($request, $periods),
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => $this->studyProgramOptions($request),
            'selectedPeriod' => $selectedPeriod?->id,
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'onlyWithSanctions' => $request->boolean('only_with_sanctions', true),
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    public function finalScores(Request $request): View
    {
        [$periods, $selectedPeriod] = $this->periodOptions($request);
        $status = $request->string('status')->toString();

        $query = InternshipEnrollment::query()
            ->with([
                'student.user',
                'studyProgram',
                'internshipPeriod.program',
                'internshipPlace',
                'lecturerSupervisor',
                'finalAssessment',
                'fieldSupervisorAssessment',
                'seminarRequests' => fn ($query) => $query->latest('scheduled_at'),
            ]);

        $this->onlyReportParticipants($query);
        $this->scopeEnrollments($query, $request);

        $rows = $query->get()
            ->map(fn (InternshipEnrollment $enrollment): array => $this->finalScoreReportRow($enrollment))
            ->when($status === 'finalized', fn (Collection $rows): Collection => $rows->where('is_final', true)->values())
            ->when($status === 'pending', fn (Collection $rows): Collection => $rows->where('is_final', false)->values())
            ->sortBy([
                ['is_final', 'asc'],
                ['student', 'asc'],
            ])
            ->values();

        $totals = [
            'students' => $rows->count(),
            'finalized' => $rows->where('is_final', true)->count(),
            'pending' => $rows->where('is_final', false)->count(),
            'average_final_score' => round((float) $rows->where('is_final', true)->pluck('final_score')->filter(fn ($score) => $score !== null)->avg(), 2),
        ];

        return view('reports.final-scores', [
            'periods' => $periods,
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => $this->studyProgramOptions($request),
            'selectedPeriod' => $selectedPeriod?->id,
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'selectedStatus' => $status,
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    public function drillDown(Request $request): View
    {
        [$periods, $selectedPeriod] = $this->periodOptions($request);
        [$startDate, $endDate] = $this->dateRange($request, $selectedPeriod);

        $query = InternshipEnrollment::query()
            ->with([
                'student.user',
                'studyProgram',
                'internshipPeriod.program',
                'internshipPlace',
                'lecturerSupervisor',
                'lecturer',
                'checkIns' => fn ($query) => $query
                    ->whereBetween('checked_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                    ->orderBy('checked_at'),
                'forgottenAttendanceRequests',
                'submissionProgress',
                'seminarRequests',
                'fieldSupervisorAssessment',
                'finalAssessment',
                'sanctions' => fn ($query) => $query
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orderBy('date'),
            ]);

        $this->onlyReportParticipants($query);
        $this->scopeEnrollments($query, $request);

        $source = $request->string('source')->toString();
        $context = $this->drillDownContext($request);
        $rows = $query->get()
            ->filter(fn (InternshipEnrollment $enrollment): bool => $this->matchesDrillDown($enrollment, $request, $startDate, $endDate))
            ->map(fn (InternshipEnrollment $enrollment): array => $this->drillDownRow($enrollment))
            ->sortBy([
                ['risk_score', 'desc'],
                ['student', 'asc'],
            ])
            ->values();

        return view('reports.drill-down', [
            'periods' => $periods,
            'periodDateRanges' => $this->reportPeriodDateRanges($request, $periods),
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'studyPrograms' => $this->studyProgramOptions($request),
            'selectedPeriod' => $selectedPeriod?->id,
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'source' => $source,
            'context' => $context,
            'rows' => $rows,
            'canOperate' => $request->user()?->hasRole('admin') || $request->user()?->hasRole('koordinator'),
        ]);
    }

    private function sanctionReportRow(InternshipEnrollment $enrollment, Carbon $startDate, Carbon $endDate): array
    {
        $checkIns = $enrollment->checkIns
            ->filter(fn (CheckIn $checkIn): bool => $checkIn->checked_at?->betweenIncluded($startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()));
        $submissionProgress = $enrollment->submissionProgress
            ->filter(fn ($progress): bool => $progress->uploaded_at?->betweenIncluded($startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()));

        $attendanceSanctions = (float) $checkIns->sum('sanction_points');
        $reportSanctions = (float) max(
            $enrollment->sanctions->sum('points_deducted'),
            $submissionProgress->sum('sanction_points'),
        );
        $finalAssessment = $enrollment->finalAssessment;
        $finalDeduction = $finalAssessment?->finalized_at
            && $finalAssessment->finalized_at->betweenIncluded($startDate->copy()->startOfDay(), $endDate->copy()->endOfDay())
                ? (float) $finalAssessment->final_deduction
                : 0.0;

        return [
            'enrollment' => $enrollment,
            'student' => $enrollment->student?->full_name ?: '-',
            'npm' => $enrollment->student?->npm ?: '-',
            'study_program' => $enrollment->studyProgram?->name ?: '-',
            'period' => $enrollment->internshipPeriod?->display_name ?: '-',
            'place' => $enrollment->internshipPlace?->name ?: '-',
            'attendance_sanctions' => $attendanceSanctions,
            'report_sanctions' => $reportSanctions,
            'final_deduction' => $finalDeduction,
            'total' => $attendanceSanctions + $reportSanctions + $finalDeduction,
            'attendance_count' => $checkIns->count(),
            'report_count' => max($enrollment->sanctions->count(), $submissionProgress->count()),
            'finalized_at' => $finalAssessment?->finalized_at,
        ];
    }

    private function drillDownContext(Request $request): array
    {
        $sourceLabels = [
            'progress_funnel' => 'Progress Funnel',
            'risk_scoring' => 'Risk Scoring',
            'operational' => 'Grafik Operasional',
            'sanctions' => 'Rekap Sanksi',
            'final_scores' => 'Rekap Nilai Akhir',
        ];
        $source = $request->string('source')->toString();
        $title = $sourceLabels[$source] ?? 'Drill-down Laporan';
        $description = 'Daftar peserta sesuai titik data yang dipilih pada laporan.';

        if ($source === 'progress_funnel') {
            $stage = collect($this->progressFunnelStages())->firstWhere('key', $request->string('stage')->toString());
            $title = $stage ? 'Progress Funnel: '.$stage['label'] : $title;
            $description = $stage['description'] ?? $description;
        }

        if ($source === 'risk_scoring') {
            $riskLabels = [
                'safe' => 'Aman',
                'watch' => 'Perlu Dipantau',
                'risky' => 'Berisiko',
                'critical' => 'Kritis',
            ];
            $risk = $request->string('risk')->toString();
            $title = 'Risk Scoring: '.($riskLabels[$risk] ?? 'Semua kategori');
            $description = 'Peserta pada kategori risiko yang dipilih.';
        }

        if ($source === 'operational') {
            $metric = $request->string('metric')->toString();
            $metricLabels = [
                'attendance' => 'Tren Presensi',
                'full_report' => 'Status Laporan Lengkap',
                'enrollment_status' => 'Status Peserta',
                'sanctions' => 'Top Sanksi',
                'assessment' => 'Progress Status Nilai',
            ];
            $title = 'Grafik Operasional: '.($metricLabels[$metric] ?? 'Detail Grafik');
            $description = match ($metric) {
                'attendance' => 'Peserta pada tanggal presensi yang dipilih.',
                'full_report' => 'Peserta dengan status laporan lengkap yang dipilih.',
                'enrollment_status' => 'Peserta dengan status enrollment/prodi yang dipilih.',
                'sanctions' => 'Peserta dengan sanksi aktif sesuai filter.',
                'assessment' => 'Peserta berdasarkan status komponen nilai yang dipilih.',
                default => $description,
            };
        }

        if ($source === 'final_scores') {
            $title = 'Rekap Nilai Akhir: '.($request->string('status')->toString() === 'finalized' ? 'Sudah final' : 'Belum final');
            $description = 'Peserta sesuai status finalisasi nilai.';
        }

        if ($source === 'sanctions') {
            $title = 'Rekap Sanksi: Peserta dengan sanksi';
            $description = 'Peserta yang memiliki sanksi presensi, laporan, atau pengurangan final.';
        }

        return compact('title', 'description');
    }

    private function matchesDrillDown(InternshipEnrollment $enrollment, Request $request, Carbon $startDate, Carbon $endDate): bool
    {
        return match ($request->string('source')->toString()) {
            'progress_funnel' => $this->matchesProgressStage($enrollment, $request->string('stage')->toString()),
            'risk_scoring' => $this->matchesRiskCategory($enrollment, $request->string('risk')->toString()),
            'operational' => $this->matchesOperationalMetric($enrollment, $request, $startDate, $endDate),
            'sanctions' => $this->sanctionReportRow($enrollment, $startDate, $endDate)['total'] > 0,
            'final_scores' => $this->matchesFinalScoreStatus($enrollment, $request->string('status')->toString()),
            default => true,
        };
    }

    private function matchesProgressStage(InternshipEnrollment $enrollment, string $stage): bool
    {
        return match ($stage) {
            'approved' => true,
            'active_attendance' => $enrollment->checkIns->isNotEmpty(),
            'full_report' => $enrollment->submissionProgress
                ->where('deadline_type', 'full_report')
                ->where('status', 'approved')
                ->isNotEmpty(),
            'field_supervisor_score' => $enrollment->fieldSupervisorAssessment?->final_score !== null,
            'seminar' => $enrollment->seminarRequests
                ->filter(fn ($seminar): bool => in_array($seminar->status, ['scheduled', 'completed'], true)
                    || filled($seminar->scheduled_at)
                    || filled($seminar->completed_at))
                ->isNotEmpty(),
            'lecturer_score' => $enrollment->seminarRequests->whereNotNull('seminar_score')->isNotEmpty(),
            'final_score' => $enrollment->finalAssessment?->final_score !== null
                && filled($enrollment->finalAssessment?->finalized_at),
            default => true,
        };
    }

    private function matchesRiskCategory(InternshipEnrollment $enrollment, string $risk): bool
    {
        if ($risk === '') {
            return true;
        }

        return $this->riskScoring->score($enrollment)['category_key'] === $risk;
    }

    private function matchesOperationalMetric(InternshipEnrollment $enrollment, Request $request, Carbon $startDate, Carbon $endDate): bool
    {
        return match ($request->string('metric')->toString()) {
            'attendance' => $this->matchesAttendanceMetric($enrollment, $request),
            'full_report' => $this->latestFullReportStatus($enrollment) === $request->string('report_status')->toString(),
            'enrollment_status' => $enrollment->status === $request->string('status')->toString(),
            'sanctions' => $this->sanctionReportRow($enrollment, $startDate, $endDate)['total'] > 0,
            'assessment' => $this->matchesAssessmentMetric($enrollment, $request),
            default => true,
        };
    }

    private function matchesAttendanceMetric(InternshipEnrollment $enrollment, Request $request): bool
    {
        $date = $request->date('date')?->toDateString();
        if (! $date) {
            return false;
        }

        $daily = $enrollment->checkIns->filter(fn (CheckIn $checkIn): bool => $checkIn->checked_at?->toDateString() === $date);

        return match ($request->string('attendance_status')->toString()) {
            'check_in' => $daily->where('action', 'check_in')->isNotEmpty(),
            'check_out' => $daily->where('action', 'check_out')->isNotEmpty(),
            'valid_pairs' => $daily->where('action', 'check_in')->isNotEmpty() && $daily->where('action', 'check_out')->isNotEmpty(),
            default => $daily->isNotEmpty(),
        };
    }

    private function matchesAssessmentMetric(InternshipEnrollment $enrollment, Request $request): bool
    {
        $isDone = match ($request->string('assessment_type')->toString()) {
            'lecturer' => $enrollment->seminarRequests->whereNotNull('seminar_score')->isNotEmpty(),
            'field_supervisor' => $enrollment->fieldSupervisorAssessment?->final_score !== null,
            'final' => filled($enrollment->finalAssessment?->finalized_at),
            default => false,
        };

        return $request->string('state')->toString() === 'missing' ? ! $isDone : $isDone;
    }

    private function matchesFinalScoreStatus(InternshipEnrollment $enrollment, string $status): bool
    {
        $isFinal = filled($enrollment->finalAssessment?->finalized_at);

        return match ($status) {
            'finalized' => $isFinal,
            'pending' => ! $isFinal,
            default => true,
        };
    }

    private function drillDownRow(InternshipEnrollment $enrollment): array
    {
        $risk = $this->riskScoring->score($enrollment);
        $final = $this->finalScoreReportRow($enrollment);

        return [
            'enrollment' => $enrollment,
            'student' => $enrollment->student?->full_name ?: '-',
            'npm' => $enrollment->student?->npm ?: '-',
            'study_program' => $enrollment->studyProgram?->name ?: '-',
            'period' => $enrollment->internshipPeriod?->display_name ?: '-',
            'program' => $enrollment->internshipPeriod?->program?->name ?: '-',
            'place' => $enrollment->internshipPlace?->name ?: '-',
            'lecturer' => $enrollment->lecturerSupervisor?->name ?: $enrollment->lecturer_supervisor ?: '-',
            'status' => $enrollment->status,
            'risk_score' => $risk['score'],
            'risk_category' => $risk['category'],
            'risk_tone' => $risk['category_tone'],
            'main_issues' => $risk['main_issues'],
            'valid_attendance_days' => $risk['valid_attendance_days'],
            'incomplete_attendance_days' => $risk['incomplete_attendance_days'],
            'total_sanctions' => $risk['total_sanctions'],
            'full_report_status' => $this->latestFullReportStatus($enrollment),
            'lecturer_score' => $final['lecturer_score'],
            'field_supervisor_score' => $final['field_supervisor_score'],
            'final_score' => $final['final_score'],
            'letter_grade' => $final['letter_grade'],
            'is_final' => $final['is_final'],
        ];
    }

    private function latestFullReportStatus(InternshipEnrollment $enrollment): string
    {
        return $enrollment->submissionProgress
            ->where('deadline_type', 'full_report')
            ->sortByDesc('uploaded_at')
            ->first()
            ?->status ?: 'not_uploaded';
    }

    private function finalScoreReportRow(InternshipEnrollment $enrollment): array
    {
        $finalAssessment = $enrollment->finalAssessment;
        $latestSeminar = $enrollment->seminarRequests
            ->whereNotNull('seminar_score')
            ->sortByDesc('scored_at')
            ->sortByDesc('completed_at')
            ->first();
        $finalScore = $finalAssessment?->final_score !== null ? (float) $finalAssessment->final_score : null;

        return [
            'enrollment' => $enrollment,
            'student' => $enrollment->student?->full_name ?: '-',
            'npm' => $enrollment->student?->npm ?: '-',
            'study_program' => $enrollment->studyProgram?->name ?: '-',
            'period' => $enrollment->internshipPeriod?->display_name ?: '-',
            'place' => $enrollment->internshipPlace?->name ?: '-',
            'lecturer' => $enrollment->lecturerSupervisor?->name ?: $enrollment->lecturer_supervisor ?: '-',
            'lecturer_score' => $finalAssessment?->lecturer_score !== null
                ? (float) $finalAssessment->lecturer_score
                : ($latestSeminar?->seminar_score !== null ? (float) $latestSeminar->seminar_score : null),
            'field_supervisor_score' => $finalAssessment?->field_supervisor_score !== null
                ? (float) $finalAssessment->field_supervisor_score
                : ($enrollment->fieldSupervisorAssessment?->final_score !== null ? (float) $enrollment->fieldSupervisorAssessment->final_score : null),
            'base_score' => $finalAssessment?->base_score !== null ? (float) $finalAssessment->base_score : null,
            'final_deduction' => $finalAssessment?->final_deduction !== null ? (float) $finalAssessment->final_deduction : null,
            'final_score' => $finalScore,
            'letter_grade' => $finalAssessment?->letter_grade ?: ($finalScore !== null ? $this->letterGrade($finalScore) : null),
            'document_number' => $finalAssessment?->document_number,
            'finalized_at' => $finalAssessment?->finalized_at,
            'is_final' => (bool) $finalAssessment?->finalized_at,
        ];
    }

    private function letterGrade(float $score): string
    {
        return match (true) {
            $score >= 76 => 'A',
            $score >= 71 => 'B+',
            $score >= 66 => 'B',
            $score >= 61 => 'C+',
            $score >= 56 => 'C',
            default => 'BL',
        };
    }

    private function periodOptions(Request $request): array
    {
        $user = $request->user();

        if (! $user?->hasRole('mahasiswa')) {
            $periodQuery = InternshipPeriod::query()
                ->with('program')
                ->orderByDesc('is_active')
                ->orderByDesc('id');

            if (! $user?->hasRole('admin')) {
                $scopedEnrollmentQuery = InternshipEnrollment::query()->select('internship_period_id');
                $this->onlyReportParticipants($scopedEnrollmentQuery);
                $this->reportScope->applyEnrollmentScope($scopedEnrollmentQuery, $user);
                $periodQuery->whereIn('id', $scopedEnrollmentQuery->distinct());
            }

            $periods = $periodQuery->get();
            $selectedPeriod = $request->integer('period_id')
                ? $periods->firstWhere('id', $request->integer('period_id'))
                : null;

            return [$periods, $selectedPeriod];
        }

        $enrollments = InternshipEnrollment::query()
            ->with('internshipPeriod.program')
            ->whereHas('student', fn (Builder $query) => $query->where('user_id', $user->id))
            ->whereIn('status', $this->reportParticipantStatuses())
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

        return $this->dateRange($request, $referencePeriod);
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

        return $this->reportScope->applyEnrollmentScope($query, $user);
    }

    private function onlyReportParticipants(Builder $query): Builder
    {
        return $query->whereIn('status', $this->reportParticipantStatuses());
    }

    private function reportParticipantStatuses(): array
    {
        return ['active', 'completed'];
    }

    private function studyProgramOptions(Request $request): Collection
    {
        $query = StudyProgram::query()
            ->where('is_active', true)
            ->orderBy('name');

        $user = $request->user();

        if (! $user?->hasRole('admin')) {
            $scopedEnrollmentQuery = InternshipEnrollment::query()->select('study_program_id');
            $this->onlyReportParticipants($scopedEnrollmentQuery);
            $this->reportScope->applyEnrollmentScope($scopedEnrollmentQuery, $user);
            $query->whereIn('id', $scopedEnrollmentQuery->distinct());
        }

        return $query->get();
    }

    private function dateRange(Request $request, ?InternshipPeriod $selectedPeriod = null): array
    {
        $start = $request->date('start_date');
        $end = $request->date('end_date');

        if ((! $start || ! $end) && $selectedPeriod) {
            [$defaultStart, $defaultEnd] = $this->defaultAttendanceRange($request, $selectedPeriod);
            $start ??= $defaultStart;
            $end ??= $defaultEnd;
        }

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

        if ($end->lessThan($start)) {
            $end = $start->copy();
        }

        return [$start->startOfDay(), $end->startOfDay()];
    }

    private function defaultAttendanceRange(Request $request, InternshipPeriod $period): array
    {
        $query = InternshipEnrollment::query()
            ->where('internship_period_id', $period->id)
            ->when($request->filled('study_program_id'), fn (Builder $query) => $query->where('study_program_id', $request->integer('study_program_id')));
        $this->onlyReportParticipants($query);
        $this->reportScope->applyEnrollmentScope($query, $request->user());

        $enrollments = $query->get(['attendance_starts_at', 'attendance_ends_at', 'status']);

        $starts = $enrollments
            ->map(fn (InternshipEnrollment $enrollment) => $enrollment->attendance_starts_at ?? $period->starts_at)
            ->filter();
        $ends = $enrollments
            ->map(fn (InternshipEnrollment $enrollment) => $enrollment->attendance_ends_at ?? $period->ends_at)
            ->filter();

        $start = $starts->sortBy(fn (Carbon $date): string => $date->toDateString())->first()?->copy()
            ?? $period->starts_at?->copy()
            ?? now()->copy()->startOfYear();
        $end = $ends->sortByDesc(fn (Carbon $date): string => $date->toDateString())->first()?->copy()
            ?? $period->ends_at?->copy()
            ?? now()->copy();

        $today = now()->copy()->startOfDay();
        if (! $request->filled('end_date') && $end->greaterThan($today)) {
            $end = $today;
        }

        return [$start->startOfDay(), $end->startOfDay()];
    }

    private function reportPeriodDateRanges(Request $request, Collection $periods): array
    {
        return $periods
            ->mapWithKeys(function (InternshipPeriod $period) use ($request): array {
                [$start, $end] = $this->defaultAttendanceRange($request, $period);

                return [
                    $period->id => [
                        'start' => $start->toDateString(),
                        'end' => $end->toDateString(),
                    ],
                ];
            })
            ->all();
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
                'key' => 'field_supervisor_score',
                'label' => 'Nilai Pembimbing Lapangan Masuk',
                'description' => 'Form nilai pembimbing lapangan sudah dikirim.',
                'constraint' => fn (Builder $query) => $query->whereHas('fieldSupervisorAssessment', fn (Builder $assessment) => $assessment->whereNotNull('final_score')),
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
