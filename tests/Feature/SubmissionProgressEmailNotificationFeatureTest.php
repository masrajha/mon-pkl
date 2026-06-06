<?php

namespace Tests\Feature;

use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\PeriodDeadline;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\SubmissionProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionProgressEmailNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_and_review_report_queue_emails(): void
    {
        Storage::fake('public');

        [$admin, $studentUser, $lecturer, $enrollment] = $this->reportFixture();

        PeriodDeadline::query()->create([
            'internship_period_id' => $enrollment->internship_period_id,
            'deadline_type' => 'proposal',
            'deadline_date' => now()->subDay()->toDateString(),
            'penalty_points' => 2,
            'is_fixed_penalty' => true,
        ]);

        $this->actingAs($studentUser)
            ->post(route('student.reports.progress.store', $enrollment), [
                'deadline_type' => 'proposal',
                'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $progress = SubmissionProgress::query()->firstOrFail();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'submission_progress.uploaded.student',
            'recipient_email' => 'student.report@example.test',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'submission_progress.uploaded.lecturer',
            'recipient_email' => $lecturer->email,
        ]);

        $this->actingAs($admin)
            ->patch(route('management.submission-progress.update', $progress), [
                'status' => 'revision_required',
                'lecturer_note' => 'Lengkapi bagian metodologi.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'submission_progress.reviewed.revision_required.student',
            'recipient_email' => 'student.report@example.test',
        ]);
    }

    public function test_submission_progress_command_queues_deadline_pending_review_and_summary_emails(): void
    {
        [$admin, , $lecturer, $enrollment, $coordinator] = $this->reportFixture();

        PeriodDeadline::query()->create([
            'internship_period_id' => $enrollment->internship_period_id,
            'deadline_type' => 'proposal',
            'deadline_date' => now()->addDays(3)->toDateString(),
            'penalty_points' => 1,
            'is_fixed_penalty' => false,
        ]);

        SubmissionProgress::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'deadline_type' => 'bab1',
            'file_path' => 'submission-progress/bab1.pdf',
            'uploaded_at' => now()->subHours(49),
            'status' => 'pending',
            'sanction_points' => 0,
        ]);

        $this->artisan('silat:submission-progress-notifications:queue --deadline-days=3 --pending-review-hours=48')
            ->assertExitCode(0);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'submission_progress.deadline.reminder.student',
            'recipient_email' => 'student.report@example.test',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'submission_progress.pending_review.reminder.lecturer',
            'recipient_email' => $lecturer->email,
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'submission_progress.summary.admin',
            'recipient_email' => $admin->email,
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'submission_progress.summary.coordinator',
            'recipient_email' => $coordinator->email,
        ]);
    }

    private function reportFixture(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin.report@example.test']);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $lecturerUser = User::factory()->create(['role' => 'dosen', 'email' => 'lecturer.report@example.test']);
        $coordinatorUser = User::factory()->create(['role' => 'dosen', 'email' => 'coordinator.report@example.test']);
        $program = Program::query()->create([
            'code' => 'KP-REPORT',
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
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create([
            'name' => 'Mitra Laporan',
            'address' => 'Bandar Lampung',
            'latitude' => -5.364,
            'longitude' => 105.243,
            'is_active' => true,
        ]);
        $lecturer = Lecturer::query()->create([
            'user_id' => $lecturerUser->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Dosen Laporan',
            'email' => 'lecturer.report@example.test',
            'status' => 'active',
        ]);
        $coordinator = Lecturer::query()->create([
            'user_id' => $coordinatorUser->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Koordinator Laporan',
            'email' => 'coordinator.report@example.test',
            'status' => 'active',
        ]);
        InternshipCoordinator::query()->create([
            'lecturer_id' => $coordinator->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
        ]);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051030',
            'full_name' => 'Mahasiswa Laporan',
            'student_email' => 'student.report@example.test',
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

        return [$admin, $studentUser, $lecturer, $enrollment, $coordinator];
    }
}
