<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\ForgottenAttendanceRequest;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FieldSupervisorPhotoAuditFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_field_supervisor_portal_shows_attendance_and_forgotten_attendance_photos(): void
    {
        Storage::fake('public');

        $fieldSupervisor = User::factory()->create([
            'role' => 'pembimbing_lapangan',
            'email' => 'audit.pl@example.test',
            'name' => 'Pembimbing Audit',
        ]);
        $program = Program::query()->create([
            'code' => 'KP-AUDIT',
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
            'name' => 'Mitra Audit',
            'address' => 'Bandar Lampung',
            'latitude' => -5.364,
            'longitude' => 105.243,
            'is_active' => true,
        ]);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051060',
            'full_name' => 'Mahasiswa Audit',
            'student_email' => 'audit.student@example.test',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'field_supervisor' => 'Pembimbing Audit',
            'field_supervisor_email' => 'audit.pl@example.test',
            'status' => 'active',
        ]);

        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'note' => 'Rencana audit',
            'checked_at' => '2026-06-03 08:00:00',
            'photo_path' => 'check-in-photos/masuk-audit.jpg',
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'note' => 'Realisasi audit',
            'checked_at' => '2026-06-03 16:00:00',
            'photo_path' => 'check-in-photos/pulang-audit.jpg',
            'duration_minutes' => 480,
        ]);
        ForgottenAttendanceRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'action' => 'check_in',
            'requested_date' => '2026-06-04',
            'requested_time' => '08:00',
            'requested_checked_at' => '2026-06-04 08:00:00',
            'note' => 'Rencana lupa presensi',
            'reason' => 'Lupa menekan tombol presensi.',
            'photo_path' => 'forgotten-attendance-photos/lupa-audit.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($fieldSupervisor)
            ->get(route('field-supervisor.enrollments.show', $enrollment))
            ->assertOk()
            ->assertSee('Foto audit presensi')
            ->assertSee('Foto bukti Lupa Presensi')
            ->assertSee('check-in-photos/masuk-audit.jpg')
            ->assertSee('forgotten-attendance-photos/lupa-audit.jpg');
    }
}
