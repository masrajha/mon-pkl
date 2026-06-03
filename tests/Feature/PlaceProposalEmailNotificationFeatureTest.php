<?php

namespace Tests\Feature;

use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\InternshipPlaceProposal;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceProposalEmailNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_place_proposal_submission_queues_student_and_admin_emails(): void
    {
        [$studentUser, $student, $period] = $this->studentFixture();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.test']);

        $this->actingAs($studentUser)
            ->post(route('student.proposals.store'), $this->proposalPayload($period))
            ->assertRedirect(route('student.dashboard'));

        $proposal = InternshipPlaceProposal::query()->firstOrFail();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'place_proposal.submitted.student',
            'recipient_email' => $student->student_email,
            'notifiable_type' => $proposal->getMorphClass(),
            'notifiable_id' => $proposal->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'place_proposal.submitted.admin',
            'recipient_email' => $admin->email,
            'notifiable_id' => $proposal->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_approval_as_new_master_queues_student_email(): void
    {
        [$admin, $proposal] = $this->proposalFixture();

        $this->actingAs($admin)
            ->post(route('management.place-proposals.approve', $proposal), [
                'mode' => 'new',
                'admin_note' => 'Disetujui sebagai master baru.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'place_proposal.reviewed.approved.student',
            'recipient_email' => $proposal->student->student_email,
            'notifiable_id' => $proposal->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_merge_and_reject_queue_student_emails(): void
    {
        [$admin, $proposal] = $this->proposalFixture();
        $target = InternshipPlace::query()->create([
            'name' => 'PT Master Tujuan',
            'latitude' => -5.31,
            'longitude' => 105.21,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('management.place-proposals.approve', $proposal), [
                'mode' => 'merge',
                'internship_place_id' => $target->id,
                'admin_note' => 'Digabung ke master yang sudah ada.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'place_proposal.reviewed.merged.student',
            'recipient_email' => $proposal->student->student_email,
            'notifiable_id' => $proposal->id,
        ]);

        [, $rejectedProposal] = $this->proposalFixture('PT Ditolak');

        $this->actingAs($admin)
            ->post(route('management.place-proposals.reject', $rejectedProposal), [
                'admin_note' => 'Lokasi tidak sesuai.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'place_proposal.reviewed.rejected.student',
            'recipient_email' => $rejectedProposal->student->student_email,
            'notifiable_id' => $rejectedProposal->id,
        ]);
    }

    public function test_pending_place_proposal_reminder_command_queues_admin_email(): void
    {
        [$admin, $proposal] = $this->proposalFixture();
        $proposal->forceFill(['created_at' => now()->subHours(49)])->save();

        $this->artisan('silat:place-proposal-notifications:queue-pending-reminders --hours=48')
            ->expectsOutput('Pending place proposal reminder notifications queued: 1.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'place_proposal.pending.reminder.admin',
            'recipient_email' => $admin->email,
            'notifiable_id' => $proposal->id,
            'status' => 'pending',
        ]);
    }

    private function studentFixture(?string $suffix = null): array
    {
        $suffix ??= (string) fake()->unique()->numberBetween(1000, 9999);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $studyProgram = StudyProgram::query()->create([
            'code' => 'ILKOM'.$suffix,
            'name' => 'S1 Ilmu Komputer '.$suffix,
            'degree_level' => 'S1',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '221705'.$suffix,
            'full_name' => 'Mahasiswa Usulan',
            'student_email' => 'mahasiswa-usulan-'.$suffix.'@example.test',
            'phone' => '081234567890',
        ]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Usulan', 'academic_year' => '2026/2027', 'is_active' => true]);

        return [$studentUser, $student, $period, $studyProgram];
    }

    private function proposalFixture(string $name = 'PT Usulan Email'): array
    {
        [, $student, $period, $studyProgram] = $this->studentFixture();
        $admin = User::factory()->create(['role' => 'admin', 'email' => fake()->unique()->safeEmail()]);
        $proposal = InternshipPlaceProposal::query()->create($this->proposalPayload($period, $name) + [
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'proposed_by' => $student->user_id,
            'status' => 'pending',
        ]);

        return [$admin, $proposal->load('student')];
    }

    private function proposalPayload(InternshipPeriod $period, string $name = 'PT Usulan Email'): array
    {
        return [
            'internship_period_id' => $period->id,
            'name' => $name,
            'address' => 'Jl. Usulan',
            'city_name' => 'Bandar Lampung',
            'latitude' => -5.41,
            'longitude' => 105.26,
            'field_supervisor_name' => 'Pembimbing Mitra',
            'field_supervisor_phone' => '081111111111',
        ];
    }
}
