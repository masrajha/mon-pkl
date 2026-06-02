<?php

namespace App\Http\Controllers;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\CheckIn;
use App\Models\Program;
use App\Models\StudyProgram;
use App\Services\PeriodConfigurationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
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
}
