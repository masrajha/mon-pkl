<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\OrientationEvent;
use App\Models\PeriodDeadline;
use App\Models\Sanction;
use App\Models\SubmissionProgress;
use App\Services\PeriodConfigurationService;
use App\Services\StudentWorkflowAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly StudentWorkflowAccessService $workflowAccess)
    {
    }

    public function show(Request $request, InternshipEnrollment $enrollment): View
    {
        logger()->info('report show start', [
            'enrollment_id' => $enrollment->id,
            'auth_id' => $request->user()?->id,
            'auth_email' => $request->user()?->email,
        ]);

        $this->authorizeEnrollment($request, $enrollment);

        logger()->info('report after authorize', [
            'enrollment_id' => $enrollment->id,
        ]);

        $enrollment->load([
            'student.user',
            'studyProgram',
            'internshipPeriod.program',
            'internshipPeriod.setting',
            'internshipPeriod.deadlines' => fn ($query) => $query
                ->whereIn('deadline_type', array_keys(config('monpkl.deadline_types')))
                ->orderBy('deadline_date'),
            'internshipPlace',
            'lecturer',
            'submissionProgress' => fn ($query) => $query->latest('uploaded_at'),
            'submissionProgress.reviewer',
            'seminarRequests' => fn ($query) => $query->latest('id'),
            'seminarRequests.lecturerApprover',
            'seminarRequests.manualAccValidator',
            'seminarRequests.scheduler',
            'seminarRequests.scorer',
            'sanctions' => fn ($query) => $query->latest('date'),
            'supervisorChangeRequests' => fn ($query) => $query->latest('id'),
            'checkIns' => fn ($query) => $query->orderBy('checked_at'),
        ]);

        logger()->info('report after load', [
            'enrollment_id' => $enrollment->id,
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
        $uploadableDeadlineLabels = collect($deadlineLabels)
            ->reject(fn ($label, $type) => $type === 'hardcopy' || $lockedDeadlineTypes->contains($type))
            ->all();
        $hardcopyProgress = $progressByType->get('hardcopy');
        $orientationEvents = OrientationEvent::query()
            ->with(['internshipPeriod.program', 'studyProgram'])
            ->with(['attendances' => fn ($query) => $query->where('student_id', $enrollment->student_id)])
            ->forEnrollment($enrollment)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get();

        logger()->info('report before view', [
            'enrollment_id' => $enrollment->id,
            'progress_count' => $enrollment->submissionProgress->count(),
            'seminar_count' => $enrollment->seminarRequests->count(),
            'orientation_event_count' => $orientationEvents->count(),
        ]);

        return view('student.reports.show', [
            'enrollment' => $enrollment,
            'progressByType' => $progressByType,
            'lockedDeadlineTypes' => $lockedDeadlineTypes,
            'uploadableDeadlineLabels' => $uploadableDeadlineLabels,
            'hardcopyProgress' => $hardcopyProgress,
            'canUploadHardcopy' => ! $lockedDeadlineTypes->contains('hardcopy'),
            'deadlineLabels' => $deadlineLabels,
            'deadlineTypeLabels' => config('monpkl.deadline_types'),
            'submissionNotes' => config('monpkl.report_submission_notes'),
            'dailyActivityRows' => $this->dailyActivityRows($enrollment),
            'seminarStatusLabels' => $this->seminarStatusLabels(),
            'canRequestSeminar' => $this->canRequestSeminar($enrollment),
            'seminarBlockedReason' => $this->seminarBlockedReason($enrollment),
            'seminarRubric' => config('monpkl.seminar_assessment_rubric', []),
            'orientationEvents' => $orientationEvents,
            'activeTab' => $this->activeTab($request),
            'canRequestRelocation' => $this->workflowAccess->relocationOpen($enrollment),
            'canRequestSupervisorChange' => $this->workflowAccess->supervisorChangeOpen($enrollment),
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
        logger()->info('report auth check', [
            'auth_id' => $request->user()?->id,
            'auth_email' => $request->user()?->email,
            'enrollment_id' => $enrollment->id,
            'student_id' => $enrollment->student_id,
            'student_user_id' => $enrollment->student?->user_id,
        ]);

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

    private function canRequestSeminar(InternshipEnrollment $enrollment): bool
    {
        return $this->seminarBlockedReason($enrollment) === null;
    }

    private function activeTab(Request $request): string
    {
        $tab = $request->string('tab')->toString();

        return in_array($tab, ['detail', 'pembekalan', 'presensi', 'pelaporan', 'seminar', 'penyelesaian'], true)
            ? $tab
            : 'detail';
    }

    private function seminarBlockedReason(InternshipEnrollment $enrollment): ?string
    {
        if (! in_array($enrollment->status, ['active', 'completed'], true)) {
            return 'Enrollment harus aktif atau selesai sebelum pengajuan seminar.';
        }

        if (! $enrollment->lecturer_supervisor_id && ! $enrollment->lecturer_supervisor_user_id) {
            return 'Dosen pembimbing wajib ditentukan sebelum pengajuan seminar.';
        }

        $hasFullReport = $enrollment->submissionProgress
            ->where('deadline_type', 'full_report')
            ->where('status', '!=', 'rejected')
            ->isNotEmpty();

        if (! $hasFullReport) {
            return 'Unggah Pelaporan Tahap 4 (Laporan Lengkap): Bab 1 s.d 5 terlebih dahulu.';
        }

        $activeSeminar = $enrollment->seminarRequests
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
            ->isNotEmpty();

        if ($activeSeminar) {
            return 'Masih ada pengajuan seminar aktif.';
        }

        return null;
    }

    private function seminarStatusLabels(): array
    {
        return [
            'waiting_lecturer_approval' => 'Menunggu ACC Dosen',
            'waiting_manual_acc_validation' => 'Menunggu Validasi ACC',
            'waiting_assessment_validation' => 'Menunggu Validasi Nilai Manual',
            'assessment_revision_required' => 'Revisi Nilai Manual',
            'lecturer_approved' => 'ACC Dosen',
            'manual_acc_approved' => 'ACC Manual Valid',
            'revision_required' => 'Perlu Revisi',
            'scheduled' => 'Terjadwal',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'rejected' => 'Ditolak',
        ];
    }
}
