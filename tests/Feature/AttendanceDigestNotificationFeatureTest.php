<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDigestNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_attendance_digest_queues_student_lecturer_and_coordinator_emails(): void
    {
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $lecturerUser = User::factory()->create(['role' => 'dosen', 'email' => 'dosen@example.test']);
        $coordinatorUser = User::factory()->create(['role' => 'dosen', 'email' => 'koordinator@example.test']);
        $program = Program::query()->create([
            'code' => 'KP-DIGEST',
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
            'name' => 'Mitra Digest',
            'address' => 'Bandar Lampung',
            'latitude' => -5.3640000,
            'longitude' => 105.2430000,
            'is_active' => true,
        ]);
        $lecturer = Lecturer::query()->create([
            'user_id' => $lecturerUser->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Dosen Pembimbing',
            'email' => 'dosen@example.test',
            'status' => 'active',
        ]);
        $coordinator = Lecturer::query()->create([
            'user_id' => $coordinatorUser->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Koordinator Program',
            'email' => 'koordinator@example.test',
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
            'npm' => '2217051020',
            'full_name' => 'Mahasiswa Digest',
            'student_email' => 'digest@student.unila.ac.id',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'lecturer_supervisor_id' => $lecturer->id,
            'status' => 'active',
        ]);

        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Datang Terlambat',
            'action' => 'check_in',
            'note' => 'Rencana kerja',
            'checked_at' => '2026-06-02 08:30:00',
            'distance_meters' => 190,
            'sanction_points' => 1,
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'note' => 'Realisasi kerja',
            'checked_at' => '2026-06-02 16:00:00',
            'distance_meters' => 190,
            'duration_minutes' => 450,
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'note' => 'Presensi tidak lengkap',
            'checked_at' => '2026-06-03 08:00:00',
            'distance_meters' => 650,
        ]);

        $this->artisan('silat:attendance-digests:queue --week-start=2026-06-01 --week-end=2026-06-07')
            ->assertExitCode(0);

        $this->assertDatabaseHas('email_notifications', [
            'type' => 'attendance_digest.weekly.student',
            'recipient_email' => 'digest@student.unila.ac.id',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'attendance_digest.weekly.lecturer',
            'recipient_email' => 'dosen@example.test',
        ]);
        $this->assertDatabaseHas('email_notifications', [
            'type' => 'attendance_digest.weekly.coordinator',
            'recipient_email' => 'koordinator@example.test',
        ]);
    }
}
