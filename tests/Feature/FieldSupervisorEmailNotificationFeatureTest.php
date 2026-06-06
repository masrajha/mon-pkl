<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\FieldSupervisorAccessToken;
use App\Models\ForgottenAttendanceRequest;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\PeriodDeadline;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldSupervisorEmailNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_field_supervisor_command_queues_token_reminders_and_reviewer_alerts(): void
    {
        [$admin, , , $enrollment, $coordinator] = $this->fieldSupervisorFixture();

        FieldSupervisorAccessToken::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'email' => 'pl.notification@example.test',
            'token_hash' => hash('sha256', 'expired-token'),
            'expires_at' => now()->subDay(),
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'note' => 'Catatan harian belum validasi',
            'checked_at' => now()->subDay()->setTime(8, 0),
        ]);
        PeriodDeadline::query()->create([
            'internship_period_id' => $enrollment->internship_period_id,
            'deadline_type' => 'full_report',
            'deadline_date' => now()->addDays(7)->toDateString(),
            'penalty_points' => 1,
            'is_fixed_penalty' => false,
        ]);
        ForgottenAttendanceRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'action' => 'check_in',
            'requested_date' => now()->subDay()->toDateString(),
            'requested_time' => '08:00',
            'requested_checked_at' => now()->subDay()->setTime(8, 0),
            'note' => 'Rencana kegiatan',
            'reason' => 'Lupa presensi',
            'status' => 'pending',
        ]);

        $this->artisan('silat:field-supervisor-notifications:queue --minimum-pending-days=1 --full-report-days=7,3,1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'field_supervisor.access_token',
            'recipient_email' => 'pl.notification@example.test',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'field_supervisor.daily_log.reminder',
            'recipient_email' => 'pl.notification@example.test',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'field_supervisor.assessment.reminder',
            'recipient_email' => 'pl.notification@example.test',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'field_supervisor.full_report_deadline.daily_log_reminder',
            'recipient_email' => 'pl.notification@example.test',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'field_supervisor.full_report_deadline.assessment_reminder',
            'recipient_email' => 'pl.notification@example.test',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'field_supervisor.forgotten_attendance.reminder',
            'recipient_email' => 'pl.notification@example.test',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'field_supervisor.alert.admin',
            'recipient_email' => $admin->email,
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'field_supervisor.alert.coordinator',
            'recipient_email' => $coordinator->email,
        ]);
    }

    public function test_field_supervisor_assessment_queues_confirmation_email(): void
    {
        [, $fieldSupervisorUser, , $enrollment] = $this->fieldSupervisorFixture();

        $this->actingAs($fieldSupervisorUser)
            ->post(route('field-supervisor.assessment.store', $enrollment), $this->assessmentPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'field_supervisor.assessment.stored',
            'recipient_email' => 'pl.notification@example.test',
        ]);
    }

    private function fieldSupervisorFixture(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin.pl@example.test']);
        $fieldSupervisorUser = User::factory()->create(['role' => 'pembimbing_lapangan', 'email' => 'pl.notification@example.test']);
        $coordinatorUser = User::factory()->create(['role' => 'dosen', 'email' => 'coordinator.pl@example.test']);
        $program = Program::query()->create([
            'code' => 'KP-PL',
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
            'starts_at' => now()->subDays(10)->toDateString(),
            'ends_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create([
            'name' => 'Mitra Pembimbing',
            'address' => 'Bandar Lampung',
            'latitude' => -5.364,
            'longitude' => 105.243,
            'is_active' => true,
        ]);
        $coordinator = Lecturer::query()->create([
            'user_id' => $coordinatorUser->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Koordinator PL',
            'email' => 'coordinator.pl@example.test',
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
            'npm' => '2217051040',
            'full_name' => 'Mahasiswa Pembimbing',
            'student_email' => 'student.pl@example.test',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'field_supervisor' => 'Pembimbing Lapangan',
            'field_supervisor_email' => 'pl.notification@example.test',
            'status' => 'active',
        ]);

        return [$admin, $fieldSupervisorUser, $studentUser, $enrollment, $coordinator];
    }

    private function assessmentPayload(): array
    {
        return [
            'scores' => [
                'rules_compliance' => 90,
                'group_teamwork' => 90,
                'other_teamwork' => 90,
                'supervisor_teamwork' => 90,
                'innovation' => 90,
                'task_ability' => 90,
                'seriousness' => 90,
            ],
            'institution_feedback' => [
                'student_preparation' => 'very_good',
                'competency_fit' => 'suitable',
                'campus_communication' => 'good',
                'supervision_support' => 'helpful',
                'future_acceptance' => 'yes',
            ],
        ];
    }
}
