<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\FieldSupervisorAssessment;
use App\Models\FinalAssessment;
use App\Models\ForgottenAttendanceRequest;
use App\Models\InternshipEnrollment;
use App\Models\InternshipCoordinator;
use App\Models\InternshipPeriod;
use App\Models\InternshipPeriodSetting;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Organization;
use App\Models\Program;
use App\Models\ReportViewerAssignment;
use App\Models\Sanction;
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
        $inactiveEnrollment = $this->createFunnelEnrollment($studyProgram, $period, $place, '2217051004', 'Mahasiswa Nonaktif', 'inactive');

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
        CheckIn::query()->create([
            'internship_enrollment_id' => $inactiveEnrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-02 08:00:00',
        ]);
        FinalAssessment::query()->create([
            'internship_enrollment_id' => $inactiveEnrollment->id,
            'lecturer_score' => 90,
            'field_supervisor_score' => 90,
            'base_score' => 90,
            'final_score' => 90,
            'finalized_by' => $admin->id,
            'finalized_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.progress-funnel', ['period_id' => $period->id]));

        $response->assertOk()
            ->assertViewHas('total', 2)
            ->assertViewHas('stages', fn ($stages): bool => collect($stages)->firstWhere('key', 'active_attendance')['count'] === 1
                && collect($stages)->firstWhere('key', 'final_score')['count'] === 1
                && collect($stages)->pluck('key')->values()->all() === [
                    'approved',
                    'active_attendance',
                    'full_report',
                    'field_supervisor_score',
                    'seminar',
                    'lecturer_score',
                    'final_score',
                ])
            ->assertViewHas('breakdownRows', fn ($rows): bool => collect($rows)->firstWhere('name', 'S1 Ilmu Komputer')['total'] === 2)
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

    public function test_report_viewer_can_access_analysis_reports_only_within_assignment_scope(): void
    {
        $viewerUser = User::factory()->create(['role' => 'dosen']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $university = Organization::query()->create(['type' => 'university', 'code' => 'UNILA-TST', 'name' => 'Universitas Lampung']);
        $faculty = Organization::query()->create(['parent_id' => $university->id, 'type' => 'faculty', 'code' => 'FMIPA-TST', 'name' => 'FMIPA']);
        $department = Organization::query()->create(['parent_id' => $faculty->id, 'type' => 'department', 'code' => 'JUR-ILKOM-TST', 'name' => 'Jurusan Ilmu Komputer']);
        $otherDepartment = Organization::query()->create(['parent_id' => $faculty->id, 'type' => 'department', 'code' => 'JUR-LAIN-TST', 'name' => 'Jurusan Lain']);
        $scopedProgram = StudyProgram::query()->create(['code' => 'SCP', 'name' => 'S1 Scope Viewer', 'organization_id' => $department->id, 'is_active' => true]);
        $otherProgram = StudyProgram::query()->create(['code' => 'OTH', 'name' => 'S1 Luar Scope', 'organization_id' => $otherDepartment->id, 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Viewer',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Viewer']);
        $viewerLecturer = Lecturer::query()->create([
            'user_id' => $viewerUser->id,
            'study_program_id' => $scopedProgram->id,
            'name' => 'Dosen Viewer',
            'email' => $viewerUser->email,
            'status' => 'active',
        ]);
        ReportViewerAssignment::query()->create([
            'lecturer_id' => $viewerLecturer->id,
            'organization_id' => $department->id,
            'level' => 'department',
            'status' => 'active',
        ]);
        $scopedEnrollment = $this->createFunnelEnrollment($scopedProgram, $period, $place, '2217052001', 'Mahasiswa Dalam Scope');
        $otherEnrollment = $this->createFunnelEnrollment($otherProgram, $period, $place, '2217052002', 'Mahasiswa Luar Scope');

        CheckIn::query()->create([
            'internship_enrollment_id' => $scopedEnrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-02 08:00:00',
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $otherEnrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-02 08:00:00',
        ]);

        $this->actingAs($viewerUser)
            ->get(route('reports.progress-funnel', ['period_id' => $period->id]))
            ->assertOk()
            ->assertSee('S1 Scope Viewer')
            ->assertDontSee('S1 Luar Scope');
    }

    public function test_lecturer_guidance_scope_keeps_analysis_reports_to_supervised_students(): void
    {
        $lecturerUser = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Pembimbing']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $guidedProgram = StudyProgram::query()->create(['code' => 'BIM', 'name' => 'S1 Bimbingan', 'is_active' => true]);
        $coordinatorProgram = StudyProgram::query()->create(['code' => 'KOR', 'name' => 'S1 Koordinator', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Dosen',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Dosen']);
        $lecturer = Lecturer::query()->create([
            'user_id' => $lecturerUser->id,
            'study_program_id' => $guidedProgram->id,
            'name' => $lecturerUser->name,
            'email' => $lecturerUser->email,
            'status' => 'active',
        ]);
        InternshipCoordinator::query()->create([
            'lecturer_id' => $lecturer->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $coordinatorProgram->id,
            'status' => 'active',
        ]);

        $guidedEnrollment = $this->createFunnelEnrollment($guidedProgram, $period, $place, '2217053001', 'Mahasiswa Bimbingan');
        $guidedEnrollment->update(['lecturer_supervisor_id' => $lecturer->id]);
        $coordinatorEnrollment = $this->createFunnelEnrollment($coordinatorProgram, $period, $place, '2217053002', 'Mahasiswa Koordinator');

        CheckIn::query()->create([
            'internship_enrollment_id' => $guidedEnrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-02 08:00:00',
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $coordinatorEnrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-02 08:00:00',
        ]);

        $this->actingAs($lecturerUser)
            ->get(route('reports.progress-funnel', ['scope' => 'bimbingan', 'period_id' => $period->id]))
            ->assertOk()
            ->assertViewHas('total', 1)
            ->assertSee('Progress Funnel Bimbingan')
            ->assertSee('Mahasiswa Bimbingan Aktif')
            ->assertSee('S1 Bimbingan')
            ->assertDontSee('S1 Koordinator');

        $this->actingAs($lecturerUser)
            ->get(route('reports.monitoring', ['scope' => 'bimbingan', 'period_id' => $period->id]))
            ->assertOk()
            ->assertSee('Rekap Monitoring Bimbingan')
            ->assertSee('Mahasiswa Bimbingan')
            ->assertDontSee('Mahasiswa Koordinator');

        $this->actingAs($lecturerUser)
            ->get(route('reports.risk-scoring', ['scope' => 'bimbingan', 'period_id' => $period->id]))
            ->assertOk()
            ->assertViewHas('canReviewReports', true)
            ->assertViewHas('canReviewSeminars', true)
            ->assertViewHas('canManageForgottenAttendance', false)
            ->assertViewHas('canFinalizeScores', false)
            ->assertSee('Mahasiswa Bimbingan')
            ->assertDontSee('Mahasiswa Koordinator')
            ->assertSee('management/submission-progress?period_id='.$period->id.'&amp;q=2217053001', false)
            ->assertSee('management/seminar-requests?period_id='.$period->id.'&amp;q=2217053001', false)
            ->assertDontSee('Lupa Presensi')
            ->assertDontSee('Finalisasi');

        $this->actingAs($lecturerUser)
            ->get(route('reports.final-scores', ['scope' => 'bimbingan', 'period_id' => $period->id]))
            ->assertOk()
            ->assertViewHas('canFinalizeScores', false)
            ->assertDontSee(route('management.final-assessments.index', ['period_id' => $period->id, 'q' => '2217053001']), false)
            ->assertDontSee('Finalisasi');

        $this->actingAs($lecturerUser)
            ->withSession(['active_role' => 'dosen'])
            ->get(route('management.final-assessments.index', ['period_id' => $period->id]))
            ->assertForbidden();
    }

    public function test_multi_role_user_can_switch_active_role_from_header(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-16 01:00:00', config('monpkl.timezone')));

        $lecturerUser = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Multi Role']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $organization = Organization::query()->create([
            'type' => 'university',
            'code' => 'UNILA-MULTI',
            'name' => 'Universitas Multi Role',
            'is_active' => true,
        ]);
        $studyProgram = StudyProgram::query()->create(['code' => 'MRL', 'name' => 'S1 Multi Role', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Multi Role',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $lecturer = Lecturer::query()->create([
            'user_id' => $lecturerUser->id,
            'study_program_id' => $studyProgram->id,
            'name' => $lecturerUser->name,
            'email' => $lecturerUser->email,
            'status' => 'active',
        ]);
        InternshipCoordinator::query()->create([
            'lecturer_id' => $lecturer->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
        ]);
        ReportViewerAssignment::query()->create([
            'lecturer_id' => $lecturer->id,
            'organization_id' => $organization->id,
            'level' => 'university',
            'status' => 'active',
            'starts_at' => '2026-06-16',
            'ends_at' => '2026-12-31',
        ]);

        $this->actingAs($lecturerUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mode akses')
            ->assertSee('Viewer Laporan')
            ->assertSee('Rekap Monitoring Bimbingan')
            ->assertDontSee('Input Lokasi Mitra')
            ->assertDontSee('Dashboard Koordinator');

        $this->actingAs($lecturerUser)
            ->post(route('active-role.update'), ['role' => 'koordinator'])
            ->assertRedirect(route('coordinator.dashboard'))
            ->assertSessionHas('active_role', 'koordinator');

        $this->actingAs($lecturerUser)
            ->withSession(['active_role' => 'koordinator'])
            ->get(route('coordinator.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Koordinator')
            ->assertDontSee('Rekap Monitoring Bimbingan');

        $this->actingAs($lecturerUser)
            ->post(route('active-role.update'), ['role' => 'report_viewer'])
            ->assertRedirect(route('reports.progress-funnel'))
            ->assertSessionHas('active_role', 'report_viewer');

        Carbon::setTestNow();
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

    public function test_report_date_filters_default_to_attendance_range_and_cap_end_to_today(): void
    {
        Carbon::setTestNow('2026-06-10 08:00:00');

        $admin = User::factory()->create(['role' => 'admin']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $studyProgram = StudyProgram::query()->firstOrCreate(['code' => 'ILKOM'], ['name' => 'S1 Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Default Tanggal',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Default Tanggal']);
        $enrollment = $this->createFunnelEnrollment($studyProgram, $period, $place, '2217051666', 'Mahasiswa Default Tanggal');
        $enrollment->update([
            'attendance_starts_at' => '2026-06-05',
            'attendance_ends_at' => '2026-06-25',
        ]);

        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'checked_at' => '2026-06-06 08:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.monitoring', ['period_id' => $period->id]))
            ->assertOk()
            ->assertViewHas('startDate', '2026-06-05')
            ->assertViewHas('endDate', '2026-06-10')
            ->assertSee('data-start-date="2026-06-05"', false)
            ->assertSee('data-end-date="2026-06-10"', false);

        $this->actingAs($admin)
            ->get(route('reports.sanctions', ['period_id' => $period->id]))
            ->assertOk()
            ->assertViewHas('startDate', '2026-06-05')
            ->assertViewHas('endDate', '2026-06-10');

        $this->actingAs($admin)
            ->get(route('reports.attendance-heatmap', ['period_id' => $period->id]))
            ->assertOk()
            ->assertViewHas('startDate', '2026-06-05')
            ->assertViewHas('endDate', '2026-06-10');

        $this->actingAs($admin)
            ->get(route('reports.operational-charts', ['period_id' => $period->id]))
            ->assertOk()
            ->assertViewHas('startDate', '2026-06-05')
            ->assertViewHas('endDate', '2026-06-10');

        $this->actingAs($admin)
            ->get(route('reports.monitoring', [
                'period_id' => $period->id,
                'start_date' => '2026-06-07',
                'end_date' => '2026-06-20',
            ]))
            ->assertOk()
            ->assertViewHas('startDate', '2026-06-07')
            ->assertViewHas('endDate', '2026-06-20');

        Carbon::setTestNow();
    }

    public function test_admin_can_access_operational_charts_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $studyProgram = StudyProgram::query()->create(['code' => 'IFG', 'name' => 'S1 Ilmu Komputer Grafik', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Grafik',
            'academic_year' => '2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'npm' => '220001',
            'full_name' => 'Mahasiswa Grafik',
            'study_program_id' => $studyProgram->id,
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Grafik', 'city' => 'Bandar Lampung']);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
            'total_sanctions_points' => 12,
        ]);
        $inactiveStudent = Student::query()->create([
            'npm' => '220002',
            'full_name' => 'Mahasiswa Grafik Nonaktif',
            'study_program_id' => $studyProgram->id,
        ]);
        $inactiveEnrollment = InternshipEnrollment::query()->create([
            'student_id' => $inactiveStudent->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'inactive',
            'total_sanctions_points' => 99,
        ]);

        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'action' => 'check_in',
            'type' => 'Tepat Waktu',
            'checked_at' => '2026-06-02 08:00:00',
            'note' => 'Masuk',
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $inactiveEnrollment->id,
            'action' => 'check_in',
            'type' => 'Tepat Waktu',
            'checked_at' => '2026-06-02 08:00:00',
            'note' => 'Masuk nonaktif',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.operational-charts', [
                'period_id' => $period->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-07',
            ]));

        $response->assertOk()
            ->assertViewHas('totalEnrollments', 1)
            ->assertViewHas('topSanctions', fn (array $rows): bool => collect($rows)->pluck('student')->doesntContain('Mahasiswa Grafik Nonaktif'))
            ->assertSee('Grafik Operasional')
            ->assertSee('Tren Presensi Harian')
            ->assertSee('Status Peserta per Prodi')
            ->assertSee('Top Sanksi')
            ->assertSee('Progress Status Nilai');
    }

    public function test_admin_can_access_sanctions_report_with_separated_sources(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $studyProgram = StudyProgram::query()->firstOrCreate(['code' => 'ILKOM'], ['name' => 'S1 Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Sanksi 2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Sanksi']);
        $enrollment = $this->createFunnelEnrollment($studyProgram, $period, $place, '2217051771', 'Mahasiswa Sanksi');

        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Pulang Cepat',
            'action' => 'check_out',
            'checked_at' => '2026-06-03 14:00:00',
            'sanction_points' => 2,
        ]);
        $progress = SubmissionProgress::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'deadline_type' => 'full_report',
            'file_path' => 'reports/sanksi.pdf',
            'uploaded_at' => '2026-06-04 09:00:00',
            'status' => 'approved',
            'sanction_points' => 5,
        ]);
        Sanction::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'submission_progress_id' => $progress->id,
            'sanction_type' => 'late_submission',
            'points_deducted' => 5,
            'reason' => 'Terlambat unggah laporan.',
            'date' => '2026-06-04',
        ]);
        FinalAssessment::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'lecturer_score' => 80,
            'field_supervisor_score' => 84,
            'base_score' => 82,
            'final_deduction' => 3,
            'final_score' => 79,
            'finalized_by' => $admin->id,
            'finalized_at' => '2026-06-05 10:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.sanctions', [
                'period_id' => $period->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Rekap Pelanggaran & Sanksi', false)
            ->assertSee('Mahasiswa Sanksi')
            ->assertSee('Sanksi Presensi')
            ->assertSee('Sanksi Laporan')
            ->assertSee('Pengurangan Final')
            ->assertSee('10,00');
    }

    public function test_admin_can_access_final_scores_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $studyProgram = StudyProgram::query()->firstOrCreate(['code' => 'ILKOM'], ['name' => 'S1 Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode Nilai 2026',
            'starts_at' => '2026-06-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Nilai']);
        $finalEnrollment = $this->createFunnelEnrollment($studyProgram, $period, $place, '2217051772', 'Mahasiswa Final');
        $pendingEnrollment = $this->createFunnelEnrollment($studyProgram, $period, $place, '2217051773', 'Mahasiswa Pending');

        FinalAssessment::query()->create([
            'internship_enrollment_id' => $finalEnrollment->id,
            'lecturer_score' => 80,
            'field_supervisor_score' => 84.33,
            'base_score' => 82.17,
            'final_deduction' => 0,
            'final_score' => 82.17,
            'document_number' => '2/UN.26/KP/2026',
            'finalized_by' => $admin->id,
            'finalized_at' => '2026-06-05 10:00:00',
        ]);
        SeminarRequest::query()->create([
            'internship_enrollment_id' => $pendingEnrollment->id,
            'title' => 'Seminar Pending',
            'status' => 'completed',
            'seminar_score' => 78,
            'scored_at' => '2026-06-05 09:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.final-scores', ['period_id' => $period->id]))
            ->assertOk()
            ->assertSee('Rekap Nilai Akhir')
            ->assertSee('Mahasiswa Final')
            ->assertSee('Mahasiswa Pending')
            ->assertSee('2/UN.26/KP/2026')
            ->assertSee('82,17')
            ->assertSee('A');

        $this->actingAs($admin)
            ->get(route('reports.final-scores.print', $finalEnrollment))
            ->assertOk()
            ->assertSee('Formulir Berita Acara');
    }

    public function test_student_cannot_access_operational_charts_report(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->get(route('reports.operational-charts'))
            ->assertForbidden();
    }

    public function test_student_cannot_access_sanctions_and_final_scores_reports(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->get(route('reports.sanctions'))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('reports.final-scores'))
            ->assertForbidden();
    }

    private function createFunnelEnrollment(StudyProgram $studyProgram, InternshipPeriod $period, InternshipPlace $place, string $npm, string $name, string $status = 'active'): InternshipEnrollment
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
            'status' => $status,
        ]);
    }
}
