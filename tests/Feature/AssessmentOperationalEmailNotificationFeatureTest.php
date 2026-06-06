<?php

namespace Tests\Feature;

use App\Models\EmailNotification;
use App\Models\FieldSupervisorAssessment;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\SeminarRequest;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentOperationalEmailNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_seminar_schedule_and_score_queue_assessment_emails(): void
    {
        [$admin, $lecturerUser, $lecturer, $enrollment] = $this->assessmentFixture();

        FieldSupervisorAssessment::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'scores' => [],
            'discipline_score' => 80,
            'teamwork_score' => 85,
            'performance_score' => 90,
            'final_score' => 85,
            'assessed_by_name' => 'PL',
            'assessed_by_email' => 'pl@example.test',
            'assessment_mode' => 'login',
            'assessed_at' => now(),
        ]);

        $seminarRequest = SeminarRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'title' => 'Pengembangan Aplikasi',
            'mode' => 'offline',
            'approval_method' => 'system',
            'status' => 'lecturer_approved',
            'lecturer_approved_by' => $lecturerUser->id,
            'lecturer_approved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('management.seminar-requests.schedule', $seminarRequest), [
                'scheduled_at' => now()->subHour()->format('Y-m-d H:i:s'),
                'mode' => 'offline',
                'location' => 'Ruang Seminar',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'assessment.lecturer.eligible',
            'recipient_email' => $lecturer->email,
        ]);

        $this->actingAs($lecturerUser)
            ->patch(route('management.seminar-requests.score', $seminarRequest), [
                'assessment_method' => 'system',
                'seminar_score_note' => 'Baik',
                'assessment_scores' => $this->seminarScores(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'assessment.lecturer.score.stored',
            'recipient_email' => $lecturer->email,
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'assessment.components.ready.admin',
            'recipient_email' => $admin->email,
        ]);
    }

    public function test_assessment_command_queues_lecturer_reminder_and_finalization_alert(): void
    {
        [$admin, , $lecturer, $enrollment, $coordinator] = $this->assessmentFixture(now()->addDays(3)->toDateString());

        SeminarRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'title' => 'Pengembangan Aplikasi',
            'mode' => 'offline',
            'approval_method' => 'system',
            'status' => 'scheduled',
            'scheduled_at' => now()->subDay(),
        ]);

        $this->artisan('silat:assessment-notifications:queue --days-before-period-end=7')
            ->assertExitCode(0);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'assessment.lecturer.reminder',
            'recipient_email' => $lecturer->email,
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'assessment.finalization.alert.admin',
            'recipient_email' => $admin->email,
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'assessment.finalization.alert.coordinator',
            'recipient_email' => $coordinator->email,
        ]);
    }

    public function test_period_and_operational_failure_notifications_are_queued(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin.ops@example.test']);
        $program = Program::query()->create([
            'code' => 'KP-OPS',
            'name' => 'Kerja Praktik',
            'rule_key' => 'kerja_praktik',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('management.periods.store'), [
                'name' => 'Periode Operasional',
                'program_id' => $program->id,
                'academic_year' => '2026/2027',
                'semester' => 'Ganjil',
                'starts_at' => '2026-07-01',
                'ends_at' => '2026-08-31',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'operational.period.created',
            'recipient_email' => 'admin.ops@example.test',
        ]);

        EmailNotification::query()->create([
            'event_key' => 'failed-mail-test',
            'type' => 'test.failed',
            'recipient_email' => 'x@example.test',
            'subject' => 'Failed',
            'body_lines' => ['Failed'],
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        $this->artisan('silat:operational-notifications:queue')
            ->assertExitCode(0);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'operational.email.failures',
            'recipient_email' => 'admin.ops@example.test',
        ]);
    }

    private function assessmentFixture(?string $periodEndsAt = null): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin.assessment@example.test']);
        $lecturerUser = User::factory()->create(['role' => 'dosen', 'email' => 'lecturer.assessment@example.test']);
        $coordinatorUser = User::factory()->create(['role' => 'dosen', 'email' => 'coordinator.assessment@example.test']);
        $program = Program::query()->create([
            'code' => 'KP-ASSESS',
            'name' => 'Kerja Praktik',
            'rule_key' => 'kerja_praktik',
            'is_active' => true,
        ]);
        $studyProgram = StudyProgram::query()->create([
            'code' => 'ILKOM',
            'name' => 'S1 Ilmu Komputer',
            'degree_level' => 'S1',
            'is_active' => true,
        ]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Juni 2026',
            'academic_year' => '2025/2026',
            'starts_at' => now()->subMonth()->toDateString(),
            'ends_at' => $periodEndsAt ?: now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create([
            'name' => 'Mitra Nilai',
            'address' => 'Bandar Lampung',
            'latitude' => -5.364,
            'longitude' => 105.243,
            'is_active' => true,
        ]);
        $lecturer = Lecturer::query()->create([
            'user_id' => $lecturerUser->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Dosen Nilai',
            'email' => 'lecturer.assessment@example.test',
            'status' => 'active',
        ]);
        $coordinator = Lecturer::query()->create([
            'user_id' => $coordinatorUser->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Koordinator Nilai',
            'email' => 'coordinator.assessment@example.test',
            'status' => 'active',
        ]);
        InternshipCoordinator::query()->create([
            'lecturer_id' => $coordinator->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
        ]);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051050',
            'full_name' => 'Mahasiswa Nilai',
            'student_email' => 'student.assessment@example.test',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'lecturer_supervisor_id' => $lecturer->id,
            'lecturer_supervisor_user_id' => $lecturerUser->id,
            'status' => 'active',
        ]);

        return [$admin, $lecturerUser, $lecturer, $enrollment, $coordinator];
    }

    private function seminarScores(): array
    {
        return collect(config('monpkl.seminar_assessment_rubric'))
            ->mapWithKeys(fn ($item, string $key) => [$key => 80])
            ->all();
    }
}
