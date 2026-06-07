<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\FieldSupervisorAssessment;
use App\Models\FinalAssessment;
use App\Models\ForgottenAttendanceRequest;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPeriodSetting;
use App\Models\InternshipPlace;
use App\Models\Program;
use App\Models\SeminarRequest;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\SubmissionProgress;
use App\Models\User;
use Carbon\Carbon;
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

    public function test_admin_can_access_progress_funnel_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $studyProgram = StudyProgram::query()->firstOrCreate(['code' => 'ILKOM'], ['name' => 'S1 Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Juni 2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Funnel']);

        $completeEnrollment = $this->createFunnelEnrollment($studyProgram, $period, $place, '2217051002', 'Mahasiswa Lengkap');
        $stalledEnrollment = $this->createFunnelEnrollment($studyProgram, $period, $place, '2217051003', 'Mahasiswa Tertahan');

        $checkIn = CheckIn::query()->create([
            'internship_enrollment_id' => $completeEnrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-02 08:00:00',
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $completeEnrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'pair_id' => $checkIn->id,
            'checked_at' => '2026-06-02 16:00:00',
        ]);
        SubmissionProgress::query()->create([
            'internship_enrollment_id' => $completeEnrollment->id,
            'deadline_type' => 'full_report',
            'file_path' => 'reports/full.pdf',
            'uploaded_at' => now(),
            'status' => 'approved',
        ]);
        SeminarRequest::query()->create([
            'internship_enrollment_id' => $completeEnrollment->id,
            'title' => 'Seminar Funnel',
            'status' => 'completed',
            'scheduled_at' => now(),
            'completed_at' => now(),
            'seminar_score' => 82,
        ]);
        FieldSupervisorAssessment::query()->create([
            'internship_enrollment_id' => $completeEnrollment->id,
            'scores' => [],
            'discipline_score' => 84,
            'teamwork_score' => 84,
            'performance_score' => 84,
            'final_score' => 84,
            'assessed_by_name' => 'Pembimbing Lapangan',
            'assessed_by_email' => 'pl@example.test',
            'assessment_mode' => 'login',
            'assessed_at' => now(),
        ]);
        FinalAssessment::query()->create([
            'internship_enrollment_id' => $completeEnrollment->id,
            'lecturer_score' => 82,
            'field_supervisor_score' => 84,
            'base_score' => 83,
            'final_score' => 83,
            'finalized_by' => $admin->id,
            'finalized_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.progress-funnel', ['period_id' => $period->id]));

        $response->assertOk()
            ->assertSee('Progress Funnel Pelaksanaan')
            ->assertSee('Pendaftaran Disetujui')
            ->assertSee('Presensi Aktif')
            ->assertSee('Laporan Lengkap')
            ->assertSee('Nilai Pembimbing Lapangan Masuk')
            ->assertSee('Nilai Final')
            ->assertSee('1 peserta belum mencapai tahap ini')
            ->assertSee('S1 Ilmu Komputer');
    }

    public function test_student_cannot_access_progress_funnel_report(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->get(route('reports.progress-funnel'))
            ->assertForbidden();
    }

    public function test_admin_can_access_risk_scoring_report(): void
    {
        Carbon::setTestNow('2026-06-07 08:00:00');

        $admin = User::factory()->create(['role' => 'admin']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $studyProgram = StudyProgram::query()->firstOrCreate(['code' => 'ILKOM'], ['name' => 'S1 Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Risk 2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-06',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Risiko']);
        $criticalEnrollment = $this->createFunnelEnrollment($studyProgram, $period, $place, '2217051999', 'Mahasiswa Risiko');

        CheckIn::query()->create([
            'internship_enrollment_id' => $criticalEnrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-01 08:00:00',
            'note' => 'Rencana kerja tanpa pulang.',
            'sanction_points' => 5,
        ]);
        ForgottenAttendanceRequest::query()->create([
            'internship_enrollment_id' => $criticalEnrollment->id,
            'action' => 'check_out',
            'requested_date' => '2026-06-01',
            'requested_time' => '16:00:00',
            'requested_checked_at' => '2026-06-01 16:00:00',
            'note' => 'Pulang lupa presensi.',
            'reason' => 'Lupa presensi pulang.',
            'status' => 'pending',
        ]);
        SubmissionProgress::query()->create([
            'internship_enrollment_id' => $criticalEnrollment->id,
            'deadline_type' => 'full_report',
            'file_path' => 'reports/full-risk.pdf',
            'uploaded_at' => now(),
            'status' => 'approved',
            'sanction_points' => 10,
        ]);
        $criticalEnrollment->update(['total_sanctions_points' => 15]);

        $response = $this->actingAs($admin)
            ->get(route('reports.risk-scoring', ['period_id' => $period->id]));

        Carbon::setTestNow();

        $response->assertOk()
            ->assertSee('Risk Scoring Peserta')
            ->assertSee('Mahasiswa Risiko')
            ->assertSee('Kritis')
            ->assertSee('Tidak hadir beruntun')
            ->assertSee('Laporan terlambat')
            ->assertSee('Sanksi');
    }

    public function test_student_cannot_access_risk_scoring_report(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->get(route('reports.risk-scoring'))
            ->assertForbidden();
    }

    public function test_admin_can_access_attendance_heatmap_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $studyProgram = StudyProgram::query()->firstOrCreate(['code' => 'ILKOM'], ['name' => 'S1 Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Heatmap 2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-06',
            'is_active' => true,
        ]);
        InternshipPeriodSetting::query()->create([
            'internship_period_id' => $period->id,
            'settings' => ['calendar' => ['holidays' => ['2026-06-05']]],
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Heatmap']);
        $enrollment = $this->createFunnelEnrollment($studyProgram, $period, $place, '2217051888', 'Mahasiswa Heatmap');

        $checkIn = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-01 08:00:00',
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'pair_id' => $checkIn->id,
            'checked_at' => '2026-06-01 16:00:00',
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-02 08:05:00',
        ]);
        ForgottenAttendanceRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'action' => 'check_out',
            'requested_date' => '2026-06-03',
            'requested_time' => '16:00:00',
            'requested_checked_at' => '2026-06-03 16:00:00',
            'note' => 'Koreksi pulang.',
            'reason' => 'Lupa presensi pulang.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.attendance-heatmap', [
                'period_id' => $period->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-06',
            ]));

        $response->assertOk()
            ->assertSee('Heatmap Kehadiran')
            ->assertSee('Mahasiswa Heatmap')
            ->assertSee('Hadir valid')
            ->assertSee('Presensi satu sisi/tidak valid')
            ->assertSee('Lupa Presensi disetujui')
            ->assertSee('Tidak hadir')
            ->assertSee('Hari libur')
            ->assertSee('Sabtu/Minggu');
    }

    public function test_student_cannot_access_attendance_heatmap_report(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->get(route('reports.attendance-heatmap'))
            ->assertForbidden();
    }

    private function createFunnelEnrollment(StudyProgram $studyProgram, InternshipPeriod $period, InternshipPlace $place, string $npm, string $name): InternshipEnrollment
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $studyProgram->id,
            'npm' => $npm,
            'full_name' => $name,
        ]);

        return InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
        ]);
    }
}
