<?php

namespace Tests\Feature;

use App\Mail\SystemNotificationMail;
use App\Models\EmailNotification;
use App\Services\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_notification_service_queues_each_event_once(): void
    {
        $service = app(EmailNotificationService::class);

        $service->queue(
            type: 'test.event',
            recipientEmail: 'USER@EXAMPLE.COM',
            subject: '[SiLAT] Test',
            bodyLines: ['Baris pertama.'],
            recipientName: 'User Test',
            eventKey: 'test-event-user',
        );

        $service->queue(
            type: 'test.event',
            recipientEmail: 'user@example.com',
            subject: '[SiLAT] Test',
            bodyLines: ['Baris pertama.'],
            recipientName: 'User Test',
            eventKey: 'test-event-user',
        );

        $this->assertDatabaseCount('email_notifications', 1);
        $this->assertDatabaseHas('email_notifications', [
            'event_key' => 'test-event-user',
            'recipient_email' => 'user@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_scheduler_command_processes_due_email_notifications(): void
    {
        Mail::fake();

        EmailNotification::query()->create([
            'event_key' => 'due-notification',
            'type' => 'test.event',
            'recipient_email' => 'user@example.com',
            'recipient_name' => 'User Test',
            'subject' => '[SiLAT] Test Due',
            'body_lines' => ['Notifikasi jatuh tempo.'],
            'status' => 'pending',
            'scheduled_for' => now()->subMinute(),
        ]);

        $this->artisan('silat:email-notifications:process')
            ->expectsOutput('Email notifications processed. Sent: 1. Failed: 0.')
            ->assertExitCode(0);

        Mail::assertSent(SystemNotificationMail::class, function (SystemNotificationMail $mail): bool {
            return $mail->notification->recipient_email === 'user@example.com'
                && $mail->notification->subject === '[SiLAT] Test Due';
        });

        $this->assertDatabaseHas('email_notifications', [
            'event_key' => 'due-notification',
            'status' => 'sent',
        ]);
    }
}
