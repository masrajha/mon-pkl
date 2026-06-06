<?php

namespace App\Services;

use App\Models\EmailNotification;
use App\Models\InternshipPeriod;
use App\Models\User;
use Throwable;

class OperationalEmailNotificationService
{
    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function periodChanged(InternshipPeriod $period, string $event, ?User $actor = null, array $details = []): void
    {
        $period->loadMissing('program');
        $label = match ($event) {
            'created' => 'Periode Baru Dibuat',
            'updated' => 'Periode Diperbarui',
            'completed' => 'Periode Diselesaikan/Dikunci',
            default => 'Perubahan Periode',
        };

        $this->notifyAdmins(
            type: 'operational.period.'.$event,
            subject: '[SiLAT] '.$label,
            lines: [
                'Periode: '.$period->display_name.'.',
                'Status aktif: '.($period->is_active ? 'Ya' : 'Tidak').'. Terkunci: '.($period->is_locked ? 'Ya' : 'Tidak').'.',
                'Diproses oleh: '.($actor?->name ?: 'Sistem').'.',
                ...$details,
            ],
            actionUrl: route('management.periods.edit', $period),
            eventKeyPrefix: 'operational-period-'.$event.'-'.$period->id.'-'.$period->updated_at?->timestamp,
        );
    }

    public function configurationUpdated(InternshipPeriod $period, ?User $actor = null): void
    {
        $period->loadMissing('program');

        $this->notifyAdmins(
            type: 'operational.configuration.updated',
            subject: '[SiLAT] Konfigurasi Program Diperbarui',
            lines: [
                'Konfigurasi program/periode diperbarui.',
                'Periode: '.$period->display_name.'.',
                'Diproses oleh: '.($actor?->name ?: 'Sistem').'.',
            ],
            actionUrl: route('system-configurations.edit', $period),
            eventKeyPrefix: 'operational-config-updated-'.$period->id.'-'.$period->updated_at?->timestamp.'-'.now()->timestamp,
        );
    }

    public function importFinished(array $report, string $reportPath): void
    {
        $counts = collect($report['counts'] ?? [])
            ->map(fn ($count, string $key) => $key.': '.$count)
            ->values()
            ->join(', ');

        $this->notifyAdmins(
            type: 'operational.import.finished',
            subject: '[SiLAT] Import Firebase Selesai',
            lines: [
                'Import Firebase selesai.',
                'Mode dry-run: '.((bool) ($report['dry_run'] ?? false) ? 'Ya' : 'Tidak').'.',
                'Ringkasan data: '.$counts.'.',
                'Failed rows: '.count($report['failed'] ?? []).'. Warning: '.count($report['warnings'] ?? []).'.',
                'Report: '.$reportPath.'.',
            ],
            actionUrl: route('dashboard'),
            eventKeyPrefix: 'operational-import-finished-'.now()->timestamp,
        );
    }

    public function importFailed(string $sourcePath, Throwable $exception): void
    {
        $this->notifyAdmins(
            type: 'operational.import.failed',
            subject: '[SiLAT] Import Firebase Gagal',
            lines: [
                'Import Firebase gagal diproses.',
                'Source: '.$sourcePath.'.',
                'Error: '.str($exception->getMessage())->limit(500).'.',
            ],
            actionUrl: route('dashboard'),
            eventKeyPrefix: 'operational-import-failed-'.now()->timestamp,
        );
    }

    public function queueEmailFailureDigest(): int
    {
        $failed = EmailNotification::query()
            ->where('status', 'failed')
            ->whereDate('failed_at', now()->toDateString())
            ->count();

        if ($failed === 0) {
            return 0;
        }

        $before = EmailNotification::query()->count();

        $this->notifyAdmins(
            type: 'operational.email.failures',
            subject: '[SiLAT] Error Pengiriman Email',
            lines: [
                'Ada email sistem yang gagal dikirim hari ini.',
                'Jumlah gagal: '.$failed.'.',
                'Silakan periksa tab Antrean Email untuk melihat detail error dan melakukan retry.',
            ],
            actionUrl: route('email-notifications.index', ['status' => 'failed']),
            eventKeyPrefix: 'operational-email-failures-'.now()->toDateString(),
        );

        return EmailNotification::query()->count() - $before;
    }

    private function notifyAdmins(string $type, string $subject, array $lines, string $actionUrl, string $eventKeyPrefix): void
    {
        User::query()
            ->where('role', 'admin')
            ->get(['id', 'name', 'email'])
            ->each(function (User $admin) use ($type, $subject, $lines, $actionUrl, $eventKeyPrefix): void {
                $this->emails->queue(
                    type: $type,
                    recipientEmail: $admin->email,
                    subject: $subject,
                    bodyLines: $lines,
                    recipientName: $admin->name,
                    actionText: 'Buka SiLAT',
                    actionUrl: $actionUrl,
                    eventKey: $eventKeyPrefix.'-admin-'.$admin->id,
                );
            });
    }
}
