<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\EmailNotification;
use App\Models\FieldSupervisorAccessToken;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\PeriodDeadline;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FieldSupervisorEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function createTokenAndQueueAccess(InternshipEnrollment $enrollment, ?int $createdBy = null, int $days = 30): ?FieldSupervisorAccessToken
    {
        $enrollment->loadMissing(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace']);

        if (blank($enrollment->field_supervisor_email)) {
            return null;
        }

        $rawToken = Str::random(64);
        $email = Str::lower(trim($enrollment->field_supervisor_email));

        $accessToken = FieldSupervisorAccessToken::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'email' => $email,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addDays($days),
            'created_by' => $createdBy,
        ]);

        $this->queueAccessToken($enrollment, $accessToken, route('field-supervisor.token', $rawToken));

        return $accessToken;
    }

    public function queuePortalAccess(string $email, Collection $enrollments): void
    {
        $email = Str::lower(trim($email));
        $enrollments = $enrollments
            ->filter(fn (InternshipEnrollment $enrollment): bool => Str::lower(trim((string) $enrollment->field_supervisor_email)) === $email)
            ->values();

        if ($enrollments->isEmpty()) {
            return;
        }

        $first = $enrollments->first();
        $studentLines = $enrollments
            ->take(8)
            ->map(fn (InternshipEnrollment $enrollment): string => sprintf(
                '- %s (%s), %s, %s',
                $enrollment->student?->full_name ?: '-',
                $enrollment->student?->npm ?: '-',
                $enrollment->internshipPeriod?->display_name ?: '-',
                $enrollment->internshipPlace?->name ?: '-',
            ))
            ->all();

        if ($enrollments->count() > 8) {
            $studentLines[] = '- Dan '.($enrollments->count() - 8).' mahasiswa lain.';
        }

        $this->emails->queue(
            type: 'field_supervisor.portal_access',
            recipientEmail: $email,
            subject: '[SiLAT] Akses Portal Pembimbing Lapangan',
            bodyLines: array_merge([
                'Anda mendapatkan akses sebagai Pembimbing Lapangan pada SiLAT.',
                'Gunakan tombol berikut untuk masuk ke portal. Setelah login dengan email ini, Anda dapat melihat seluruh mahasiswa bimbingan yang terdaftar menggunakan email yang sama.',
                'Mahasiswa terkait:',
            ], $studentLines),
            recipientName: $first?->field_supervisor ?: $email,
            actionText: 'Buka Portal Pembimbing',
            actionUrl: route('field-supervisor.index'),
            eventKey: 'field-supervisor-portal-access-'.$email.'-'.now()->timestamp,
        );
    }

    public function assessmentStored(InternshipEnrollment $enrollment): void
    {
        $enrollment->loadMissing(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'fieldSupervisorAssessment']);

        if (! $enrollment->fieldSupervisorAssessment || blank($enrollment->field_supervisor_email)) {
            return;
        }

        $this->emails->queue(
            type: 'field_supervisor.assessment.stored',
            recipientEmail: $enrollment->field_supervisor_email,
            subject: '[SiLAT] Nilai Pembimbing Lapangan Tersimpan',
            bodyLines: [
                'Nilai Pembimbing Lapangan berhasil disimpan di SiLAT.',
                $this->enrollmentContext($enrollment),
                'Nilai akhir Pembimbing Lapangan: '.number_format((float) $enrollment->fieldSupervisorAssessment->final_score, 2, ',', '.').'.',
            ],
            recipientName: $enrollment->field_supervisor ?: $enrollment->field_supervisor_email,
            actionText: 'Buka Portal Pembimbing',
            actionUrl: route('field-supervisor.index'),
            notifiable: $enrollment,
            eventKey: 'field-supervisor-assessment-stored-'.$enrollment->id.'-'.$enrollment->fieldSupervisorAssessment->updated_at?->timestamp,
        );
    }

    public function queueExpiredTokenReplacements(): int
    {
        $queuedBefore = EmailNotification::query()->count();

        InternshipEnrollment::query()
            ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'fieldSupervisorAccessTokens'])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereNotNull('field_supervisor_email')
            ->where('field_supervisor_email', '!=', '')
            ->get()
            ->each(function (InternshipEnrollment $enrollment): void {
                $hasValidToken = $enrollment->fieldSupervisorAccessTokens
                    ->contains(fn (FieldSupervisorAccessToken $token) => $token->isValid());
                $hasExpiredToken = $enrollment->fieldSupervisorAccessTokens
                    ->contains(fn (FieldSupervisorAccessToken $token) => $token->revoked_at === null && $token->expires_at->isPast());

                if (! $hasValidToken && $hasExpiredToken) {
                    $this->createTokenAndQueueAccess($enrollment);
                }
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    public function queueDailyLogValidationReminders(int $minimumPendingDays = 1): int
    {
        $queuedBefore = EmailNotification::query()->count();

        $this->activeFieldSupervisorEnrollments()
            ->get()
            ->each(function (InternshipEnrollment $enrollment) use ($minimumPendingDays): void {
                $pending = $this->pendingDailyRows($enrollment);

                if ($pending->count() < $minimumPendingDays || blank($enrollment->field_supervisor_email)) {
                    return;
                }

                $this->emails->queue(
                    type: 'field_supervisor.daily_log.reminder',
                    recipientEmail: $enrollment->field_supervisor_email,
                    subject: '[SiLAT] Reminder Validasi Catatan Harian',
                    bodyLines: [
                        'Ada catatan harian mahasiswa yang belum divalidasi.',
                        $this->enrollmentContext($enrollment),
                        'Jumlah catatan belum divalidasi: '.$pending->count().'.',
                        'Mohon buka portal Pembimbing Lapangan untuk melakukan validasi.',
                    ],
                    recipientName: $enrollment->field_supervisor ?: $enrollment->field_supervisor_email,
                    actionText: 'Buka Portal Pembimbing',
                    actionUrl: route('field-supervisor.index'),
                    notifiable: $enrollment,
                    eventKey: 'field-supervisor-daily-reminder-'.$enrollment->id.'-'.now()->toDateString(),
                );
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    public function queueAssessmentReminders(): int
    {
        $queuedBefore = EmailNotification::query()->count();

        $this->activeFieldSupervisorEnrollments()
            ->whereDoesntHave('fieldSupervisorAssessment')
            ->get()
            ->filter(fn (InternshipEnrollment $enrollment) => $this->canAssessEnrollment($enrollment))
            ->each(function (InternshipEnrollment $enrollment): void {
                if (blank($enrollment->field_supervisor_email)) {
                    return;
                }

                $this->emails->queue(
                    type: 'field_supervisor.assessment.reminder',
                    recipientEmail: $enrollment->field_supervisor_email,
                    subject: '[SiLAT] Reminder Pengisian Nilai Pembimbing Lapangan',
                    bodyLines: [
                        'Form nilai Pembimbing Lapangan sudah dapat diisi.',
                        $this->enrollmentContext($enrollment),
                        'Mohon lengkapi penilaian dan feedback institusi melalui portal Pembimbing Lapangan.',
                    ],
                    recipientName: $enrollment->field_supervisor ?: $enrollment->field_supervisor_email,
                    actionText: 'Buka Form Nilai',
                    actionUrl: route('field-supervisor.index'),
                    notifiable: $enrollment,
                    eventKey: 'field-supervisor-assessment-reminder-'.$enrollment->id.'-'.now()->toDateString(),
                );
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    public function queueFullReportDeadlineReminders(array $days = [7, 3, 1]): int
    {
        $queuedBefore = EmailNotification::query()->count();
        $today = now()->startOfDay();
        $targetDates = collect($days)
            ->map(fn (int $day) => $today->copy()->addDays($day)->toDateString())
            ->unique()
            ->values();

        PeriodDeadline::query()
            ->with('internshipPeriod.program')
            ->where('deadline_type', 'full_report')
            ->where(function (Builder $query) use ($targetDates): void {
                $targetDates->each(fn (string $date) => $query->orWhereDate('deadline_date', $date));
            })
            ->get()
            ->each(function (PeriodDeadline $deadline) use ($today): void {
                $dayDiff = $today->diffInDays($deadline->deadline_date->copy()->startOfDay(), false);

                $this->activeFieldSupervisorEnrollments()
                    ->where('internship_period_id', $deadline->internship_period_id)
                    ->whereDoesntHave('finalAssessment')
                    ->get()
                    ->each(function (InternshipEnrollment $enrollment) use ($deadline, $dayDiff): void {
                        if (blank($enrollment->field_supervisor_email)) {
                            return;
                        }

                        $pendingDaily = $this->pendingDailyRows($enrollment)->count();
                        if ($pendingDaily > 0) {
                            $this->emails->queue(
                                type: 'field_supervisor.full_report_deadline.daily_log_reminder',
                                recipientEmail: $enrollment->field_supervisor_email,
                                subject: '[SiLAT] H-'.$dayDiff.' Deadline Laporan: Validasi Catatan Harian',
                                bodyLines: [
                                    'Deadline laporan lengkap/tahap 4 jatuh tempo H-'.$dayDiff.' ('.$deadline->deadline_date?->format('d/m/Y').').',
                                    $this->enrollmentContext($enrollment),
                                    'Masih ada '.$pendingDaily.' catatan harian yang belum divalidasi.',
                                    'Mohon buka portal Pembimbing Lapangan untuk menyelesaikan validasi.',
                                ],
                                recipientName: $enrollment->field_supervisor ?: $enrollment->field_supervisor_email,
                                actionText: 'Buka Catatan Harian',
                                actionUrl: route('field-supervisor.index'),
                                notifiable: $enrollment,
                                eventKey: 'field-supervisor-full-report-daily-'.$deadline->id.'-'.$enrollment->id.'-'.$dayDiff,
                            );
                        }

                        if (! $enrollment->fieldSupervisorAssessment) {
                            $this->emails->queue(
                                type: 'field_supervisor.full_report_deadline.assessment_reminder',
                                recipientEmail: $enrollment->field_supervisor_email,
                                subject: '[SiLAT] H-'.$dayDiff.' Deadline Laporan: Nilai Pembimbing Lapangan',
                                bodyLines: [
                                    'Deadline laporan lengkap/tahap 4 jatuh tempo H-'.$dayDiff.' ('.$deadline->deadline_date?->format('d/m/Y').').',
                                    $this->enrollmentContext($enrollment),
                                    'Nilai Pembimbing Lapangan belum tersimpan.',
                                    'Jika form nilai sudah terbuka, mohon lengkapi penilaian dan feedback institusi.',
                                ],
                                recipientName: $enrollment->field_supervisor ?: $enrollment->field_supervisor_email,
                                actionText: 'Buka Form Nilai',
                                actionUrl: route('field-supervisor.index'),
                                notifiable: $enrollment,
                                eventKey: 'field-supervisor-full-report-assessment-'.$deadline->id.'-'.$enrollment->id.'-'.$dayDiff,
                            );
                        }
                    });
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    public function queueForgottenAttendanceReminders(): int
    {
        $queuedBefore = EmailNotification::query()->count();

        $this->activeFieldSupervisorEnrollments()
            ->whereHas('forgottenAttendanceRequests', fn (Builder $query) => $query->where('status', 'pending'))
            ->get()
            ->each(function (InternshipEnrollment $enrollment): void {
                $pending = $enrollment->forgottenAttendanceRequests
                    ->where('status', 'pending')
                    ->count();

                if ($pending === 0 || blank($enrollment->field_supervisor_email)) {
                    return;
                }

                $this->emails->queue(
                    type: 'field_supervisor.forgotten_attendance.reminder',
                    recipientEmail: $enrollment->field_supervisor_email,
                    subject: '[SiLAT] Reminder Pengajuan Lupa Presensi',
                    bodyLines: [
                        'Ada pengajuan Lupa Presensi mahasiswa yang belum diproses.',
                        $this->enrollmentContext($enrollment),
                        'Jumlah pengajuan pending: '.$pending.'.',
                        'Mohon buka portal Pembimbing Lapangan untuk menyetujui atau menolak pengajuan.',
                    ],
                    recipientName: $enrollment->field_supervisor ?: $enrollment->field_supervisor_email,
                    actionText: 'Buka Lupa Presensi',
                    actionUrl: route('field-supervisor.index'),
                    notifiable: $enrollment,
                    eventKey: 'field-supervisor-forgotten-attendance-'.$enrollment->id.'-'.now()->toDateString(),
                );
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    public function queueReviewerAlerts(): int
    {
        $queuedBefore = EmailNotification::query()->count();
        $today = now()->toDateString();

        $problemEnrollments = $this->activeFieldSupervisorEnrollments()
            ->where(function (Builder $query): void {
                $query->whereDoesntHave('fieldSupervisorAssessment')
                    ->orWhereHas('fieldSupervisorAccessTokens', fn (Builder $tokens) => $tokens
                        ->whereNull('revoked_at')
                        ->where('expires_at', '<', now()));
            })
            ->get()
            ->filter(fn (InternshipEnrollment $enrollment) => $this->canAssessEnrollment($enrollment)
                || $enrollment->fieldSupervisorAccessTokens->contains(fn (FieldSupervisorAccessToken $token) => $token->revoked_at === null && $token->expires_at->isPast()))
            ->values();

        if ($problemEnrollments->isEmpty()) {
            return 0;
        }

        User::query()
            ->where('role', 'admin')
            ->get(['id', 'name', 'email'])
            ->each(function (User $admin) use ($problemEnrollments, $today): void {
                $this->emails->queue(
                    type: 'field_supervisor.alert.admin',
                    recipientEmail: $admin->email,
                    subject: '[SiLAT] Rekap Kendala Pembimbing Lapangan',
                    bodyLines: $this->alertLines($problemEnrollments),
                    recipientName: $admin->name,
                    actionText: 'Buka Peserta Periode',
                    actionUrl: route('management.enrollments.index'),
                    eventKey: 'field-supervisor-alert-admin-'.$admin->id.'-'.$today,
                );
            });

        InternshipCoordinator::query()
            ->with('lecturer.user')
            ->where('status', 'active')
            ->get()
            ->each(function (InternshipCoordinator $coordinator) use ($problemEnrollments, $today): void {
                $items = $problemEnrollments
                    ->filter(fn (InternshipEnrollment $enrollment) => $enrollment->internship_period_id === $coordinator->internship_period_id
                        && $enrollment->study_program_id === $coordinator->study_program_id)
                    ->values();

                if ($items->isEmpty()) {
                    return;
                }

                $email = $coordinator->lecturer?->email ?: $coordinator->lecturer?->user?->email;

                if (! $email) {
                    return;
                }

                $this->emails->queue(
                    type: 'field_supervisor.alert.coordinator',
                    recipientEmail: $email,
                    subject: '[SiLAT] Rekap Kendala Pembimbing Lapangan',
                    bodyLines: $this->alertLines($items),
                    recipientName: $coordinator->lecturer?->name ?: $coordinator->lecturer?->user?->name,
                    actionText: 'Buka Peserta Periode',
                    actionUrl: route('management.enrollments.index', [
                        'period_id' => $coordinator->internship_period_id,
                        'study_program_id' => $coordinator->study_program_id,
                    ]),
                    eventKey: 'field-supervisor-alert-coordinator-'.$coordinator->id.'-'.$today,
                );
            });

        return EmailNotification::query()->count() - $queuedBefore;
    }

    private function queueAccessToken(InternshipEnrollment $enrollment, FieldSupervisorAccessToken $accessToken, string $url): void
    {
        $this->emails->queue(
            type: 'field_supervisor.access_token',
            recipientEmail: $accessToken->email,
            subject: '[SiLAT] Akses Pembimbing Lapangan',
            bodyLines: [
                'Anda mendapatkan akses sebagai Pembimbing Lapangan pada SiLAT.',
                $this->enrollmentContext($enrollment),
                'Tautan ini berlaku sampai '.$accessToken->expires_at->format('d/m/Y H:i').' dan hanya membuka data mahasiswa terkait.',
            ],
            recipientName: $enrollment->field_supervisor ?: $accessToken->email,
            actionText: 'Buka Portal Pembimbing',
            actionUrl: $url,
            notifiable: $enrollment,
            eventKey: 'field-supervisor-access-'.$accessToken->id,
        );
    }

    private function activeFieldSupervisorEnrollments(): Builder
    {
        return InternshipEnrollment::query()
            ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPeriod.setting', 'internshipPlace', 'checkIns', 'fieldSupervisorAssessment', 'fieldSupervisorAccessTokens', 'forgottenAttendanceRequests'])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereNotNull('field_supervisor_email')
            ->where('field_supervisor_email', '!=', '');
    }

    private function pendingDailyRows(InternshipEnrollment $enrollment): Collection
    {
        return $enrollment->checkIns
            ->groupBy(fn (CheckIn $checkIn) => $checkIn->checked_at?->toDateString() ?: 'tanpa-tanggal-'.$checkIn->id)
            ->filter(fn (Collection $items) => $items->every(fn (CheckIn $checkIn) => ! $checkIn->daily_log_validated_at));
    }

    private function alertLines(Collection $enrollments): array
    {
        $lines = ['Daftar mahasiswa yang perlu ditindaklanjuti terkait Pembimbing Lapangan.'];

        $enrollments->take(10)->each(function (InternshipEnrollment $enrollment) use (&$lines): void {
            $expiredTokens = $enrollment->fieldSupervisorAccessTokens
                ->filter(fn (FieldSupervisorAccessToken $token) => $token->revoked_at === null && $token->expires_at->isPast())
                ->count();
            $missingAssessment = $this->canAssessEnrollment($enrollment) && ! $enrollment->fieldSupervisorAssessment;

            $issues = collect([
                $missingAssessment ? 'nilai belum diisi' : null,
                $expiredTokens > 0 ? 'token kedaluwarsa '.$expiredTokens.' kali' : null,
            ])->filter()->join(', ');

            $lines[] = ($enrollment->student?->full_name ?: '-').' ('.($enrollment->student?->npm ?: '-').'): '.$issues.'.';
        });

        if ($enrollments->count() > 10) {
            $lines[] = 'Dan '.($enrollments->count() - 10).' mahasiswa lain.';
        }

        return $lines;
    }

    private function canAssessEnrollment(InternshipEnrollment $enrollment): bool
    {
        $endsAt = $enrollment->effectiveAttendanceEndsAt();
        $timezone = (string) (config('monpkl.timezone') ?: 'Asia/Jakarta');
        $endsDate = $endsAt ? $this->dateString($endsAt, $timezone) : null;

        return $endsDate ? $endsDate <= Carbon::today($timezone)->toDateString() : false;
    }

    private function dateString(DateTimeInterface|string $date, string $timezone): string
    {
        return $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : Carbon::parse($date, $timezone)->toDateString();
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
}
