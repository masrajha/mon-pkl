<?php

namespace App\Services;

use App\Mail\SystemNotificationMail;
use App\Models\EmailNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EmailNotificationService
{
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
    ): EmailNotification {
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

        return compact('sent', 'failed');
    }

    public function send(EmailNotification $notification): bool
    {
        if ($notification->status !== 'pending') {
            return false;
        }

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
}
