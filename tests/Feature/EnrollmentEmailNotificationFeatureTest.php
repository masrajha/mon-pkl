<?php

namespace Tests\Feature;

use App\Models\EmailNotification;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\PeriodDeadline;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentEmailNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_submission_queues_student_and_reviewer_emails(): void
    {
        Storage::fake('public');

        [$studentUser, $student, $period, $place] = $this->registrationFixture();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.test']);

        $this->actingAs($studentUser)
            ->post(route('student.enrollments.store'), $this->validEnrollmentPayload($period, $place))
            ->assertRedirect(route('student.dashboard'));

        $enrollment = InternshipEnrollment::query()->firstOrFail();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'enrollment.submitted.student',
            'recipient_email' => $student->student_email,
            'notifiable_type' => $enrollment->getMorphClass(),
            'notifiable_id' => $enrollment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'enrollment.submitted.reviewer',
            'recipient_email' => $admin->email,
            'notifiable_id' => $enrollment->id,
            'status' => 'pending',
        ]);
    }

    public function test_validation_decision_queues_student_status_email(): void
    {
        [$admin, $enrollment, $lecturer] = $this->validationFixture();

        $this->actingAs($admin)
            ->patch(route('management.enrollment-validations.update', $enrollment), [
                'status' => 'active',
                'lecturer_supervisor_id' => $lecturer->id,
                'field_supervisor' => 'Pembimbing Lapangan',
                'field_supervisor_phone' => '081111111111',
                'field_supervisor_email' => 'lapangan@example.test',
                'admin_note' => 'Disetujui.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'enrollment.validation.active.student',
            'recipient_email' => $enrollment->student->student_email,
            'notifiable_id' => $enrollment->id,
            'status' => 'pending',
        ]);
    }

    public function test_pending_validation_reminder_command_queues_reviewer_email_near_deadline(): void
    {
        [$admin, $enrollment] = $this->pendingReminderFixture();

        $this->artisan('silat:enrollment-notifications:queue-pending-reminders --days=3')
            ->expectsOutput('Pending enrollment reminder notifications queued: 1.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'enrollment.pending.reminder.reviewer',
            'recipient_email' => $admin->email,
            'notifiable_id' => $enrollment->id,
            'status' => 'pending',
        ]);
    }

    private function registrationFixture(): array
    {
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $studyProgram = StudyProgram::query()->create([
            'code' => 'ILKOM',
            'name' => 'S1 Ilmu Komputer',
            'degree_level' => 'S1',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051001',
            'full_name' => 'Mahasiswa Email',
            'student_email' => 'mahasiswa@example.test',
            'phone' => '081234567890',
        ]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Email', 'academic_year' => '2026/2027', 'is_active' => true]);
        $place = InternshipPlace::query()->create(['name' => 'PT Email', 'latitude' => -5.4, 'longitude' => 105.2, 'is_active' => true]);

        return [$studentUser, $student, $period, $place, $studyProgram];
    }

    private function validationFixture(): array
    {
        [$studentUser, $student, $period, $place, $studyProgram] = $this->registrationFixture();
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturerUser = User::factory()->create(['role' => 'dosen']);
        $lecturer = Lecturer::query()->create([
            'user_id' => $lecturerUser->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Dosen Email',
            'email' => 'dosen@example.test',
            'status' => 'active',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'contact_student_phone' => '081234567890',
            'has_krs_pkl' => true,
            'total_sks' => 120,
            'current_semester' => 7,
            'gpa' => 3.25,
            'status' => 'pending_verification',
        ]);

        return [$admin, $enrollment->load('student'), $lecturer];
    }

    private function pendingReminderFixture(): array
    {
        [$admin, $enrollment] = $this->validationFixture();

        PeriodDeadline::query()->create([
            'internship_period_id' => $enrollment->internship_period_id,
            'deadline_type' => 'registration_end',
            'deadline_date' => now()->addDays(2)->toDateString(),
            'penalty_points' => 0,
            'is_fixed_penalty' => false,
        ]);

        return [$admin, $enrollment];
    }

    private function validEnrollmentPayload(InternshipPeriod $period, InternshipPlace $place): array
    {
        return [
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'contact_student_phone' => '081234567890',
            'field_supervisor' => 'Pembimbing Lapangan',
            'field_supervisor_phone' => '081111111111',
            'has_krs_pkl' => 1,
            'total_sks' => 120,
            'current_semester' => 7,
            'gpa' => 3.25,
            'registration_document' => UploadedFile::fake()->create('bukti-akademik.pdf', 128, 'application/pdf'),
        ];
    }
}
