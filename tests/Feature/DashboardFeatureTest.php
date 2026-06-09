<?php

namespace Tests\Feature;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_handles_unknown_enrollment_status(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $studyProgram = StudyProgram::query()->create([
            'code' => 'ILKOM',
            'name' => 'Ilmu Komputer',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051999',
            'full_name' => 'Mahasiswa Status Tidak Dikenal',
            'student_email' => '2217051999@student.unila.ac.id',
            'phone' => '081234567890',
        ]);
        $period = InternshipPeriod::query()->create([
            'name' => 'Periode Dashboard',
            'academic_year' => '2026/2027',
            'is_active' => true,
        ]);

        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'status' => 'legacy_status',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Status Tidak Dikenal');
    }

    public function test_student_dashboard_shows_inactive_enrollment_status(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $studyProgram = StudyProgram::query()->create([
            'code' => 'IF',
            'name' => 'Informatika',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051888',
            'full_name' => 'Mahasiswa Nonaktif',
            'student_email' => '2217051888@student.unila.ac.id',
            'phone' => '081234567891',
        ]);
        $period = InternshipPeriod::query()->create([
            'name' => 'Periode Nonaktif Peserta',
            'academic_year' => '2026/2027',
            'is_active' => true,
        ]);

        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nonaktif')
            ->assertDontSee('Status Tidak Dikenal');
    }
}
