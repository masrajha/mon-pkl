<?php

namespace Tests\Feature;

use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorRelocationEmailNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_change_submission_and_approval_queue_expected_emails(): void
    {
        [$studentUser, $student, $admin, $coordinatorUser, $enrollment, $oldLecturer, $newLecturer] = $this->supervisorFixture();

        $this->actingAs($studentUser)
            ->post(route('student.supervisor-requests.store'), [
                'internship_enrollment_id' => $enrollment->id,
                'requested_lecturer_supervisor_id' => $newLecturer->id,
                'requested_field_supervisor' => 'Pembimbing Lapangan Baru',
                'requested_field_supervisor_phone' => '081211111111',
                'requested_field_supervisor_email' => 'lapangan.baru@example.test',
                'reason' => 'Pembimbing baru menyesuaikan lokasi kegiatan.',
            ])
            ->assertRedirect(route('student.dashboard'));

        $request = \App\Models\SupervisorChangeRequest::query()->firstOrFail();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'supervisor_change.submitted.student',
            'recipient_email' => $student->student_email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'supervisor_change.submitted.reviewer',
            'recipient_email' => $admin->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'supervisor_change.submitted.reviewer',
            'recipient_email' => $coordinatorUser->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);

        $request->forceFill(['created_at' => now()->subHours(50)])->save();

        $this->artisan('silat:supervisor-change-notifications:queue-pending-reminders --hours=48')
            ->assertExitCode(0);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'supervisor_change.pending.reminder.reviewer',
            'recipient_email' => $admin->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'supervisor_change.pending.reminder.reviewer',
            'recipient_email' => $coordinatorUser->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('management.supervisor-requests.update', $request), [
                'status' => 'approved',
                'lecturer_supervisor_id' => $newLecturer->id,
                'field_supervisor' => 'Pembimbing Lapangan Baru',
                'field_supervisor_phone' => '081211111111',
                'field_supervisor_email' => 'lapangan.baru@example.test',
                'admin_note' => 'Disetujui.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'supervisor_change.reviewed.approved.student',
            'recipient_email' => $student->student_email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'supervisor_change.lecturer.assigned',
            'recipient_email' => $newLecturer->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'supervisor_change.lecturer.unassigned',
            'recipient_email' => $oldLecturer->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
    }

    public function test_relocation_submission_and_approval_queue_expected_emails(): void
    {
        [$studentUser, $student, $admin, $coordinatorUser, $enrollment, $oldPlace, $newPlace, $lecturer] = $this->relocationFixture();

        $this->actingAs($studentUser)
            ->post(route('student.relocations.store'), [
                'internship_enrollment_id' => $enrollment->id,
                'new_internship_place_id' => $newPlace->id,
                'reason' => 'Instansi lama tidak dapat melanjutkan program.',
            ])
            ->assertRedirect(route('student.dashboard'));

        $request = \App\Models\RelocationRequest::query()->firstOrFail();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'relocation.submitted.student',
            'recipient_email' => $student->student_email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'relocation.submitted.reviewer',
            'recipient_email' => $admin->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'relocation.submitted.reviewer',
            'recipient_email' => $coordinatorUser->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);

        $request->forceFill(['created_at' => now()->subHours(50)])->save();

        $this->artisan('silat:relocation-notifications:queue-pending-reminders --hours=48')
            ->assertExitCode(0);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'relocation.pending.reminder.reviewer',
            'recipient_email' => $admin->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'relocation.pending.reminder.reviewer',
            'recipient_email' => $coordinatorUser->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);

        $this->actingAs($coordinatorUser)
            ->get(route('management.relocations.index'))
            ->assertOk()
            ->assertSee($oldPlace->name)
            ->assertSee($newPlace->name);

        $this->actingAs($coordinatorUser)
            ->patch(route('management.relocations.update', $request), [
                'status' => 'approved',
                'admin_note' => 'Disetujui koordinator.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'relocation.reviewed.approved.student',
            'recipient_email' => $student->student_email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'relocation.lecturer.approved',
            'recipient_email' => $lecturer->email,
            'notifiable_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('internship_enrollments', [
            'id' => $enrollment->id,
            'internship_place_id' => $newPlace->id,
        ]);
    }

    private function supervisorFixture(): array
    {
        [$studentUser, $student, $admin, $coordinatorUser, $period, $program, $place] = $this->baseFixture('SUP');
        $oldLecturerUser = User::factory()->create(['role' => 'dosen', 'email' => 'old.lecturer@example.test']);
        $newLecturerUser = User::factory()->create(['role' => 'dosen', 'email' => 'new.lecturer@example.test']);
        $oldLecturer = Lecturer::query()->create([
            'user_id' => $oldLecturerUser->id,
            'study_program_id' => $program->id,
            'name' => 'Dosen Lama',
            'email' => 'old.lecturer@example.test',
            'status' => 'active',
        ]);
        $newLecturer = Lecturer::query()->create([
            'user_id' => $newLecturerUser->id,
            'study_program_id' => $program->id,
            'name' => 'Dosen Baru',
            'email' => 'new.lecturer@example.test',
            'status' => 'active',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'lecturer_supervisor_id' => $oldLecturer->id,
            'lecturer_supervisor_user_id' => $oldLecturerUser->id,
            'lecturer_supervisor' => $oldLecturer->name,
            'field_supervisor' => 'Pembimbing Lama',
            'field_supervisor_phone' => '081100000000',
            'field_supervisor_email' => 'lapangan.lama@example.test',
            'status' => 'active',
        ]);

        return [$studentUser, $student, $admin, $coordinatorUser, $enrollment, $oldLecturer, $newLecturer];
    }

    private function relocationFixture(): array
    {
        [$studentUser, $student, $admin, $coordinatorUser, $period, $program, $oldPlace] = $this->baseFixture('REL');
        $newPlace = InternshipPlace::query()->create(['name' => 'Tempat Baru Email', 'is_active' => true]);
        $lecturerUser = User::factory()->create(['role' => 'dosen', 'email' => 'relocation.lecturer@example.test']);
        $lecturer = Lecturer::query()->create([
            'user_id' => $lecturerUser->id,
            'study_program_id' => $program->id,
            'name' => 'Dosen Relokasi',
            'email' => 'relocation.lecturer@example.test',
            'status' => 'active',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $oldPlace->id,
            'lecturer_supervisor_id' => $lecturer->id,
            'lecturer_supervisor_user_id' => $lecturerUser->id,
            'lecturer_supervisor' => $lecturer->name,
            'status' => 'active',
        ]);

        return [$studentUser, $student, $admin, $coordinatorUser, $enrollment, $oldPlace, $newPlace, $lecturer];
    }

    private function baseFixture(string $suffix): array
    {
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $admin = User::factory()->create(['role' => 'admin', 'email' => strtolower($suffix).'.admin@example.test']);
        $coordinatorUser = User::factory()->create(['role' => 'koordinator', 'email' => strtolower($suffix).'.coordinator@example.test']);
        $program = StudyProgram::query()->create([
            'code' => $suffix,
            'name' => 'Program '.$suffix,
            'degree_level' => 'S1',
            'is_active' => true,
        ]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode '.$suffix, 'academic_year' => '2026/2027', 'is_active' => true]);
        $place = InternshipPlace::query()->create(['name' => 'Tempat Lama '.$suffix, 'is_active' => true]);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $program->id,
            'npm' => '2217051'.($suffix === 'SUP' ? '701' : '702'),
            'full_name' => 'Mahasiswa '.$suffix,
            'student_email' => strtolower($suffix).'.student@example.test',
            'phone' => '081234567890',
        ]);
        $coordinatorLecturer = Lecturer::query()->create([
            'user_id' => $coordinatorUser->id,
            'study_program_id' => $program->id,
            'name' => 'Koordinator '.$suffix,
            'email' => $coordinatorUser->email,
            'status' => 'active',
        ]);
        InternshipCoordinator::query()->create([
            'lecturer_id' => $coordinatorLecturer->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $program->id,
            'status' => 'active',
        ]);

        return [$studentUser, $student, $admin, $coordinatorUser, $period, $program, $place];
    }
}
