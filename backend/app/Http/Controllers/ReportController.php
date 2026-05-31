<?php

namespace App\Http\Controllers;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\CheckIn;
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
        [$startDate, $endDate] = $this->dateRange($request);

        $query = InternshipEnrollment::query()
            ->with([
                'student.user',
                'studyProgram',
                'internshipPeriod',
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
            'periods' => InternshipPeriod::query()->orderByDesc('is_active')->orderByDesc('id')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedPeriod' => $request->integer('period_id') ?: null,
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

    private function summarizeEnrollment(InternshipEnrollment $enrollment, Request $request, Carbon $startDate, Carbon $endDate): ?array
    {
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);

        $allowedDates = collect(CarbonPeriod::create($startDate, $endDate))
            ->filter(fn (Carbon $date) => $this->isIncludedDate($date, $request, $settings))
            ->mapWithKeys(fn (Carbon $date) => [$date->toDateString() => true]);

        $daily = $enrollment->checkIns
            ->filter(fn ($checkIn) => isset($allowedDates[$checkIn->checked_at->toDateString()]))
            ->groupBy(fn ($checkIn) => $checkIn->checked_at->toDateString())
            ->map(fn ($items) => $this->summarizeDay($items->values(), $settings));

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
            'period' => $enrollment->internshipPeriod?->name,
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
        $first = $checkIns->first();
        $last = $checkIns->last();

        if ($checkIns->count() === 1) {
            $cutoff = Carbon::createFromFormat('H:i', $settings['report']['single_check_in_cutoff']);
            $hourMinute = (int) $first->checked_at->format('Hi');
            $cutoffHourMinute = (int) $cutoff->format('Hi');

            if ($hourMinute < $cutoffHourMinute) {
                $checkIn = $first->checked_at;
                $checkOut = $first->checked_at->copy()->setTime((int) $settings['report']['single_morning_checkout_hour'], (int) $first->checked_at->format('i'));
            } else {
                $checkIn = $first->checked_at->copy()->setTime((int) $settings['report']['single_afternoon_checkin_hour'], (int) $first->checked_at->format('i'));
                $checkOut = $first->checked_at;
            }
        } else {
            $checkIn = $first->checked_at;
            $checkOut = $last->checked_at;
        }

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

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        if ($user?->hasRole('admin')) {
            return $query;
        }

        if ($user?->hasRole('dosen')) {
            return $query->where(function (Builder $query) use ($user): void {
                if ($user->lecturer?->id) {
                    $query->where('lecturer_supervisor_id', $user->lecturer->id);
                }

                $query->orWhere('lecturer_supervisor_user_id', $user->id)
                    ->orWhere('lecturer_supervisor', $user->name)
                    ->orWhere('lecturer_supervisor', $user->email);
            });
        }

        return $query->whereHas('student', fn (Builder $query) => $query->where('user_id', $user?->id));
    }

    private function dateRange(Request $request): array
    {
        $start = $request->date('start_date');
        $end = $request->date('end_date');

        if (! $start || ! $end) {
            $firstCheckIn = CheckIn::query()->min('checked_at');
            $lastCheckIn = CheckIn::query()->max('checked_at');

            $start ??= $firstCheckIn ? Carbon::parse($firstCheckIn) : now()->startOfYear();
            $end ??= $lastCheckIn ? Carbon::parse($lastCheckIn) : now();
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
