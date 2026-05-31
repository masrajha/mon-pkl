<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_check_in_requires_mahasiswa_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('check-ins.create'))
            ->assertForbidden();
    }

    public function test_mahasiswa_can_store_check_in_for_own_active_enrollment(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 31, 8, 30, 0, config('monpkl.timezone')));

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'name' => 'Periode Uji',
            'academic_year' => '2025/2026',
            'semester' => 'Genap',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create([
            'name' => 'Instansi Uji',
            'latitude' => -5.3971,
            'longitude' => 105.2668,
        ]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051001',
            'full_name' => $user->name,
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('check-ins.store'), [
                'student_latitude' => -5.3972,
                'student_longitude' => 105.2669,
                'note' => 'Mengerjakan dokumentasi.',
            ])
            ->assertRedirect(route('check-ins.create'));

        $this->assertDatabaseHas('check_ins', [
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Datang Terlambat',
            'note' => 'Mengerjakan dokumentasi.',
        ]);

        $this->assertGreaterThan(0, CheckIn::query()->first()->distance_meters);
    }

    public function test_check_in_is_rejected_outside_working_hours(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 31, 20, 0, 0, config('monpkl.timezone')));

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Uji', 'academic_year' => '2025/2026', 'semester' => 'Genap']);
        $place = InternshipPlace::query()->create(['name' => 'Instansi Uji', 'latitude' => -5.3971, 'longitude' => 105.2668]);
        $student = Student::query()->create(['user_id' => $user->id, 'study_program_id' => $program->id, 'npm' => '2217051002', 'full_name' => $user->name]);
        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->from(route('check-ins.create'))
            ->post(route('check-ins.store'), [
                'student_latitude' => -5.3972,
                'student_longitude' => 105.2669,
            ])
            ->assertRedirect(route('check-ins.create'))
            ->assertSessionHasErrors('student_latitude');

        $this->assertDatabaseCount('check_ins', 0);
    }
}
