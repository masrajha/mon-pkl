<?php

namespace App\Services;

use App\Models\EmailNotification;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\Lecturer;
use App\Models\PeriodDeadline;
use App\Models\SubmissionProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SubmissionProgressEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function uploaded(SubmissionProgress $progress): void
    {
        $progress->loadMissing(['enrollment.student.user', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'enrollment.lecturer.user']);

        $label = $this->deadlineLabels()[$progress->deadline_type] ?? Str::headline($progress->deadline_type);
        $lines = [
            'Unggahan laporan Anda berhasil dicatat dan menunggu review.',
            $this->progressContext($progress),
            'Status: Menunggu Review.',
        ];

        if ((int) $progress->sanction_points > 0) {
            $lines[] = 'Sanksi keterlambatan tercatat: '.$progress->sanction_points.' poin.';
        }

        $this->notifyStudent(
            $progress,
            'submission_progress.uploaded.student',
            '[SiLAT] Unggahan '.$label.' Berhasil',
            $lines,
            'submission-uploaded-student-'.$progress->id.'-'.$progress->updated_at?->timestamp,
        );

        $this->notifyLecturer(
            $progress,
            'submission_progress.uploaded.lecturer',
            '[SiLAT] Laporan Mahasiswa Menunggu Review',
            [
                'Ada unggahan laporan baru yang menunggu review dosen pembimbing.',
                $this->progressContext($progress),
                'Mohon lakukan review melalui SiLAT.',
            ],
            'submission-uploaded-lecturer-'.$progress->id.'-'.$progress->updated_at?->timestamp,
        );
    }

    public function reviewed(SubmissionProgress $progress): void
    {
        $progress->loadMissing(['enrollment.student.user', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'reviewer']);

        $label = $this->deadlineLabels()[$progress->deadline_type] ?? Str::headline($progress->deadline_type);
        $statusLabel = match ($progress->status) {
            'approved' => 'Disetujui',
            'revision_required' => 'Perlu Revisi',
            'rejected' => 'Ditolak',
            default => Str::headline($progress->status),
        };

        $lines = [
            'Hasil review laporan Anda: '.$statusLabel.'.',
            $this->progressContext($progress),
        ];

        if ($progress->lecturer_note) {
            $lines[] = 'Catatan reviewer: '.$progress->lecturer_note;
        }

        $lines[] = $progress->status === 'approved'
            ? 'Dokumen yang sudah disetujui terkunci dan tidak dapat direvisi lagi.'
            : 'Silakan unggah revisi melalui tab Pelaporan jika diperlukan.';

        $this->notifyStudent(
            $progress,
            'submission_progress.reviewed.'.$progress->status.'.student',
            '[SiLAT] '.$label.' '.$statusLabel,
            $lines,
            'submission-reviewed-'.$progress->status.'-'.$progress->id.'-'.$progress->updated_at?->timestamp,
        );
    }

    public function queueDeadlineReminders(array $days = [7, 3, 1, 0]): int
    {
        $queuedBefore = EmailNotification::query()->count();
        $today = now()->startOfDay();
        $types = array_keys($this->deadlineLabels());
        $targetDates = collect($days)
            ->map(fn (int $day) => $today->copy()->addDays($day)->toDateString())
            ->unique()
            ->values();

        PeriodDeadline::query()
            ->with('internshipPeriod.program')
            ->whereIn('deadline_type', $types)
            ->where(function (Builder $query) use ($targetDates): void {
                $targetDates->each(fn (string $date) => $query->orWhereDate('deadline_date', $date));
            })
            ->get()
            ->each(function (PeriodDeadline $deadline) use ($today): void {
                $dayDiff = $today->diffInDays($deadline->deadline_date->copy()->startOfDay(), false);

                $this->enrollmentsForDeadline($deadline)
                    ->filter(fn (InternshipEnrollment $enrollment) => ! $this->hasApprovedOrPendingProgress($enrollment, $deadline->deadline_type))
                    ->each(function (InternshipEnrollment $enrollment) use ($deadline, $dayDiff): void {
                        $this->notifyStudentForDeadline($enrollment, $deadline, $dayDiff);
                    });
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    public function queuePendingReviewReminders(int $hours = 48): int
    {
        $queuedBefore = EmailNotification::query()->count();

        SubmissionProgress::query()
            ->with(['enrollment.student', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'enrollment.lecturer.user'])
            ->where('status', 'pending')
            ->where('uploaded_at', '<=', now()->subHours($hours))
            ->get()
            ->each(function (SubmissionProgress $progress) use ($hours): void {
                $this->notifyLecturer(
                    $progress,
                    'submission_progress.pending_review.reminder.lecturer',
                    '[SiLAT] Reminder Laporan Pending Review',
                    [
                        'Laporan mahasiswa masih menunggu review lebih dari '.$hours.' jam.',
                        $this->progressContext($progress),
                        'Mohon segera lakukan review melalui SiLAT.',
                    ],
                    'submission-pending-review-'.$progress->id.'-'.$hours,
                );
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    public function queueReviewerSummaries(): int
    {
        $queuedBefore = EmailNotification::query()->count();
        $today = now()->toDateString();
        $types = array_keys($this->deadlineLabels());

        InternshipCoordinator::query()
            ->with(['lecturer.user', 'internshipPeriod.program', 'studyProgram'])
            ->where('status', 'active')
            ->get()
            ->each(function (InternshipCoordinator $coordinator) use ($types, $today): void {
                if (! $this->periodHasStarted($coordinator->internshipPeriod)) {
                    return;
                }

                $enrollments = InternshipEnrollment::query()
                    ->with(['student', 'studyProgram', 'internshipPeriod.program', 'sanctions'])
                    ->where('internship_period_id', $coordinator->internship_period_id)
                    ->where('study_program_id', $coordinator->study_program_id)
                    ->whereNotIn('status', ['cancelled', 'rejected'])
                    ->get();

                $missing = $enrollments->filter(fn (InternshipEnrollment $enrollment) => collect($types)
                    ->contains(fn (string $type) => ! $this->hasApprovedOrPendingProgress($enrollment, $type)));
                $pending = SubmissionProgress::query()
                    ->whereIn('internship_enrollment_id', $enrollments->pluck('id'))
                    ->where('status', 'pending')
                    ->count();
                $topSanctions = $enrollments
                    ->sortByDesc(fn (InternshipEnrollment $enrollment) => (int) $enrollment->total_sanctions_points)
                    ->take(5);

                if ($missing->isEmpty() && $pending === 0 && $topSanctions->sum('total_sanctions_points') <= 0) {
                    return;
                }

                $recipient = $this->coordinatorRecipient($coordinator);

                if (! $recipient) {
                    return;
                }

                $this->emails->queue(
                    type: 'submission_progress.summary.coordinator',
                    recipientEmail: $recipient['email'],
                    subject: '[SiLAT] Rekap Laporan Peserta Program',
                    bodyLines: $this->summaryLines($coordinator->internshipPeriod?->display_name, $coordinator->studyProgram?->name, $missing, $pending, $topSanctions),
                    recipientName: $recipient['name'],
                    actionText: 'Buka Review Laporan',
                    actionUrl: route('management.submission-progress.index', [
                        'period_id' => $coordinator->internship_period_id,
                    ]),
                    eventKey: 'submission-summary-coordinator-'.$coordinator->id.'-'.$today,
                );
            });

        Lecturer::query()
            ->with('user')
            ->where(function (Builder $query): void {
                $query->whereNotNull('email')
                    ->orWhereHas('user', fn (Builder $query) => $query->whereNotNull('email'));
            })
            ->whereHas('enrollments.internshipPeriod', fn (Builder $query) => $this->startedPeriodQuery($query))
            ->get()
            ->each(function (Lecturer $lecturer) use ($types, $today): void {
                $enrollments = InternshipEnrollment::query()
                    ->with(['student', 'studyProgram', 'internshipPeriod.program', 'sanctions'])
                    ->where('lecturer_supervisor_id', $lecturer->id)
                    ->whereHas('internshipPeriod', fn (Builder $query) => $this->startedPeriodQuery($query))
                    ->whereNotIn('status', ['cancelled', 'rejected'])
                    ->get();

                if ($enrollments->isEmpty()) {
                    return;
                }

                $missing = $enrollments->filter(fn (InternshipEnrollment $enrollment) => collect($types)
                    ->contains(fn (string $type) => ! $this->hasApprovedOrPendingProgress($enrollment, $type)));
                $pending = SubmissionProgress::query()
                    ->whereIn('internship_enrollment_id', $enrollments->pluck('id'))
                    ->where('status', 'pending')
                    ->count();
                $topSanctions = $enrollments
                    ->sortByDesc(fn (InternshipEnrollment $enrollment) => (int) $enrollment->total_sanctions_points)
                    ->take(5);

                if ($missing->isEmpty() && $pending === 0 && $topSanctions->sum('total_sanctions_points') <= 0) {
                    return;
                }

                $recipient = $this->lecturerRecipient($lecturer);

                if (! $recipient) {
                    return;
                }

                $this->emails->queue(
                    type: 'submission_progress.summary.lecturer',
                    recipientEmail: $recipient['email'],
                    subject: '[SiLAT] Rekap Laporan Mahasiswa Bimbingan',
                    bodyLines: $this->summaryLines('Semua periode aktif bimbingan', 'Mahasiswa bimbingan', $missing, $pending, $topSanctions),
                    recipientName: $recipient['name'],
                    actionText: 'Buka Review Laporan',
                    actionUrl: route('management.submission-progress.index', ['status' => 'pending']),
                    eventKey: 'submission-summary-lecturer-'.$lecturer->id.'-'.$today,
                );
            });

        User::query()
            ->where('role', 'admin')
            ->get(['name', 'email'])
            ->each(function (User $admin) use ($today): void {
                $pending = SubmissionProgress::query()
                    ->where('status', 'pending')
                    ->whereHas('enrollment.internshipPeriod', fn (Builder $query) => $this->startedPeriodQuery($query))
                    ->count();
                $missing = $this->missingSubmissionCount();
                $topSanctions = InternshipEnrollment::query()
                    ->with(['student', 'studyProgram', 'internshipPeriod.program'])
                    ->whereHas('internshipPeriod', fn (Builder $query) => $this->startedPeriodQuery($query))
                    ->where('total_sanctions_points', '>', 0)
                    ->orderByDesc('total_sanctions_points')
                    ->limit(5)
                    ->get();

                if ($missing === 0 && $pending === 0 && $topSanctions->isEmpty()) {
                    return;
                }

                $this->emails->queue(
                    type: 'submission_progress.summary.admin',
                    recipientEmail: $admin->email,
                    subject: '[SiLAT] Rekap Laporan dan Sanksi',
                    bodyLines: $this->summaryLines('Semua periode', 'Semua prodi', collect($missing > 0 ? range(1, $missing) : []), $pending, $topSanctions),
                    recipientName: $admin->name,
                    actionText: 'Buka Review Laporan',
                    actionUrl: route('management.submission-progress.index'),
                    eventKey: 'submission-summary-admin-'.$admin->id.'-'.$today,
                );
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    private function notifyStudentForDeadline(InternshipEnrollment $enrollment, PeriodDeadline $deadline, int $dayDiff): void
    {
        $label = $this->deadlineLabels()[$deadline->deadline_type] ?? Str::headline($deadline->deadline_type);
        $timeLabel = $dayDiff === 0 ? 'hari ini' : 'H-'.$dayDiff;

        $this->notifyStudent(
            $this->progressShell($enrollment, $deadline->deadline_type),
            'submission_progress.deadline.reminder.student',
            '[SiLAT] Reminder Deadline '.$label,
            [
                'Deadline '.$label.' jatuh tempo '.$timeLabel.' ('.$deadline->deadline_date?->format('d/m/Y').').',
                $this->enrollmentContext($enrollment),
                'Silakan unggah dokumen melalui tab Pelaporan sebelum batas waktu.',
            ],
            'submission-deadline-'.$deadline->id.'-'.$enrollment->id.'-'.$dayDiff,
        );
    }

    private function notifyStudent(SubmissionProgress $progress, string $type, string $subject, array $lines, string $eventKey): void
    {
        $enrollment = $progress->enrollment;
        $email = $enrollment?->student?->student_email ?: $enrollment?->student?->user?->email;

        if (! $email || ! $enrollment) {
            return;
        }

        $this->emails->queue(
            type: $type,
            recipientEmail: $email,
            subject: $subject,
            bodyLines: $lines,
            recipientName: $enrollment->student?->full_name,
            actionText: 'Buka Pelaporan',
            actionUrl: route('student.reports.show', ['enrollment' => $enrollment->id, 'tab' => 'pelaporan']),
            notifiable: $progress->exists ? $progress : $enrollment,
            eventKey: $eventKey,
        );
    }

    private function notifyLecturer(SubmissionProgress $progress, string $type, string $subject, array $lines, string $eventKey): void
    {
        $lecturer = $progress->enrollment?->lecturer;
        $email = $lecturer?->email ?: $lecturer?->user?->email;

        if (! $email) {
            return;
        }

        $this->emails->queue(
            type: $type,
            recipientEmail: $email,
            subject: $subject,
            bodyLines: $lines,
            recipientName: $lecturer?->name ?: $lecturer?->user?->name,
            actionText: 'Buka Review Laporan',
            actionUrl: route('management.submission-progress.index', ['status' => 'pending']),
            notifiable: $progress,
            eventKey: $eventKey.'-'.$email,
        );
    }

    private function enrollmentsForDeadline(PeriodDeadline $deadline): Collection
    {
        return InternshipEnrollment::query()
            ->with(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace'])
            ->where('internship_period_id', $deadline->internship_period_id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->get();
    }

    private function hasApprovedOrPendingProgress(InternshipEnrollment $enrollment, string $type): bool
    {
        return SubmissionProgress::query()
            ->where('internship_enrollment_id', $enrollment->id)
            ->where('deadline_type', $type)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
    }

    private function progressShell(InternshipEnrollment $enrollment, string $deadlineType): SubmissionProgress
    {
        $progress = new SubmissionProgress(['deadline_type' => $deadlineType]);
        $progress->setRelation('enrollment', $enrollment);

        return $progress;
    }

    private function progressContext(SubmissionProgress $progress): string
    {
        $label = $this->deadlineLabels()[$progress->deadline_type] ?? Str::headline($progress->deadline_type);

        return $this->enrollmentContext($progress->enrollment).' Jenis laporan: '.$label.'.';
    }

    private function enrollmentContext(InternshipEnrollment $enrollment): string
    {
        return sprintf(
            'Mahasiswa: %s (%s). Program/periode: %s. Prodi: %s. Mitra: %s.',
            $enrollment->student?->full_name ?: '-',
            $enrollment->student?->npm ?: '-',
            $enrollment->internshipPeriod?->display_name ?: '-',
            $enrollment->studyProgram?->name ?: '-',
            $enrollment->internshipPlace?->name ?: '-',
        );
    }

    private function summaryLines(?string $period, ?string $studyProgram, Collection $missing, int $pending, Collection $topSanctions): array
    {
        $lines = [
            'Scope: '.($period ?: '-').' / '.($studyProgram ?: '-').'.',
            'Belum unggah/masih kosong: '.$missing->count().'. Pending review: '.$pending.'.',
        ];

        if ($topSanctions->isNotEmpty()) {
            $lines[] = 'Sanksi tertinggi: '.$topSanctions
                ->map(fn (InternshipEnrollment $enrollment) => ($enrollment->student?->full_name ?: '-').' ('.$enrollment->total_sanctions_points.' poin)')
                ->join('; ').'.';
        }

        return $lines;
    }

    private function missingSubmissionCount(): int
    {
        $types = array_keys($this->deadlineLabels());

        return InternshipEnrollment::query()
            ->whereHas('internshipPeriod', fn (Builder $query) => $this->startedPeriodQuery($query))
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->get()
            ->sum(fn (InternshipEnrollment $enrollment) => collect($types)
                ->filter(fn (string $type) => ! $this->hasApprovedOrPendingProgress($enrollment, $type))
                ->count());
    }

    private function periodHasStarted($period): bool
    {
        return ! $period?->starts_at || $period->starts_at->copy()->startOfDay()->lte(now()->startOfDay());
    }

    private function startedPeriodQuery(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereNull('starts_at')
                ->orWhereDate('starts_at', '<=', now()->toDateString());
        });
    }

    private function coordinatorRecipient(InternshipCoordinator $coordinator): ?array
    {
        $email = $coordinator->lecturer?->email ?: $coordinator->lecturer?->user?->email;

        if (! $email) {
            return null;
        }

        return [
            'name' => $coordinator->lecturer?->name ?: $coordinator->lecturer?->user?->name ?: $email,
            'email' => Str::lower(trim($email)),
        ];
    }

    private function lecturerRecipient(Lecturer $lecturer): ?array
    {
        $email = $lecturer->email ?: $lecturer->user?->email;

        if (! $email) {
            return null;
        }

        return [
            'name' => $lecturer->name ?: $lecturer->user?->name ?: $email,
            'email' => Str::lower(trim($email)),
        ];
    }

    private function deadlineLabels(): array
    {
        return config('monpkl.report_submission_types');
    }
}
