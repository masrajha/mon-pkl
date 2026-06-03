<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\PeriodDeadline;
use App\Models\Sanction;
use App\Models\SubmissionProgress;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function show(Request $request, InternshipEnrollment $enrollment): View
    {
        $this->authorizeEnrollment($request, $enrollment);

        $enrollment->load([
            'student.user',
            'studyProgram',
            'internshipPeriod.program',
            'internshipPeriod.deadlines' => fn ($query) => $query
                ->whereIn('deadline_type', array_keys(config('monpkl.deadline_types')))
                ->orderBy('deadline_date'),
            'internshipPlace',
            'lecturer',
            'submissionProgress' => fn ($query) => $query->latest('uploaded_at'),
            'submissionProgress.reviewer',
            'sanctions' => fn ($query) => $query->latest('date'),
            'supervisorChangeRequests' => fn ($query) => $query->latest('id'),
            'checkIns' => fn ($query) => $query->orderBy('checked_at'),
        ]);

        $deadlineLabels = $this->deadlineLabels();
        $progressByType = $enrollment->submissionProgress
            ->groupBy('deadline_type')
            ->map(fn ($items) => $items->sortByDesc('uploaded_at')->first());
        $lockedDeadlineTypes = $enrollment->submissionProgress
            ->where('status', 'approved')
            ->pluck('deadline_type')
            ->unique()
            ->values();

        return view('student.reports.show', [
            'enrollment' => $enrollment,
            'progressByType' => $progressByType,
            'lockedDeadlineTypes' => $lockedDeadlineTypes,
            'uploadableDeadlineLabels' => collect($deadlineLabels)->reject(fn ($label, $type) => $lockedDeadlineTypes->contains($type))->all(),
            'deadlineLabels' => $deadlineLabels,
            'deadlineTypeLabels' => config('monpkl.deadline_types'),
            'submissionNotes' => config('monpkl.report_submission_notes'),
            'dailyActivityRows' => $this->dailyActivityRows($enrollment),
        ]);
    }

    public function storeProgress(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeEnrollment($request, $enrollment);
        abort_unless($enrollment->status === 'active', 403);

        $data = $request->validate([
            'deadline_type' => ['required', Rule::in(array_keys($this->deadlineLabels()))],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        if ($data['deadline_type'] === 'seminar' && blank($enrollment->field_supervisor_email)) {
            return back()
                ->withErrors(['deadline_type' => 'Email pembimbing lapangan wajib dilengkapi sebelum mengajukan Seminar.'])
                ->withInput();
        }

        $approvedExists = SubmissionProgress::query()
            ->where('internship_enrollment_id', $enrollment->id)
            ->where('deadline_type', $data['deadline_type'])
            ->where('status', 'approved')
            ->exists();

        if ($approvedExists) {
            return back()
                ->withErrors(['deadline_type' => 'Dokumen yang sudah disetujui tidak dapat direvisi lagi.'])
                ->withInput();
        }

        $uploadedAt = now();
        $filePath = $request->file('file')->store('submission-progress', 'public');

        DB::transaction(function () use ($enrollment, $data, $uploadedAt, $filePath): void {
            $existingProgress = SubmissionProgress::query()
                ->where('internship_enrollment_id', $enrollment->id)
                ->where('deadline_type', $data['deadline_type'])
                ->latest('uploaded_at')
                ->first();

            if ($existingProgress) {
                $existingProgress->update([
                    'file_path' => $filePath,
                    'uploaded_at' => $uploadedAt,
                    'status' => 'pending',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);

                return;
            }

            $deadline = PeriodDeadline::query()
                ->where('internship_period_id', $enrollment->internship_period_id)
                ->where('deadline_type', $data['deadline_type'])
                ->first();
            $sanctionPoints = $this->lateSubmissionPenalty($deadline, $uploadedAt);

            $progress = SubmissionProgress::query()->create([
                'internship_enrollment_id' => $enrollment->id,
                'deadline_type' => $data['deadline_type'],
                'file_path' => $filePath,
                'uploaded_at' => $uploadedAt,
                'status' => 'pending',
                'sanction_points' => $sanctionPoints,
            ]);

            if ($sanctionPoints <= 0) {
                return;
            }

            Sanction::query()->create([
                'internship_enrollment_id' => $enrollment->id,
                'submission_progress_id' => $progress->id,
                'sanction_type' => 'late_submission',
                'points_deducted' => $sanctionPoints,
                'reason' => 'Unggahan '.$this->deadlineLabels()[$data['deadline_type']].' melewati deadline '.$deadline?->deadline_date?->format('d/m/Y').'.',
                'date' => $uploadedAt->toDateString(),
            ]);

            $enrollment->increment('total_sanctions_points', $sanctionPoints);
        });

        return back()->with('status', 'Progres laporan berhasil diunggah.');
    }

    public function printDailyLogs(Request $request, InternshipEnrollment $enrollment): View
    {
        $this->authorizeEnrollment($request, $enrollment);

        $enrollment->load(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'checkIns' => fn ($query) => $query->orderBy('checked_at')]);
        $dailyActivityRows = $this->dailyActivityRows($enrollment);

        return view('student.reports.daily-logs-print', compact('enrollment', 'dailyActivityRows'));
    }

    public function print(Request $request, InternshipEnrollment $enrollment): View
    {
        $this->authorizeEnrollment($request, $enrollment);

        $enrollment->load(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'lecturer', 'checkIns' => fn ($query) => $query->orderBy('checked_at'), 'submissionProgress']);

        $missing = collect([
            'Dosen pembimbing' => ! $enrollment->lecturer,
            'Pembimbing lapangan' => blank($enrollment->field_supervisor),
            'Mitra' => ! $enrollment->internshipPlace,
            'Koordinat mitra' => ! $enrollment->internshipPlace?->latitude || ! $enrollment->internshipPlace?->longitude,
        ])->filter()->keys();

        abort_if($missing->isNotEmpty(), 422, 'Data belum lengkap untuk cetak laporan: '.$missing->implode(', '));

        $attendanceRows = $this->dailyActivityRows($enrollment, false);
        $attendanceColorRules = $this->attendanceColorRules($enrollment);

        return view('student.reports.print', compact('enrollment', 'attendanceRows', 'attendanceColorRules'));
    }

    private function authorizeEnrollment(Request $request, InternshipEnrollment $enrollment): void
    {
        abort_if($enrollment->student?->user_id !== $request->user()->id, 403);
    }

    private function lateSubmissionPenalty(?PeriodDeadline $deadline, $uploadedAt): int
    {
        if (! $deadline || $uploadedAt->toDateString() <= $deadline->deadline_date->toDateString()) {
            return 0;
        }

        if ($deadline->is_fixed_penalty) {
            return (int) $deadline->penalty_points;
        }

        return $deadline->deadline_date->startOfDay()->diffInDays($uploadedAt->copy()->startOfDay()) * (int) $deadline->penalty_points;
    }

    private function dailyActivityRows(InternshipEnrollment $enrollment, bool $descending = true)
    {
        $checkIns = $enrollment->relationLoaded('checkIns')
            ? $enrollment->checkIns
            : $enrollment->checkIns()->orderBy('checked_at')->get();

        return $checkIns
            ->groupBy(fn ($checkIn) => $checkIn->checked_at?->toDateString())
            ->map(function ($items) {
                $checkIn = $items->firstWhere('action', 'check_in');
                $checkOut = $items->firstWhere('action', 'check_out');
                $legacyItems = $items->whereNull('action')->sortBy('checked_at')->values();

                if (! $checkIn && ! $checkOut && $legacyItems->count() >= 2) {
                    $checkIn = $legacyItems->first();
                    $checkOut = $legacyItems->last();
                }

                $durationMinutes = $checkOut?->duration_minutes;

                if ($durationMinutes === null && $checkIn && $checkOut) {
                    $durationMinutes = max(0, (int) $checkIn->checked_at->diffInMinutes($checkOut->checked_at));
                }

                return [
                    'date' => $checkIn?->checked_at ?: $checkOut?->checked_at,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'duration_minutes' => $durationMinutes,
                ];
            })
            ->filter(fn (array $row) => $row['check_in'] || $row['check_out'])
            ->when(
                $descending,
                fn ($rows) => $rows->sortByDesc(fn (array $row) => $row['date']),
                fn ($rows) => $rows->sortBy(fn (array $row) => $row['date']),
            )
            ->values();
    }

    private function attendanceColorRules(InternshipEnrollment $enrollment): array
    {
        $settings = app(PeriodConfigurationService::class)->forPeriod($enrollment->internshipPeriod);
        $schedule = collect($settings['check_in']['schedule'] ?? []);

        return [
            'check_in_success_before' => $this->scheduleTime($schedule, 'Masuk', 'end', '09:00'),
            'check_in_warning_before' => $this->scheduleTime($schedule, 'Datang Terlambat', 'end', '11:00'),
            'check_out_success_from' => $this->scheduleTime($schedule, 'Pulang', 'start', '16:00'),
            'check_out_warning_from' => $this->scheduleTime($schedule, 'Pulang Cepat', 'start', '13:00'),
        ];
    }

    private function scheduleTime($schedule, string $status, string $field, string $fallback): int
    {
        $slot = $schedule->first(fn (array $slot) => ($slot['status'] ?? null) === $status);

        return $this->timeToMinutes($slot[$field] ?? $fallback);
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, 0);

        return ((int) $hours * 60) + (int) $minutes;
    }

    private function deadlineLabels(): array
    {
        return config('monpkl.report_submission_types');
    }
}
