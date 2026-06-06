<?php

namespace App\Services;

use App\Mail\SystemNotificationMail;
use App\Models\EmailNotification;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EmailNotificationService
{
    public function notificationsEnabled(): bool
    {
        $settings = SystemSetting::getValue('email_notifications', ['enabled' => true]);

        return (bool) ($settings['enabled'] ?? true);
    }

    public function categoryDefinitions(): array
    {
        return [
            'enrollment' => [
                'code' => 'EN-02',
                'label' => 'Pendaftaran Program',
                'prefix' => 'enrollment.',
                'summary' => 'Pengajuan pendaftaran, validasi, penolakan, dan reminder pendaftaran pending.',
                'implemented' => true,
            ],
            'place_proposal' => [
                'code' => 'EN-03',
                'label' => 'Usulan Mitra',
                'prefix' => 'place_proposal.',
                'summary' => 'Usulan mitra baru, keputusan review, dan pengingat usulan belum diproses.',
                'implemented' => true,
            ],
            'orientation' => [
                'code' => 'EN-04',
                'label' => 'Pembekalan',
                'prefix' => 'orientation.',
                'summary' => 'Event dibuka, reminder H-1/jam dekat kegiatan, presensi berhasil, belum presensi, dan rekap penutupan.',
                'implemented' => true,
            ],
            'attendance_digest' => [
                'code' => 'EN-05',
                'label' => 'Digest Presensi',
                'prefix' => 'attendance_digest.',
                'summary' => 'Digest mingguan mahasiswa dan ringkasan pola presensi bermasalah untuk pembimbing/koordinator.',
                'implemented' => true,
            ],
            'submission_progress' => [
                'code' => 'EN-06',
                'label' => 'Laporan dan Deadline',
                'prefix' => 'submission_progress.',
                'summary' => 'Reminder H-7/H-3/H-1/hari H, upload, review, revisi, penolakan, sanksi, dan rekap admin.',
                'implemented' => true,
            ],
            'supervisor_change' => [
                'code' => 'EN-07',
                'label' => 'Perubahan Pembimbing',
                'prefix' => 'supervisor_change.',
                'summary' => 'Pengajuan perubahan pembimbing, keputusan review, dan reminder pengajuan belum diproses.',
                'implemented' => true,
            ],
            'relocation' => [
                'code' => 'EN-08',
                'label' => 'Pindah Mitra',
                'prefix' => 'relocation.',
                'summary' => 'Pengajuan pindah mitra, keputusan review, dan reminder pengajuan belum diproses.',
                'implemented' => true,
            ],
            'field_supervisor' => [
                'code' => 'EN-09',
                'label' => 'Pembimbing Lapangan',
                'prefix' => 'field_supervisor.',
                'summary' => 'Token akses, reminder validasi catatan harian, Lupa Presensi, pengisian nilai, dan konfirmasi nilai.',
                'implemented' => true,
            ],
            'assessment' => [
                'code' => 'EN-10',
                'label' => 'Penilaian dan Finalisasi',
                'prefix' => 'assessment.',
                'summary' => 'Reminder penilaian dosen, konfirmasi nilai, kelengkapan komponen nilai, dan kesiapan finalisasi.',
                'implemented' => true,
            ],
            'operational' => [
                'code' => 'EN-11',
                'label' => 'Operasional Sistem',
                'prefix' => 'operational.',
                'summary' => 'Periode dibuat/dikunci, konfigurasi penting berubah, import selesai/gagal, dan error pengiriman.',
                'implemented' => true,
            ],
        ];
    }

    public function categorySettings(): array
    {
        $stored = SystemSetting::getValue('email_notification_categories');

        return collect($this->categoryDefinitions())
            ->mapWithKeys(fn (array $category, string $key) => [
                $key => (bool) ($stored[$key] ?? $category['implemented']),
            ])
            ->all();
    }

    public function categoryEnabledForType(string $type): bool
    {
        $categoryKey = $this->categoryKeyForType($type);

        if (! $categoryKey) {
            return true;
        }

        return (bool) ($this->categorySettings()[$categoryKey] ?? true);
    }

    public function queue(
        string $type,
        string $recipientEmail,
        string $subject,
        array $bodyLines,
        ?string $recipientName = null,
        ?string $actionText = null,
        ?string $actionUrl = null,
        ?Model $notifiable = null,
        array $payload = [],
        mixed $scheduledFor = null,
        ?string $eventKey = null,
    ): ?EmailNotification {
        if (! $this->categoryEnabledForType($type)) {
            return null;
        }

        $recipientEmail = Str::lower(trim($recipientEmail));
        $eventKey ??= $this->eventKey($type, $recipientEmail, $notifiable, $payload, $scheduledFor);

        return EmailNotification::query()->firstOrCreate(
            ['event_key' => $eventKey],
            [
                'type' => $type,
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'body_lines' => array_values(array_filter($bodyLines, fn ($line) => filled($line))),
                'action_text' => $actionText,
                'action_url' => $actionUrl,
                'notifiable_type' => $notifiable?->getMorphClass(),
                'notifiable_id' => $notifiable?->getKey(),
                'payload' => $payload,
                'scheduled_for' => $scheduledFor,
                'status' => 'pending',
            ],
        );
    }

    public function processDue(int $limit = 100): array
    {
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        if (! $this->notificationsEnabled()) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => EmailNotification::query()->due()->count(),
            ];
        }

        EmailNotification::query()
            ->due()
            ->orderBy('scheduled_for')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (EmailNotification $notification) use (&$sent, &$failed): void {
                if ($this->send($notification)) {
                    $sent++;

                    return;
                }

                $failed++;
            });

        return compact('sent', 'failed', 'skipped');
    }

    public function send(EmailNotification $notification): bool
    {
        if ($notification->status !== 'pending') {
            return false;
        }

        if (! $this->notificationsEnabled()) {
            return false;
        }

        $this->applyMailSettings();

        $notification->increment('attempts');

        try {
            Mail::to($notification->recipient_email, $notification->recipient_name)
                ->send(new SystemNotificationMail($notification));

            $notification->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();

            return true;
        } catch (Throwable $exception) {
            $notification->forceFill([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => Str::limit($exception->getMessage(), 2000),
            ])->save();

            report($exception);

            return false;
        }
    }

    public function mailSettings(): array
    {
        $stored = SystemSetting::getValue('mail_settings');
        $password = '';

        if (filled($stored['password_encrypted'] ?? null)) {
            try {
                $password = Crypt::decryptString($stored['password_encrypted']);
            } catch (Throwable) {
                $password = '';
            }
        }

        unset($stored['password_encrypted']);

        return array_replace([
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'password' => '',
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ], array_filter($stored, fn ($value) => $value !== null), ['password' => $password]);
    }

    public function applyMailSettings(): void
    {
        $settings = $this->mailSettings();

        config([
            'mail.default' => $settings['mailer'] ?: config('mail.default'),
            'mail.mailers.smtp.host' => $settings['host'] ?: config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => (int) ($settings['port'] ?: config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.encryption' => $settings['encryption'] ?: null,
            'mail.mailers.smtp.username' => $settings['username'] ?: null,
            'mail.from.address' => $settings['from_address'] ?: config('mail.from.address'),
            'mail.from.name' => $settings['from_name'] ?: config('mail.from.name'),
        ]);

        if (filled($settings['password'] ?? null)) {
            config(['mail.mailers.smtp.password' => $settings['password']]);
        }
    }

    private function eventKey(string $type, string $recipientEmail, ?Model $notifiable, array $payload, mixed $scheduledFor): string
    {
        $parts = [
            $type,
            $recipientEmail,
            $notifiable?->getMorphClass(),
            $notifiable?->getKey(),
            $scheduledFor ? (string) $scheduledFor : null,
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];

        return hash('sha256', implode('|', array_map(fn ($part) => (string) $part, $parts)));
    }

    private function categoryKeyForType(string $type): ?string
    {
        foreach ($this->categoryDefinitions() as $key => $category) {
            if (Str::startsWith($type, $category['prefix'])) {
                return $key;
            }
        }

        return null;
    }
}
