<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_report_requires_authentication(): void
    {
        $this->get(route('reports.monitoring'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_monitoring_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('reports.monitoring'))
            ->assertOk()
            ->assertSee('Rekap Monitoring Program');
    }

    public function test_student_monitoring_report_defaults_to_active_enrollment_period(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051001',
            'full_name' => 'Mahasiswa Uji',
        ]);
        $activePeriod = InternshipPeriod::query()->create([
            'name' => 'Periode Aktif',
            'academic_year' => '2025/2026',
            'semester' => 'Genap',
            'starts_at' => '2026-02-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $oldPeriod = InternshipPeriod::query()->create([
            'name' => 'Periode Lama',
            'academic_year' => '2024/2025',
            'semester' => 'Ganjil',
            'starts_at' => '2025-07-01',
            'ends_at' => '2025-12-31',
        ]);
        $activePlace = InternshipPlace::query()->create(['name' => 'Instansi Aktif', 'latitude' => -5.3971, 'longitude' => 105.2668]);
        $oldPlace = InternshipPlace::query()->create(['name' => 'Instansi Lama', 'latitude' => -5.3971, 'longitude' => 105.2668]);
        $activeEnrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $activePeriod->id,
            'internship_place_id' => $activePlace->id,
            'status' => 'active',
        ]);
        $oldEnrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $oldPeriod->id,
            'internship_place_id' => $oldPlace->id,
            'status' => 'completed',
        ]);

        $activeCheckIn = CheckIn::query()->create([
            'internship_enrollment_id' => $activeEnrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-03-02 08:00:00',
            'student_latitude' => -5.3972,
            'student_longitude' => 105.2669,
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $activeEnrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'pair_id' => $activeCheckIn->id,
            'duration_minutes' => 480,
            'checked_at' => '2026-03-02 16:00:00',
            'student_latitude' => -5.3972,
            'student_longitude' => 105.2669,
        ]);

        $oldCheckIn = CheckIn::query()->create([
            'internship_enrollment_id' => $oldEnrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2025-08-01 08:00:00',
            'student_latitude' => -5.3972,
            'student_longitude' => 105.2669,
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $oldEnrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'pair_id' => $oldCheckIn->id,
            'duration_minutes' => 480,
            'checked_at' => '2025-08-01 16:00:00',
            'student_latitude' => -5.3972,
            'student_longitude' => 105.2669,
        ]);

        $this->actingAs($user)
            ->get(route('reports.monitoring'))
            ->assertOk()
            ->assertSee('Periode Aktif')
            ->assertSee('Periode Lama')
            ->assertSee('Instansi Aktif')
            ->assertDontSee('Instansi Lama');

        $this->actingAs($user)
            ->get(route('reports.monitoring', ['period_id' => $oldPeriod->id]))
            ->assertOk()
            ->assertSee('Instansi Lama')
            ->assertDontSee('Instansi Aktif');
    }
}
