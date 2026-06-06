<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\FieldSupervisorAccessToken;
use App\Models\FieldSupervisorAssessment;
use App\Models\InternshipEnrollment;
use App\Services\PeriodConfigurationService;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FieldSupervisorPortalController extends Controller
{
    public function token(Request $request, string $token): View
    {
        $accessToken = FieldSupervisorAccessToken::query()
            ->with($this->tokenRelations())
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_unless($accessToken->isValid(), 403, 'Token akses pembimbing lapangan tidak valid atau sudah kedaluwarsa.');

        $accessToken->forceFill(['last_accessed_at' => now()])->save();

        return $this->renderDashboard(collect([$accessToken->enrollment]), $accessToken->email, accessMode: 'token');
    }

    public function validateWithToken(Request $request, string $token, CheckIn $checkIn): RedirectResponse
    {
        $accessToken = FieldSupervisorAccessToken::query()
            ->with('enrollment')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_unless($accessToken->isValid(), 403, 'Token akses pembimbing lapangan tidak valid atau sudah kedaluwarsa.');
        abort_unless((int) $checkIn->internship_enrollment_id === (int) $accessToken->internship_enrollment_id, 403);

        $this->validateDailyLog($request, $checkIn, $accessToken->email, $accessToken->enrollment?->field_supervisor ?: $accessToken->email, 'token');

        return back()->with('status', 'Catatan harian berhasil divalidasi.');
    }

    public function bulkValidateWithToken(Request $request, string $token): RedirectResponse
    {
        $accessToken = FieldSupervisorAccessToken::query()
            ->with('enrollment')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_unless($accessToken->isValid(), 403, 'Token akses pembimbing lapangan tidak valid atau sudah kedaluwarsa.');

        $validatedCount = $this->bulkValidateDailyLogs(
            $request,
            (int) $accessToken->internship_enrollment_id,
            $accessToken->email,
            $accessToken->enrollment?->field_supervisor ?: $accessToken->email,
            'token'
        );

        return back()->with('status', $validatedCount.' catatan harian berhasil divalidasi.');
    }

    public function assessWithToken(Request $request, string $token, InternshipEnrollment $enrollment): RedirectResponse
    {
        $accessToken = FieldSupervisorAccessToken::query()
            ->with('enrollment.internshipPeriod')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_unless($accessToken->isValid(), 403, 'Token akses pembimbing lapangan tidak valid atau sudah kedaluwarsa.');
        abort_unless((int) $enrollment->id === (int) $accessToken->internship_enrollment_id, 403);

        $this->storeAssessment($request, $enrollment, $accessToken->email, $enrollment->field_supervisor ?: $accessToken->email, 'token');

        return back()->with('status', 'Nilai pembimbing lapangan berhasil disimpan.');
    }

    public function index(Request $request): View
    {
        $email = $this->loginEmail($request);
        $summaries = $this->supervisedEnrollments($email)
            ->get()
            ->map(fn (InternshipEnrollment $enrollment): array => $this->enrollmentSummary($enrollment));
        $dashboardSummaries = $summaries->whereIn('period_bucket', ['active', 'completed'])->values();
        $activeSummaries = $dashboardSummaries->where('period_bucket', 'active')->values();
        $completedSummaries = $dashboardSummaries->where('period_bucket', 'completed')->values();

        return view('field-supervisor.overview', [
            'email' => $email,
            'summaries' => $dashboardSummaries,
            'activeSummaries' => $activeSummaries,
            'completedSummaries' => $completedSummaries,
            'stats' => [
                'total_students' => $dashboardSummaries->count(),
                'active_students' => $activeSummaries->count(),
                'pending_validations' => $dashboardSummaries->sum('pending_daily_validations'),
                'pending_assessments' => $dashboardSummaries->where('can_assess', true)->where('has_assessment', false)->count(),
            ],
        ]);
    }

    public function enrollments(Request $request): View
    {
        $email = $this->loginEmail($request);
        $search = trim((string) $request->query('q', ''));
        $period = (string) $request->query('period', 'active');
        $summaries = $this->supervisedEnrollments($email)
            ->get()
            ->map(fn (InternshipEnrollment $enrollment): array => $this->enrollmentSummary($enrollment))
            ->filter(function (array $summary) use ($period, $search): bool {
                if (in_array($period, ['active', 'completed'], true) && $summary['period_bucket'] !== $period) {
                    return false;
                }

                if ($search === '') {
                    return true;
                }

                $haystack = Str::lower(implode(' ', [
                    $summary['student_name'],
                    $summary['student_npm'],
                    $summary['period_name'],
                    $summary['place_name'],
                    $summary['study_program'],
                ]));

                return str_contains($haystack, Str::lower($search));
            })
            ->values();

        return view('field-supervisor.enrollments.index', [
            'email' => $email,
            'period' => $period,
            'search' => $search,
            'summaries' => $summaries,
        ]);
    }

    public function show(Request $request, InternshipEnrollment $enrollment): View
    {
        $email = $this->loginEmail($request);
        $enrollment = $this->supervisedEnrollments($email)
            ->whereKey($enrollment->id)
            ->firstOrFail();

        return $this->renderDashboard(collect([$enrollment]), $email, accessMode: 'login', initialTab: $request->query('tab') === 'assessment' ? 'assessment' : 'daily');
    }

    public function validateDailyLogForLogin(Request $request, CheckIn $checkIn): RedirectResponse
    {
        $email = Str::lower(trim((string) $request->user()?->email));

        abort_unless(
            InternshipEnrollment::query()
                ->whereKey($checkIn->internship_enrollment_id)
                ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->exists(),
            403
        );

        $this->validateDailyLog($request, $checkIn, $email, $request->user()?->name ?: $email, 'login');

        return back()->with('status', 'Catatan harian berhasil divalidasi.');
    }

    public function bulkValidateDailyLogsForLogin(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $email = Str::lower(trim((string) $request->user()?->email));

        abort_unless(
            (int) InternshipEnrollment::query()
                ->whereKey($enrollment->id)
                ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->count() === 1,
            403
        );

        $validatedCount = $this->bulkValidateDailyLogs($request, $enrollment->id, $email, $request->user()?->name ?: $email, 'login');

        return back()->with('status', $validatedCount.' catatan harian berhasil divalidasi.');
    }

    public function assessForLogin(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $email = Str::lower(trim((string) $request->user()?->email));

        abort_unless(
            (int) InternshipEnrollment::query()
                ->whereKey($enrollment->id)
                ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->count() === 1,
            403
        );

        $enrollment->loadMissing('internshipPeriod');
        $enrollment->loadMissing('finalAssessment');

        if ($enrollment->finalAssessment) {
            throw ValidationException::withMessages([
                'assessment' => 'Nilai pembimbing lapangan sudah terkunci karena nilai akhir telah difinalisasi.',
            ]);
        }

        $this->storeAssessment($request, $enrollment, $email, $request->user()?->name ?: $email, 'login');

        return back()->with('status', 'Nilai pembimbing lapangan berhasil disimpan.');
    }

    private function renderDashboard($enrollments, string $email, string $accessMode, string $initialTab = 'daily'): View
    {
        $data = [
            'enrollments' => $enrollments,
            'email' => $email,
            'accessMode' => $accessMode,
            'initialTab' => $initialTab,
            'dailyRowsByEnrollment' => $enrollments->mapWithKeys(fn (InternshipEnrollment $enrollment) => [
                $enrollment->id => $this->dailyRows($enrollment),
            ]),
            'attendanceScoresByEnrollment' => $enrollments->mapWithKeys(fn (InternshipEnrollment $enrollment) => [
                $enrollment->id => $this->attendanceScore($enrollment),
            ]),
        ];

        if ($accessMode === 'login') {
            return view('field-supervisor.dashboard-auth', $data);
        }

        return view('field-supervisor.dashboard', $data);
    }

    private function loginEmail(Request $request): string
    {
        return Str::lower(trim((string) $request->user()?->email));
    }

    private function supervisedEnrollments(string $email)
    {
        return InternshipEnrollment::query()
            ->with($this->enrollmentRelations())
            ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->latest('id');
    }

    private function enrollmentSummary(InternshipEnrollment $enrollment): array
    {
        $dailyRows = $this->dailyRows($enrollment);
        $attendanceScore = $this->attendanceScore($enrollment);
        $canAssess = $this->canAssessEnrollment($enrollment);
        $assessment = $enrollment->fieldSupervisorAssessment;

        return [
            'enrollment' => $enrollment,
            'student_name' => $enrollment->student?->full_name ?: '-',
            'student_npm' => $enrollment->student?->npm ?: '-',
            'study_program' => $enrollment->studyProgram?->name ?: '-',
            'period_name' => $enrollment->internshipPeriod?->display_name ?: '-',
            'place_name' => $enrollment->internshipPlace?->name ?: '-',
            'lecturer_name' => $enrollment->lecturer?->name ?: '-',
            'status' => $enrollment->status,
            'period_bucket' => $this->periodBucket($enrollment),
            'attendance_range' => $this->dateRangeLabel($enrollment->effectiveAttendanceStartsAt(), $enrollment->effectiveAttendanceEndsAt()),
            'daily_total' => $dailyRows->count(),
            'daily_validated' => $dailyRows->filter(fn (array $row): bool => (bool) ($row['validation_check_in']?->daily_log_validated_at))->count(),
            'pending_daily_validations' => $dailyRows->filter(fn (array $row): bool => ! ($row['validation_check_in']?->daily_log_validated_at))->count(),
            'attendance_score' => $attendanceScore,
            'can_assess' => $canAssess,
            'has_assessment' => (bool) $assessment,
            'assessment_score' => $assessment?->final_score,
        ];
    }

    private function periodBucket(InternshipEnrollment $enrollment): string
    {
        $period = $enrollment->internshipPeriod;
        $timezone = (string) (config('monpkl.timezone') ?: 'Asia/Jakarta');
        $startsDate = $enrollment->effectiveAttendanceStartsAt()
            ? $this->dateString($enrollment->effectiveAttendanceStartsAt(), $timezone)
            : null;
        $endsDate = $enrollment->effectiveAttendanceEndsAt()
            ? $this->dateString($enrollment->effectiveAttendanceEndsAt(), $timezone)
            : null;
        $today = Carbon::today($timezone)->toDateString();

        if ($startsDate && $startsDate > $today) {
            return 'future';
        }

        if ($enrollment->status === 'completed' || $period?->is_locked || ($endsDate && $endsDate < $today)) {
            return 'completed';
        }

        return 'active';
    }

    private function dateRangeLabel($startsAt, $endsAt): string
    {
        if (! $startsAt && ! $endsAt) {
            return '-';
        }

        $format = fn ($date): string => $date
            ? Carbon::parse($date)->format('d/m/Y')
            : '-';

        return $format($startsAt).' - '.$format($endsAt);
    }

    private function enrollmentRelations(): array
    {
        return [
            'student',
            'studyProgram',
            'internshipPeriod.program',
            'internshipPlace',
            'lecturer',
            'fieldSupervisorAssessment',
            'finalAssessment',
            'forgottenAttendanceRequests' => fn ($query) => $query->latest('id'),
            'internshipPeriod.setting',
            'checkIns' => fn ($query) => $query->orderBy('checked_at'),
        ];
    }

    private function tokenRelations(): array
    {
        $relations = [];

        foreach ($this->enrollmentRelations() as $relation => $constraint) {
            if (is_int($relation)) {
                $relations[] = 'enrollment.'.$constraint;

                continue;
            }

            $relations['enrollment.'.$relation] = $constraint;
        }

        return $relations;
    }

    private function dailyRows(InternshipEnrollment $enrollment)
    {
        return $enrollment->checkIns
            ->groupBy(fn ($checkIn) => $checkIn->checked_at?->toDateString() ?: 'tanpa-tanggal')
            ->map(function ($items) {
                $checkIn = $items->firstWhere('action', 'check_in') ?: $items->first();
                $checkOut = $items->firstWhere('action', 'check_out') ?: $items->firstWhere('pair_id', $checkIn?->id);
                $validationRecord = $items
                    ->first(fn (CheckIn $item): bool => (bool) $item->daily_log_validated_at)
                    ?: $checkIn
                    ?: $checkOut;

                return [
                    'date' => $checkIn?->checked_at ?: $items->first()?->checked_at,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'validation_check_in' => $validationRecord,
                    'duration_minutes' => $checkOut?->duration_minutes ?? $checkIn?->duration_minutes,
                ];
            })
            ->sortByDesc('date')
            ->values();
    }

    private function validateDailyLog(Request $request, CheckIn $checkIn, string $email, string $name, string $mode): void
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->applyDailyLogValidation($checkIn, $email, $name, $mode, $data['note'] ?? null);
    }

    private function bulkValidateDailyLogs(Request $request, int $enrollmentId, string $email, string $name, string $mode): int
    {
        $data = $request->validate([
            'check_in_ids' => ['required', 'array', 'min:1'],
            'check_in_ids.*' => ['integer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $ids = collect($data['check_in_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'check_in_ids' => 'Pilih minimal satu catatan harian untuk divalidasi.',
            ]);
        }

        $checkIns = CheckIn::query()
            ->where('internship_enrollment_id', $enrollmentId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($checkIns->count() !== $ids->count()) {
            abort(403);
        }

        $validatedDates = collect();

        $ids->each(function (int $id) use ($checkIns, $email, $name, $mode, $data, $validatedDates): void {
            $checkIn = $checkIns->get($id);
            $date = $checkIn?->checked_at?->toDateString() ?: 'tanpa-tanggal-'.$id;

            if ($validatedDates->contains($date)) {
                return;
            }

            $this->applyDailyLogValidation($checkIn, $email, $name, $mode, $data['note'] ?? null);
            $validatedDates->push($date);
        });

        return $validatedDates->count();
    }

    private function applyDailyLogValidation(CheckIn $checkIn, string $email, string $name, string $mode, ?string $note): void
    {
        $checkedAt = $checkIn->checked_at;
        $dailyCheckIns = $checkedAt
            ? CheckIn::query()
                ->where('internship_enrollment_id', $checkIn->internship_enrollment_id)
                ->whereBetween('checked_at', [$checkedAt->copy()->startOfDay(), $checkedAt->copy()->endOfDay()])
                ->get()
            : collect([$checkIn]);

        $dailyCheckIns->each->forceFill([
            'daily_log_validated_at' => now(),
            'daily_log_validated_by_name' => $name,
            'daily_log_validated_by_email' => Str::lower(trim($email)),
            'daily_log_validation_mode' => $mode,
            'daily_log_validation_note' => $note,
        ]);

        $dailyCheckIns->each->save();
    }

    private function storeAssessment(Request $request, InternshipEnrollment $enrollment, string $email, string $name, string $mode): void
    {
        $enrollment->loadMissing('finalAssessment');

        if ($enrollment->finalAssessment) {
            throw ValidationException::withMessages([
                'assessment' => 'Nilai pembimbing lapangan sudah terkunci karena nilai akhir telah difinalisasi.',
            ]);
        }

        if (! $this->canAssessEnrollment($enrollment)) {
            throw ValidationException::withMessages([
                'assessment' => 'Penilaian pembimbing lapangan baru dapat dilakukan mulai tanggal akhir presensi/turun lapang.',
            ]);
        }

        $rubric = config('monpkl.field_supervisor_assessment_rubric', []);
        $survey = config('monpkl.field_supervisor_institution_feedback_survey', []);
        $attendanceScore = $this->attendanceScore($enrollment);
        $rules = [
            'note' => ['nullable', 'string', 'max:3000'],
            'student_general_note' => ['nullable', 'string', 'max:3000'],
            'student_recommendation' => ['nullable', 'string', 'max:3000'],
            'institution_note' => ['nullable', 'string', 'max:3000'],
        ];

        foreach (array_keys($rubric) as $key) {
            if ($key === 'attendance') {
                continue;
            }

            $rules['scores.'.$key] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        foreach ($survey as $key => $question) {
            $rules['institution_feedback.'.$key] = ['required', 'string', 'in:'.implode(',', array_keys($question['options'] ?? []))];
        }

        $data = $request->validate($rules);
        $scores = collect($rubric)
            ->mapWithKeys(fn (array $item, string $key): array => [$key => [
                'group' => $item['group'],
                'label' => $item['label'],
                'score' => $key === 'attendance'
                    ? $attendanceScore['score']
                    : round((float) data_get($data, 'scores.'.$key), 2),
            ]])
            ->all();
        $institutionFeedback = collect($survey)
            ->mapWithKeys(function (array $question, string $key) use ($data): array {
                $value = (string) data_get($data, 'institution_feedback.'.$key);
                $options = $question['options'] ?? [];

                return [$key => [
                    'label' => $question['label'],
                    'value' => $value,
                    'answer' => $options[$value] ?? $value,
                ]];
            })
            ->all();

        $disciplineScore = $this->averageScores($scores, ['attendance', 'rules_compliance']);
        $teamworkScore = $this->averageScores($scores, ['group_teamwork', 'other_teamwork', 'supervisor_teamwork']);
        $performanceScore = $this->averageScores($scores, ['innovation', 'task_ability', 'seriousness']);
        $finalScore = round(($disciplineScore + $teamworkScore + $performanceScore) / 3, 2);

        FieldSupervisorAssessment::query()->updateOrCreate(
            ['internship_enrollment_id' => $enrollment->id],
            [
                'scores' => $scores,
                'discipline_score' => $disciplineScore,
                'teamwork_score' => $teamworkScore,
                'performance_score' => $performanceScore,
                'final_score' => $finalScore,
                'note' => $data['note'] ?? null,
                'student_general_note' => $data['student_general_note'] ?? null,
                'student_recommendation' => $data['student_recommendation'] ?? null,
                'institution_feedback' => $institutionFeedback,
                'institution_note' => $data['institution_note'] ?? null,
                'assessed_by_name' => $name,
                'assessed_by_email' => Str::lower(trim($email)),
                'assessment_mode' => $mode,
                'assessed_at' => now(),
            ]
        );
    }

    private function averageScores(array $scores, array $keys): float
    {
        return round(collect($keys)->avg(fn (string $key): float => (float) data_get($scores, $key.'.score', 0)), 2);
    }

    private function attendanceScore(InternshipEnrollment $enrollment): array
    {
        $enrollment->loadMissing(['checkIns', 'internshipPeriod.setting']);

        $timezone = (string) (config('monpkl.timezone') ?: 'Asia/Jakarta');
        $startsAt = $enrollment->effectiveAttendanceStartsAt();
        $endsAt = $enrollment->effectiveAttendanceEndsAt();

        if (! $startsAt || ! $endsAt) {
            return [
                'score' => 0.0,
                'present_days' => 0,
                'working_days' => 0,
            ];
        }

        $startDate = Carbon::parse($this->dateString($startsAt, $timezone), $timezone)->startOfDay();
        $endDate = Carbon::parse($this->dateString($endsAt, $timezone), $timezone)->startOfDay();

        if ($endDate->lt($startDate)) {
            return [
                'score' => 0.0,
                'present_days' => 0,
                'working_days' => 0,
            ];
        }

        $settings = app(PeriodConfigurationService::class)->forPeriod($enrollment->internshipPeriod);
        $holidays = collect($settings['calendar']['holidays'] ?? [])
            ->filter()
            ->flip()
            ->all();

        $workingDates = collect(CarbonPeriod::create($startDate, $endDate))
            ->filter(fn ($date): bool => $this->isWorkingDate($date, $holidays))
            ->mapWithKeys(fn ($date): array => [$date->toDateString() => true]);

        $presentDays = $enrollment->checkIns
            ->filter(function (CheckIn $checkIn) use ($workingDates, $timezone): bool {
                if (! $checkIn->checked_at) {
                    return false;
                }

                return isset($workingDates[$checkIn->checked_at->copy()->timezone($timezone)->toDateString()]);
            })
            ->groupBy(fn (CheckIn $checkIn): string => $checkIn->checked_at->copy()->timezone($timezone)->toDateString())
            ->filter(function ($items): bool {
                $checkIn = $items->firstWhere('action', 'check_in');
                $checkOut = $items->firstWhere('action', 'check_out');

                return $checkIn
                    && $checkOut
                    && $checkIn->checked_at
                    && $checkOut->checked_at
                    && $checkOut->checked_at->gt($checkIn->checked_at);
            })
            ->count();

        $workingDays = $workingDates->count();

        return [
            'score' => $workingDays > 0 ? round(min(100, ($presentDays / $workingDays) * 100), 2) : 0.0,
            'present_days' => $presentDays,
            'working_days' => $workingDays,
        ];
    }

    private function isWorkingDate($date, array $holidays): bool
    {
        return ! $date->isWeekend() && ! isset($holidays[$date->toDateString()]);
    }

    private function canAssessEnrollment(InternshipEnrollment $enrollment): bool
    {
        $enrollment->loadMissing('internshipPeriod');

        $endsAt = $enrollment->effectiveAttendanceEndsAt();
        $timezone = (string) (config('monpkl.timezone') ?: 'Asia/Jakarta');
        $endsDate = $endsAt ? $this->dateString($endsAt, $timezone) : null;

        return $endsDate
            ? $endsDate <= Carbon::today($timezone)->toDateString()
            : false;
    }

    private function dateString(DateTimeInterface|string $date, string $timezone): string
    {
        return $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : Carbon::parse($date, $timezone)->toDateString();
    }
}
