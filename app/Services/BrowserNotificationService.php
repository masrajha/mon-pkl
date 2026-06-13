<?php

namespace App\Services;

use App\Models\BrowserNotification;
use App\Models\BrowserPushSubscription;
use App\Models\InternshipEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class BrowserNotificationService
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function queueAttendanceReminders(int $warningMinutes = 15): int
    {
        $queued = 0;

        InternshipEnrollment::query()
            ->with(['student.user', 'internshipPeriod.setting', 'internshipPeriod.program'])
            ->where('status', 'active')
            ->whereHas('student', fn ($query) => $query->whereNotNull('user_id'))
            ->whereHas('internshipPeriod', fn ($query) => $query->where('is_active', true))
            ->chunkById(100, function (Collection $enrollments) use ($warningMinutes, &$queued): void {
                foreach ($enrollments as $enrollment) {
                    $queued += $this->queueEnrollmentAttendanceReminders($enrollment, $warningMinutes);
                }
            });

        return $queued;
    }

    public function pushDueNotifications(int $limit = 100): array
    {
        if (! $this->hasVapidKeys()) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => BrowserNotification::query()->whereNull('pushed_at')->whereNull('shown_at')->count(),
                'message' => 'VAPID key belum dikonfigurasi.',
            ];
        }

        $now = now();
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $webPush = $this->webPush();

        BrowserNotification::query()
            ->whereNull('pushed_at')
            ->whereNull('shown_at')
            ->whereNull('read_at')
            ->where(function ($query) use ($now): void {
                $query->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->oldest('scheduled_for')
            ->limit($limit)
            ->get()
            ->each(function (BrowserNotification $notification) use (&$sent, &$failed, &$skipped, $webPush): void {
                $subscriptions = BrowserPushSubscription::query()
                    ->where('user_id', $notification->user_id)
                    ->where('is_active', true)
                    ->get();

                if ($subscriptions->isEmpty()) {
                    $skipped++;

                    return;
                }

                $hasSuccess = false;
                $lastError = null;

                foreach ($subscriptions as $subscription) {
                    try {
                        $report = $webPush->sendOneNotification(
                            Subscription::create([
                                'endpoint' => $subscription->endpoint,
                                'publicKey' => $subscription->public_key,
                                'authToken' => $subscription->auth_token,
                                'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                            ]),
                            json_encode($this->payload($notification), JSON_UNESCAPED_UNICODE),
                            [
                                'TTL' => (int) config('monpkl.web_notifications.push_ttl_seconds', 3600),
                                'urgency' => 'high',
                                'topic' => 'silat-'.$notification->id,
                            ],
                        );

                        if ($report->isSuccess()) {
                            $hasSuccess = true;
                            $subscription->forceFill(['last_used_at' => now()])->save();
                        } else {
                            $lastError = $report->getReason();

                            if ($report->isSubscriptionExpired()) {
                                $subscription->forceFill([
                                    'is_active' => false,
                                    'revoked_at' => now(),
                                ])->save();
                            }
                        }
                    } catch (\Throwable $exception) {
                        $lastError = $exception->getMessage();
                    }
                }

                $notification->forceFill([
                    'pushed_at' => $hasSuccess ? now() : null,
                    'shown_at' => $hasSuccess ? now() : null,
                    'push_attempts' => $notification->push_attempts + 1,
                    'push_last_error' => $hasSuccess ? null : $lastError,
                ])->save();

                $hasSuccess ? $sent++ : $failed++;
            });

        return compact('sent', 'failed', 'skipped') + ['message' => null];
    }

    private function queueEnrollmentAttendanceReminders(InternshipEnrollment $enrollment, int $warningMinutes): int
    {
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);
        $timezone = $settings['timezone'] ?? config('monpkl.timezone', 'Asia/Jakarta');
        $now = now($timezone);

        if (! $this->isEffectiveAttendanceDay($enrollment, $now, $settings)) {
            return 0;
        }

        $dailyActions = $enrollment->checkIns()
            ->whereBetween('checked_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->pluck('action')
            ->unique();

        $queued = 0;

        if (! $dailyActions->contains('check_in')) {
            $queued += $this->queueReminderIfWithinWindow(
                enrollment: $enrollment,
                now: $now,
                settings: $settings,
                action: 'check_in',
                warningMinutes: $warningMinutes,
            );
        }

        if (! $dailyActions->contains('check_out')) {
            $queued += $this->queueReminderIfWithinWindow(
                enrollment: $enrollment,
                now: $now,
                settings: $settings,
                action: 'check_out',
                warningMinutes: $warningMinutes,
            );
        }

        return $queued;
    }

    private function queueReminderIfWithinWindow(InternshipEnrollment $enrollment, Carbon $now, array $settings, string $action, int $warningMinutes): int
    {
        $deadlineMinutes = $this->deadlineMinutes($settings, $action);

        if ($deadlineMinutes === null) {
            return 0;
        }

        $deadline = $now->copy()->startOfDay()->addMinutes($deadlineMinutes);

        if ($now->lt($deadline->copy()->subMinutes($warningMinutes)) || $now->gte($deadline)) {
            return 0;
        }

        $date = $now->toDateString();
        $eventKey = "attendance.missing.{$action}.{$enrollment->id}.{$date}";
        $label = $action === 'check_in' ? 'masuk' : 'pulang';
        $title = $action === 'check_in'
            ? 'Presensi masuk belum tercatat'
            : 'Presensi pulang belum tercatat';

        $notification = BrowserNotification::query()->firstOrCreate(
            ['event_key' => $eventKey],
            [
                'user_id' => $enrollment->student->user_id,
                'type' => "attendance.missing.{$action}",
                'title' => $title,
                'body' => 'Batas presensi '.$label.' berakhir pukul '.$deadline->format('H:i').'. Silakan buka halaman Presensi jika Anda sudah siap mencatat kehadiran.',
                'action_url' => route('check-ins.create', ['enrollment' => $enrollment->id]),
                'data' => [
                    'enrollment_id' => $enrollment->id,
                    'program' => $enrollment->internshipPeriod?->display_name,
                    'date' => $date,
                    'action' => $action,
                    'deadline' => $deadline->toIso8601String(),
                ],
                'scheduled_for' => now(),
                'expires_at' => $deadline->copy()->addMinutes(10)->setTimezone(config('app.timezone', 'UTC')),
            ],
        );

        return $notification->wasRecentlyCreated ? 1 : 0;
    }

    private function isEffectiveAttendanceDay(InternshipEnrollment $enrollment, Carbon $now, array $settings): bool
    {
        $startsAt = $enrollment->effectiveAttendanceStartsAt();
        $endsAt = $enrollment->effectiveAttendanceEndsAt();

        if ($startsAt && $now->lt($startsAt->copy()->startOfDay())) {
            return false;
        }

        if ($endsAt && $now->gt($endsAt->copy()->endOfDay())) {
            return false;
        }

        if ($now->isWeekend()) {
            return false;
        }

        return ! in_array($now->toDateString(), $settings['calendar']['holidays'] ?? [], true);
    }

    private function deadlineMinutes(array $settings, string $action): ?int
    {
        $allowedStatuses = $action === 'check_in'
            ? ['Masuk', 'Datang Terlambat']
            : ['Pulang Cepat', 'Pulang'];

        return collect($settings['check_in']['schedule'] ?? [])
            ->filter(fn (array $slot): bool => in_array($slot['status'] ?? '', $allowedStatuses, true))
            ->map(fn (array $slot): ?int => $this->timeToMinutes($slot['end'] ?? null))
            ->filter(fn ($minutes): bool => $minutes !== null)
            ->max();
    }

    private function timeToMinutes(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        [$hour, $minute] = array_pad(explode(':', $value, 2), 2, 0);

        return ((int) $hour) * 60 + (int) $minute;
    }

    private function payload(BrowserNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'body' => $notification->body,
            'action_url' => $notification->action_url ?: url('/dashboard'),
            'type' => $notification->type,
            'data' => $notification->data ?? [],
        ];
    }

    private function webPush(): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject' => config('monpkl.web_notifications.vapid_subject'),
                'publicKey' => config('monpkl.web_notifications.vapid_public_key'),
                'privateKey' => config('monpkl.web_notifications.vapid_private_key'),
            ],
        ]);
    }

    private function hasVapidKeys(): bool
    {
        return filled(config('monpkl.web_notifications.vapid_subject'))
            && filled(config('monpkl.web_notifications.vapid_public_key'))
            && filled(config('monpkl.web_notifications.vapid_private_key'));
    }
}
