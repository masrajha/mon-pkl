<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\InternshipPlaceProposal;
use App\Models\Lecturer;
use App\Models\PeriodDeadline;
use App\Models\SubmissionProgress;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentWorkflowFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_can_complete_profile_register_and_propose_place(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Aktif', 'academic_year' => '2026/2027', 'is_active' => true]);
        $place = InternshipPlace::query()->create(['name' => 'PT Pilihan', 'latitude' => -5.4, 'longitude' => 105.2]);

        $this->actingAs($user)
            ->patch(route('student.profile.update'), [
                'npm' => '2217051001',
                'full_name' => 'Mahasiswa Workflow',
                'student_email' => '2217051001@student.unila.ac.id',
                'phone' => '081234567890',
                'study_program_id' => $program->id,
            ])
            ->assertRedirect(route('student.dashboard'));

        $student = Student::query()->where('npm', '2217051001')->firstOrFail();

        $otherProgram = StudyProgram::query()->create(['code' => 'SI', 'name' => 'Sistem Informasi', 'is_active' => true]);

        $this->actingAs($user)
            ->post(route('student.enrollments.store'), [
                'internship_period_id' => $period->id,
                'study_program_id' => $otherProgram->id,
                'internship_place_id' => $place->id,
                'contact_student_phone' => '081234567890',
                'field_supervisor' => 'Pembimbing Lapangan',
                'field_supervisor_phone' => '081111111111',
                'has_krs_pkl' => 1,
                'total_sks' => 120,
                'current_semester' => 7,
                'gpa' => 3.25,
                'registration_document' => UploadedFile::fake()->create('bukti-akademik.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('internship_enrollments', [
            'student_id' => $student->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $program->id,
            'status' => 'pending_verification',
        ]);
        $this->assertNotNull(InternshipEnrollment::query()->firstOrFail()->registration_document_path);

        $this->actingAs($user)
            ->post(route('student.proposals.store'), [
                'internship_period_id' => $period->id,
                'study_program_id' => $otherProgram->id,
                'name' => 'PT Usulan Baru',
                'address' => 'Alamat usulan',
                'city_name' => 'Bandar Lampung',
                'latitude' => -5.45,
                'longitude' => 105.27,
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('internship_place_proposals', [
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'name' => 'PT Usulan Baru',
            'status' => 'pending',
        ]);
    }

    public function test_student_can_open_partner_data_and_register_with_selected_place(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $studyProgram = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051010',
            'full_name' => 'Mahasiswa Data Mitra',
            'student_email' => '2217051010@student.unila.ac.id',
            'phone' => '081234567890',
        ]);
        InternshipPeriod::query()->create(['name' => 'Periode Aktif', 'academic_year' => '2026/2027', 'is_active' => true]);
        $inactivePeriod = InternshipPeriod::query()->create(['name' => 'Periode Lama', 'academic_year' => '2025/2026']);
        $place = InternshipPlace::query()->create([
            'name' => 'PT Data Mitra',
            'address' => 'Jl. Mitra',
            'latitude' => -5.4,
            'longitude' => 105.2,
            'is_active' => true,
        ]);
        $student = Student::query()->where('user_id', $user->id)->firstOrFail();
        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $inactivePeriod->id,
            'internship_place_id' => $place->id,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get(route('student.places.index'))
            ->assertOk()
            ->assertSee('Data Mitra')
            ->assertSee('Daftar Mitra');

        $this->actingAs($user)
            ->getJson(route('student.places.data'))
            ->assertOk()
            ->assertJsonPath('features.0.properties.id', $place->id)
            ->assertJsonPath('features.0.properties.enrollments_count', 0)
            ->assertJsonPath('features.0.properties.register_url', route('student.enrollments.create', ['internship_place_id' => $place->id]));

        $this->actingAs($user)
            ->get(route('student.enrollments.create', ['internship_place_id' => $place->id]))
            ->assertOk()
            ->assertSee('PT Data Mitra');
    }

    public function test_admin_can_approve_student_place_proposal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Proposal', 'academic_year' => '2026/2027']);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $program->id,
            'npm' => '2217051002',
            'full_name' => 'Mahasiswa Proposal',
        ]);
        $proposal = InternshipPlaceProposal::query()->create([
            'internship_period_id' => $period->id,
            'study_program_id' => $program->id,
            'student_id' => $student->id,
            'proposed_by' => $studentUser->id,
            'name' => 'PT Proposal',
            'address' => 'Alamat proposal',
            'city_name' => 'Bandar Lampung',
            'latitude' => -5.4,
            'longitude' => 105.2,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('management.place-proposals.approve', $proposal), [
                'mode' => 'new',
                'admin_note' => 'Disetujui',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_place_proposals', [
            'id' => $proposal->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('internship_places', [
            'name' => 'PT Proposal',
        ]);
    }

    public function test_student_print_report_requires_complete_supervisors(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = Lecturer::query()->create(['name' => 'Dosen Pembimbing', 'status' => 'active']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Laporan', 'academic_year' => '2026/2027']);
        $place = InternshipPlace::query()->create(['name' => 'PT Laporan', 'latitude' => -5.4, 'longitude' => 105.2]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051003',
            'full_name' => 'Mahasiswa Laporan',
            'student_email' => '2217051003@student.unila.ac.id',
            'phone' => '081234567890',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'lecturer_supervisor_id' => $lecturer->id,
            'field_supervisor' => 'Pembimbing Lapangan',
            'status' => 'active',
        ]);
        $checkIn = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'note' => 'Menyusun rencana kegiatan laporan hari ini.',
            'checked_at' => '2026-06-02 08:00:00',
            'distance_meters' => 12.5,
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'pair_id' => $checkIn->id,
            'note' => 'Merealisasikan kegiatan laporan bersama pembimbing.',
            'checked_at' => '2026-06-02 16:00:00',
            'distance_meters' => 15.25,
            'duration_minutes' => 480,
        ]);

        $this->actingAs($user)
            ->get(route('student.reports.print', $enrollment))
            ->assertOk()
            ->assertSee('Grafik Kehadiran')
            ->assertSee('Jam Masuk')
            ->assertSee('Jam Pulang')
            ->assertSee('Jarak Masuk')
            ->assertSee('Jarak Pulang')
            ->assertSee('chart-time')
            ->assertSee('bg-success');
    }

    public function test_student_can_request_relocation_and_admin_can_approve_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'IF', 'name' => 'Informatika', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Pindah', 'academic_year' => '2026/2027']);
        $oldPlace = InternshipPlace::query()->create(['name' => 'Tempat Lama', 'is_active' => true]);
        $newPlace = InternshipPlace::query()->create(['name' => 'Tempat Baru', 'is_active' => true]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051004',
            'full_name' => 'Mahasiswa Pindah',
            'student_email' => '2217051004@student.unila.ac.id',
            'phone' => '081234567890',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $oldPlace->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('student.relocations.store'), [
                'internship_enrollment_id' => $enrollment->id,
                'new_internship_place_id' => $newPlace->id,
                'reason' => 'Instansi lama tidak dapat menerima mahasiswa.',
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('relocation_requests', [
            'internship_enrollment_id' => $enrollment->id,
            'new_internship_place_id' => $newPlace->id,
            'status' => 'pending',
        ]);

        $request = \App\Models\RelocationRequest::query()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('management.relocations.update', $request), [
                'status' => 'approved',
                'admin_note' => 'Disetujui.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_enrollments', [
            'id' => $enrollment->id,
            'internship_place_id' => $newPlace->id,
        ]);
    }

    public function test_student_cannot_register_again_when_period_enrollment_is_not_cancelled_or_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'SI', 'name' => 'Sistem Informasi', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Aktif Ulang', 'academic_year' => '2026/2027', 'is_active' => true]);
        $place = InternshipPlace::query()->create(['name' => 'Tempat Awal', 'is_active' => true]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051005',
            'full_name' => 'Mahasiswa Sudah Daftar',
            'student_email' => '2217051005@student.unila.ac.id',
            'phone' => '081234567890',
        ]);

        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->from(route('student.enrollments.create'))
            ->post(route('student.enrollments.store'), $this->validEnrollmentPayload($period, $program, $place))
            ->assertRedirect(route('student.enrollments.create'))
            ->assertSessionHasErrors('internship_period_id');

        $this->assertDatabaseCount('internship_enrollments', 1);
    }

    public function test_student_can_register_again_when_previous_period_enrollment_was_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'MI', 'name' => 'Manajemen Informatika', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Ditolak', 'academic_year' => '2026/2027', 'is_active' => true]);
        $oldPlace = InternshipPlace::query()->create(['name' => 'Tempat Ditolak', 'is_active' => true]);
        $newPlace = InternshipPlace::query()->create(['name' => 'Tempat Baru Daftar', 'is_active' => true]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051006',
            'full_name' => 'Mahasiswa Daftar Ulang',
            'student_email' => '2217051006@student.unila.ac.id',
            'phone' => '081234567890',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $oldPlace->id,
            'status' => 'rejected',
            'admin_note' => 'Ditolak.',
        ]);

        $this->actingAs($user)
            ->post(route('student.enrollments.store'), $this->validEnrollmentPayload($period, $program, $newPlace))
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseCount('internship_enrollments', 1);
        $this->assertDatabaseHas('internship_enrollments', [
            'id' => $enrollment->id,
            'internship_place_id' => $newPlace->id,
            'status' => 'pending_verification',
            'admin_note' => null,
        ]);
    }

    public function test_student_enrollment_minimum_sks_follows_study_program_degree_level(): void
    {
        Storage::fake('public');

        $period = InternshipPeriod::query()->create(['name' => 'Periode Syarat Jenjang', 'academic_year' => '2026/2027', 'is_active' => true]);
        $place = InternshipPlace::query()->create(['name' => 'Tempat Syarat Jenjang', 'is_active' => true]);

        $s1User = User::factory()->create(['role' => 'mahasiswa']);
        $s1Program = StudyProgram::query()->create(['code' => 'S1UJI', 'name' => 'S1 Uji', 'degree_level' => 'S1', 'is_active' => true]);
        Student::query()->create([
            'user_id' => $s1User->id,
            'study_program_id' => $s1Program->id,
            'npm' => '2217051991',
            'full_name' => 'Mahasiswa S1',
            'student_email' => '2217051991@student.unila.ac.id',
            'phone' => '081234567890',
        ]);

        $this->actingAs($s1User)
            ->from(route('student.enrollments.create'))
            ->post(route('student.enrollments.store'), array_merge($this->validEnrollmentPayload($period, $s1Program, $place), [
                'total_sks' => 99,
                'current_semester' => 6,
            ]))
            ->assertRedirect(route('student.enrollments.create'))
            ->assertSessionHasErrors('total_sks');

        $d3User = User::factory()->create(['role' => 'mahasiswa']);
        $d3Program = StudyProgram::query()->create(['code' => 'D3UJI', 'name' => 'D3 Uji', 'degree_level' => 'D3', 'is_active' => true]);
        $d3Student = Student::query()->create([
            'user_id' => $d3User->id,
            'study_program_id' => $d3Program->id,
            'npm' => '2207051992',
            'full_name' => 'Mahasiswa D3',
            'student_email' => '2207051992@student.unila.ac.id',
            'phone' => '081234567891',
        ]);

        $this->actingAs($d3User)
            ->post(route('student.enrollments.store'), array_merge($this->validEnrollmentPayload($period, $d3Program, $place), [
                'total_sks' => 80,
                'current_semester' => 4,
            ]))
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('internship_enrollments', [
            'student_id' => $d3Student->id,
            'study_program_id' => $d3Program->id,
            'total_sks' => 80,
            'current_semester' => 4,
            'status' => 'pending_verification',
        ]);
    }

    public function test_student_can_revise_enrollment_when_revision_is_required(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Revisi', 'academic_year' => '2026/2027', 'is_active' => true]);
        $oldPlace = InternshipPlace::query()->create(['name' => 'Tempat Lama Revisi', 'is_active' => true]);
        $newPlace = InternshipPlace::query()->create(['name' => 'Tempat Baru Revisi', 'is_active' => true]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051007',
            'full_name' => 'Mahasiswa Revisi',
            'student_email' => '2217051007@student.unila.ac.id',
            'phone' => '081234567890',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $oldPlace->id,
            'contact_student_phone' => '080000000000',
            'has_krs_pkl' => true,
            'total_sks' => 100,
            'current_semester' => 6,
            'gpa' => 3.10,
            'status' => 'revision_required',
            'admin_note' => 'Lengkapi nomor pembimbing lapangan.',
        ]);

        $this->actingAs($user)
            ->get(route('student.enrollments.edit', $enrollment))
            ->assertOk()
            ->assertSee('Revisi Pendaftaran Program')
            ->assertSee('Lengkapi nomor pembimbing lapangan.');

        $this->actingAs($user)
            ->patch(route('student.enrollments.update', $enrollment), array_merge($this->validEnrollmentPayload($period, $program, $newPlace), [
                'contact_student_phone' => '081299999999',
                'field_supervisor' => 'Pembimbing Baru',
                'field_supervisor_phone' => '081211111111',
                'registration_document' => UploadedFile::fake()->create('bukti-revisi.pdf', 128, 'application/pdf'),
            ]))
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('internship_enrollments', [
            'id' => $enrollment->id,
            'internship_place_id' => $newPlace->id,
            'contact_student_phone' => '081299999999',
            'field_supervisor' => 'Pembimbing Baru',
            'field_supervisor_phone' => '081211111111',
            'status' => 'pending_verification',
            'admin_note' => null,
        ]);
        $this->assertNotNull($enrollment->fresh()->registration_document_path);
    }

    public function test_student_can_request_supervisor_completion_and_print_after_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Pembimbing', 'academic_year' => '2026/2027', 'is_active' => true]);
        $place = InternshipPlace::query()->create(['name' => 'PT Pembimbing', 'latitude' => -5.4, 'longitude' => 105.2, 'is_active' => true]);
        $lecturerUser = User::factory()->create(['role' => 'dosen']);
        $lecturer = Lecturer::query()->create(['user_id' => $lecturerUser->id, 'name' => 'Dosen Baru', 'status' => 'active']);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051008',
            'full_name' => 'Mahasiswa Pembimbing',
            'student_email' => '2217051008@student.unila.ac.id',
            'phone' => '081234567890',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('student.reports.show', $enrollment))
            ->assertOk()
            ->assertSee('Ajukan Pembimbing');

        $this->actingAs($user)
            ->post(route('student.supervisor-requests.store'), [
                'internship_enrollment_id' => $enrollment->id,
                'requested_lecturer_supervisor_id' => $lecturer->id,
                'requested_field_supervisor' => 'Pembimbing Lapangan Baru',
                'requested_field_supervisor_phone' => '081211111111',
                'reason' => 'Melengkapi data pembimbing untuk laporan.',
            ])
            ->assertRedirect(route('student.dashboard'));

        $request = \App\Models\SupervisorChangeRequest::query()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('management.supervisor-requests.update', $request), [
                'status' => 'approved',
                'lecturer_supervisor_id' => $lecturer->id,
                'field_supervisor' => 'Pembimbing Lapangan Baru',
                'field_supervisor_phone' => '081211111111',
                'admin_note' => 'Disetujui.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_enrollments', [
            'id' => $enrollment->id,
            'lecturer_supervisor_id' => $lecturer->id,
            'lecturer_supervisor_user_id' => $lecturerUser->id,
            'lecturer_supervisor' => 'Dosen Baru',
            'field_supervisor' => 'Pembimbing Lapangan Baru',
            'field_supervisor_phone' => '081211111111',
        ]);

        $this->actingAs($user)
            ->get(route('student.reports.print', $enrollment))
            ->assertOk();
    }

    public function test_student_can_upload_progress_with_late_sanction_and_lecturer_can_review(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $lecturerUser = User::factory()->create(['role' => 'dosen']);
        $lecturer = Lecturer::query()->create(['user_id' => $lecturerUser->id, 'name' => 'Dosen Review', 'status' => 'active']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Progres', 'academic_year' => '2026/2027']);
        PeriodDeadline::query()->create([
            'internship_period_id' => $period->id,
            'deadline_type' => 'bab1',
            'deadline_date' => now()->subDays(2)->toDateString(),
            'penalty_points' => 5,
        ]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051011',
            'full_name' => 'Mahasiswa Progres',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'lecturer_supervisor_id' => $lecturer->id,
            'lecturer_supervisor_user_id' => $lecturerUser->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('student.reports.progress.store', $enrollment), [
                'deadline_type' => 'bab1',
                'file' => UploadedFile::fake()->create('bab1.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect();

        $progress = SubmissionProgress::query()->firstOrFail();
        $this->assertSame('pending', $progress->status);
        $this->assertSame(10, $progress->sanction_points);
        $this->assertSame(10, $enrollment->fresh()->total_sanctions_points);
        $this->assertDatabaseHas('sanctions', [
            'internship_enrollment_id' => $enrollment->id,
            'submission_progress_id' => $progress->id,
            'sanction_type' => 'late_submission',
            'points_deducted' => 10,
        ]);

        $this->actingAs($user)
            ->get(route('submission-progress.file', $progress))
            ->assertOk();

        $this->actingAs($lecturerUser)
            ->get(route('submission-progress.file', $progress))
            ->assertOk();

        $otherStudentUser = User::factory()->create(['role' => 'mahasiswa']);
        $this->actingAs($otherStudentUser)
            ->get(route('submission-progress.file', $progress))
            ->assertForbidden();

        $this->actingAs($lecturerUser)
            ->get(route('management.submission-progress.index'))
            ->assertOk()
            ->assertSee('Mahasiswa Progres');

        $this->actingAs($lecturerUser)
            ->patch(route('management.submission-progress.update', $progress), [
                'status' => 'revision_required',
                'lecturer_note' => 'Perbaiki bagian rumusan masalah.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submission_progress', [
            'id' => $progress->id,
            'status' => 'revision_required',
            'lecturer_note' => 'Perbaiki bagian rumusan masalah.',
            'reviewed_by' => $lecturerUser->id,
        ]);

        $this->actingAs($user)
            ->post(route('student.reports.progress.store', $enrollment), [
                'deadline_type' => 'bab1',
                'file' => UploadedFile::fake()->create('bab1-revisi.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect();

        $progress->refresh();
        $this->assertSame('pending', $progress->status);
        $this->assertSame(10, $progress->sanction_points);
        $this->assertSame(10, $enrollment->fresh()->total_sanctions_points);
        $this->assertDatabaseCount('submission_progress', 1);
        $this->assertDatabaseCount('sanctions', 1);

        $this->actingAs($lecturerUser)
            ->patch(route('management.submission-progress.update', $progress), [
                'status' => 'approved',
                'lecturer_note' => 'Sudah sesuai.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submission_progress', [
            'id' => $progress->id,
            'status' => 'approved',
            'lecturer_note' => 'Sudah sesuai.',
            'reviewed_by' => $lecturerUser->id,
        ]);

        $this->actingAs($user)
            ->from(route('student.reports.show', $enrollment))
            ->post(route('student.reports.progress.store', $enrollment), [
                'deadline_type' => 'bab1',
                'file' => UploadedFile::fake()->create('bab1-setelah-approve.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect(route('student.reports.show', $enrollment))
            ->assertSessionHasErrors('deadline_type');

        $this->actingAs($lecturerUser)
            ->patch(route('management.submission-progress.update', $progress), [
                'status' => 'revision_required',
                'lecturer_note' => 'Buka ulang.',
            ])
            ->assertForbidden();
    }

    public function test_student_must_have_field_supervisor_email_before_uploading_seminar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Seminar', 'academic_year' => '2026/2027']);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051013',
            'full_name' => 'Mahasiswa Seminar',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'field_supervisor' => 'Pembimbing Lapangan',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->from(route('student.reports.show', $enrollment))
            ->post(route('student.reports.progress.store', $enrollment), [
                'deadline_type' => 'seminar',
                'file' => UploadedFile::fake()->create('seminar.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect(route('student.reports.show', $enrollment))
            ->assertSessionHasErrors('deadline_type');

        $enrollment->update(['field_supervisor_email' => 'lapangan@example.test']);

        $this->actingAs($user)
            ->post(route('student.reports.progress.store', $enrollment), [
                'deadline_type' => 'seminar',
                'file' => UploadedFile::fake()->create('seminar.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submission_progress', [
            'internship_enrollment_id' => $enrollment->id,
            'deadline_type' => 'seminar',
            'status' => 'pending',
        ]);
    }

    public function test_student_can_print_daily_activity_from_check_in_notes(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Log', 'academic_year' => '2026/2027']);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '2217051012',
            'full_name' => 'Mahasiswa Log',
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'status' => 'active',
        ]);

        $checkIn = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'note' => 'Menyusun rencana dokumentasi kebutuhan sistem hari ini.',
            'checked_at' => '2026-06-02 08:00:00',
            'distance_meters' => 12.5,
        ]);
        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'pair_id' => $checkIn->id,
            'note' => 'Merealisasikan dokumentasi kebutuhan sistem bersama pembimbing.',
            'checked_at' => '2026-06-02 16:00:00',
            'distance_meters' => 15.25,
            'duration_minutes' => 480,
        ]);

        $this->actingAs($user)
            ->get(route('student.reports.daily-logs.print', $enrollment))
            ->assertOk()
            ->assertSee('Form Catatan Harian Program')
            ->assertSee('Rencana:')
            ->assertSee('Menyusun rencana dokumentasi kebutuhan sistem hari ini.')
            ->assertSee('Realisasi:')
            ->assertSee('Merealisasikan dokumentasi kebutuhan sistem bersama pembimbing.');
    }

    private function validEnrollmentPayload(InternshipPeriod $period, StudyProgram $program, InternshipPlace $place): array
    {
        return [
            'internship_period_id' => $period->id,
            'study_program_id' => $program->id,
            'internship_place_id' => $place->id,
            'contact_student_phone' => '081234567890',
            'has_krs_pkl' => 1,
            'total_sks' => 120,
            'current_semester' => 7,
            'gpa' => 3.25,
            'registration_document' => UploadedFile::fake()->create('bukti-akademik.pdf', 128, 'application/pdf'),
        ];
    }
}
