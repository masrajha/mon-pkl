<?php

namespace Tests\Feature;

use App\Models\InternshipPeriod;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPlace;
use App\Models\InternshipPlaceProposal;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManagementFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_management_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('management.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('management.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.students.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.lecturers.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.coordinators.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.study-programs.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.programs.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.periods.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.places.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.enrollments.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.enrollment-validations.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.supervisor-requests.index'))->assertOk();
    }

    public function test_non_admin_cannot_access_management(): void
    {
        $user = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($user)->get(route('management.dashboard'))->assertForbidden();
    }

    public function test_admin_can_create_period_student_and_enrollment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activityProgram = Program::query()->where('code', 'KP')->firstOrFail();
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $lecturerUser = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Uji']);
        $place = InternshipPlace::query()->create(['name' => 'PT Uji', 'latitude' => -5.4, 'longitude' => 105.2]);

        $this->actingAs($admin)
            ->post(route('management.study-programs.store'), [
                'code' => 'TIF',
                'name' => 'Teknik Informatika',
                'degree_level' => 'S1',
                'faculty' => 'FT',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $program = StudyProgram::query()->where('code', 'TIF')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('management.lecturers.store'), [
                'user_id' => $lecturerUser->id,
                'study_program_id' => $program->id,
                'name' => 'Dosen Uji',
                'email' => 'dosen.uji@example.test',
                'nip' => '198001012006041001',
                'nidn' => '0001018001',
                'status' => 'active',
            ])
            ->assertRedirect();

        $lecturer = Lecturer::query()->where('nidn', '0001018001')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('management.periods.store'), [
                'name' => 'Periode Uji',
                'program_id' => $activityProgram->id,
                'academic_year' => '2026/2027',
                'semester' => 'Ganjil',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $period = InternshipPeriod::query()->where('name', 'Periode Uji')->firstOrFail();
        $this->assertSame($activityProgram->id, $period->program_id);

        $this->actingAs($admin)
            ->post(route('management.students.store'), [
                'user_id' => $studentUser->id,
                'study_program_id' => $program->id,
                'npm' => '2217051999',
                'full_name' => 'Mahasiswa Uji',
            ])
            ->assertRedirect();

        $student = Student::query()->where('npm', '2217051999')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('management.enrollments.store'), [
                'student_id' => $student->id,
                'internship_period_id' => $period->id,
                'internship_place_id' => $place->id,
                'attendance_starts_at' => '2026-06-20',
                'attendance_ends_at' => '2026-08-10',
                'lecturer_supervisor_id' => $lecturer->id,
                'field_supervisor' => 'Pembimbing Lapangan',
                'field_supervisor_phone' => '081111111111',
                'contact_student_phone' => '08123456789',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_enrollments', [
            'student_id' => $student->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'attendance_starts_at' => '2026-06-20 00:00:00',
            'attendance_ends_at' => '2026-08-10 00:00:00',
            'lecturer_supervisor_id' => $lecturer->id,
            'lecturer_supervisor_user_id' => $lecturerUser->id,
            'lecturer_supervisor' => $lecturer->name,
            'field_supervisor_phone' => '081111111111',
        ]);
    }

    public function test_admin_enrollment_student_search_and_store_use_student_study_program(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentProgram = StudyProgram::query()->create(['code' => 'IK', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $otherProgram = StudyProgram::query()->create(['code' => 'SI', 'name' => 'Sistem Informasi', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Search', 'academic_year' => '2026/2027']);
        $student = Student::query()->create([
            'npm' => '2217051771',
            'full_name' => 'Mahasiswa Search',
            'study_program_id' => $studentProgram->id,
        ]);

        $this->actingAs($admin)
            ->getJson(route('management.enrollments.students.search', ['q' => '1771']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $student->id,
                'npm' => '2217051771',
                'study_program_name' => 'Ilmu Komputer',
            ]);

        $this->actingAs($admin)
            ->post(route('management.enrollments.store'), [
                'student_id' => $student->id,
                'study_program_id' => $otherProgram->id,
                'internship_period_id' => $period->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_enrollments', [
            'student_id' => $student->id,
            'study_program_id' => $studentProgram->id,
            'internship_period_id' => $period->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_manage_activity_programs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('management.programs.store'), [
                'code' => 'MAGANG',
                'name' => 'Magang',
                'description' => 'Program magang MBKM.',
                'rule_key' => 'kerja_praktik',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $program = Program::query()->where('code', 'MAGANG')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('management.programs.update', $program), [
                'code' => 'MAGANG',
                'name' => 'Magang Industri',
                'description' => 'Program magang MBKM.',
                'rule_key' => 'kerja_praktik',
                'is_active' => 1,
            ])
            ->assertRedirect(route('management.programs.index'));

        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'name' => 'Magang Industri',
            'rule_key' => 'kerja_praktik',
        ]);
    }

    public function test_admin_can_validate_pending_enrollment_from_validation_page(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Validasi', 'academic_year' => '2026/2027']);
        $student = Student::query()->create(['npm' => '2217051222', 'full_name' => 'Mahasiswa Validasi', 'study_program_id' => $program->id]);
        $lecturer = Lecturer::query()->create(['name' => 'Dosen Validasi', 'status' => 'active']);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'status' => 'pending_verification',
            'registration_document_path' => UploadedFile::fake()->create('bukti.pdf', 128, 'application/pdf')->store('registration-documents', 'public'),
        ]);

        $this->actingAs($admin)
            ->get(route('management.enrollment-validations.index'))
            ->assertOk()
            ->assertSee('Buka dokumen');

        $this->actingAs($admin)
            ->get(route('management.enrollment-validations.document', $enrollment))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('management.enrollment-validations.update', $enrollment), [
                'status' => 'active',
                'lecturer_supervisor_id' => $lecturer->id,
                'field_supervisor' => 'Pembimbing Lapangan',
                'admin_note' => 'Disetujui.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_enrollments', [
            'id' => $enrollment->id,
            'status' => 'active',
            'lecturer_supervisor_id' => $lecturer->id,
            'field_supervisor' => 'Pembimbing Lapangan',
        ]);
    }

    public function test_coordinator_can_approve_supervisor_change_within_assignment_scope(): void
    {
        $coordinatorUser = User::factory()->create(['role' => 'koordinator']);
        $lecturerUser = User::factory()->create(['role' => 'dosen']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Scope Pembimbing', 'academic_year' => '2026/2027']);
        $coordinatorLecturer = Lecturer::query()->create([
            'user_id' => $coordinatorUser->id,
            'study_program_id' => $program->id,
            'name' => 'Koordinator Scope',
            'status' => 'active',
        ]);
        $supervisor = Lecturer::query()->create([
            'user_id' => $lecturerUser->id,
            'study_program_id' => $program->id,
            'name' => 'Dosen Scope',
            'status' => 'active',
        ]);
        InternshipCoordinator::query()->create([
            'lecturer_id' => $coordinatorLecturer->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $program->id,
            'status' => 'active',
        ]);
        $student = Student::query()->create(['npm' => '2217051444', 'full_name' => 'Mahasiswa Scope', 'study_program_id' => $program->id]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'status' => 'active',
        ]);
        $request = \App\Models\SupervisorChangeRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'requested_field_supervisor' => 'Pembimbing Scope',
            'reason' => 'Melengkapi pembimbing.',
            'status' => 'pending',
        ]);

        $this->actingAs($coordinatorUser)
            ->get(route('management.supervisor-requests.index'))
            ->assertOk()
            ->assertSee('Mahasiswa Scope');

        $this->actingAs($coordinatorUser)
            ->patch(route('management.supervisor-requests.update', $request), [
                'status' => 'approved',
                'lecturer_supervisor_id' => $supervisor->id,
                'field_supervisor' => 'Pembimbing Scope',
                'admin_note' => 'Disetujui koordinator.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_enrollments', [
            'id' => $enrollment->id,
            'lecturer_supervisor_id' => $supervisor->id,
            'field_supervisor' => 'Pembimbing Scope',
        ]);
    }

    public function test_coordinator_sees_action_required_summary_and_scoped_place_proposals(): void
    {
        $coordinatorUser = User::factory()->create(['role' => 'koordinator']);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $scopeProgram = StudyProgram::query()->create(['code' => 'SCP', 'name' => 'Scope Prodi', 'is_active' => true]);
        $otherProgram = StudyProgram::query()->create(['code' => 'OTH', 'name' => 'Other Prodi', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Scope Action', 'academic_year' => '2026/2027']);
        $coordinatorLecturer = Lecturer::query()->create([
            'user_id' => $coordinatorUser->id,
            'study_program_id' => $scopeProgram->id,
            'name' => 'Koordinator Action',
            'status' => 'active',
        ]);
        InternshipCoordinator::query()->create([
            'lecturer_id' => $coordinatorLecturer->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $scopeProgram->id,
            'status' => 'active',
        ]);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'npm' => '2217051991',
            'full_name' => 'Mahasiswa Proposal Scope',
            'study_program_id' => $scopeProgram->id,
        ]);
        $proposal = InternshipPlaceProposal::query()->create([
            'internship_period_id' => $period->id,
            'study_program_id' => $scopeProgram->id,
            'student_id' => $student->id,
            'proposed_by' => $studentUser->id,
            'name' => 'PT Scope Action',
            'address' => 'Alamat scope',
            'city_name' => 'Bandar Lampung',
            'latitude' => -5.4,
            'longitude' => 105.2,
            'status' => 'pending',
        ]);
        InternshipPlaceProposal::query()->create([
            'internship_period_id' => $period->id,
            'study_program_id' => $otherProgram->id,
            'student_id' => $student->id,
            'proposed_by' => $studentUser->id,
            'name' => 'PT Luar Scope',
            'address' => 'Alamat luar',
            'status' => 'pending',
        ]);

        $this->actingAs($coordinatorUser)
            ->get(route('coordinator.dashboard'))
            ->assertOk()
            ->assertSee('Tindakan Diperlukan')
            ->assertSee('Usulan Tempat')
            ->assertSee('1 pengajuan menunggu tindakan.');

        $this->actingAs($coordinatorUser)
            ->get(route('management.place-proposals.index'))
            ->assertOk()
            ->assertSee('PT Scope Action')
            ->assertDontSee('PT Luar Scope');

        $this->actingAs($coordinatorUser)
            ->post(route('management.place-proposals.approve', $proposal), [
                'mode' => 'new',
                'admin_note' => 'Disetujui koordinator.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_place_proposals', [
            'id' => $proposal->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_assign_lecturer_as_coordinator_without_changing_dosen_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturerUser = User::factory()->create(['role' => 'dosen']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $secondProgram = StudyProgram::query()->create(['code' => 'SI', 'name' => 'Sistem Informasi', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Koordinator', 'academic_year' => '2026/2027']);
        $lecturer = Lecturer::query()->create([
            'user_id' => $lecturerUser->id,
            'study_program_id' => $program->id,
            'name' => $lecturerUser->name,
            'email' => $lecturerUser->email,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('management.coordinators.store'), [
                'lecturer_id' => $lecturer->id,
                'internship_period_id' => $period->id,
                'study_program_ids' => [$program->id, $secondProgram->id],
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_coordinators', [
            'lecturer_id' => $lecturer->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $program->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('internship_coordinators', [
            'lecturer_id' => $lecturer->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $secondProgram->id,
            'status' => 'active',
        ]);
        $this->assertTrue($lecturerUser->fresh()->hasRole('dosen'));
        $this->assertTrue($lecturerUser->fresh()->hasRole('koordinator'));
        $this->actingAs($lecturerUser)->get(route('coordinator.dashboard'))->assertOk();
    }

    public function test_admin_can_complete_period_and_complete_active_enrollments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'name' => 'Periode Selesai',
            'academic_year' => '2026/2027',
            'is_active' => true,
            'is_locked' => false,
        ]);
        $activeStudent = Student::query()->create(['npm' => '2217051666', 'full_name' => 'Mahasiswa Aktif', 'study_program_id' => $program->id]);
        $pendingStudent = Student::query()->create(['npm' => '2217051667', 'full_name' => 'Mahasiswa Pending', 'study_program_id' => $program->id]);

        InternshipEnrollment::query()->create([
            'student_id' => $activeStudent->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'status' => 'active',
        ]);
        InternshipEnrollment::query()->create([
            'student_id' => $pendingStudent->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'status' => 'pending_verification',
        ]);

        $this->actingAs($admin)
            ->post(route('management.periods.complete', $period))
            ->assertRedirect(route('management.periods.index'));

        $this->assertDatabaseHas('internship_periods', [
            'id' => $period->id,
            'is_active' => false,
            'is_locked' => true,
        ]);
        $this->assertDatabaseHas('internship_enrollments', [
            'student_id' => $activeStudent->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('internship_enrollments', [
            'student_id' => $pendingStudent->id,
            'status' => 'pending_verification',
        ]);
    }

    public function test_place_bulk_delete_only_allows_places_without_enrollments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Uji', 'academic_year' => '2026/2027']);
        $student = Student::query()->create(['npm' => '2217051888', 'full_name' => 'Mahasiswa Uji', 'study_program_id' => $program->id]);
        $usedPlace = InternshipPlace::query()->create(['name' => 'Tempat Terpakai']);
        $unusedPlace = InternshipPlace::query()->create(['name' => 'Tempat Kosong']);

        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $usedPlace->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('management.places.bulk'), [
                'action' => 'delete',
                'place_ids' => [$usedPlace->id],
            ])
            ->assertSessionHasErrors('place_ids');

        $this->assertDatabaseHas('internship_places', ['id' => $usedPlace->id]);

        $this->actingAs($admin)
            ->post(route('management.places.bulk'), [
                'action' => 'delete',
                'place_ids' => [$unusedPlace->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('internship_places', ['id' => $unusedPlace->id]);
    }

    public function test_admin_places_can_show_total_and_period_participant_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $activePeriod = InternshipPeriod::query()->create(['name' => 'Periode Aktif Count', 'academic_year' => '2026/2027', 'is_active' => true]);
        $oldPeriod = InternshipPeriod::query()->create(['name' => 'Periode Lama Count', 'academic_year' => '2025/2026']);
        $studentA = Student::query()->create(['npm' => '2217051901', 'full_name' => 'Mahasiswa Count A', 'study_program_id' => $program->id]);
        $studentB = Student::query()->create(['npm' => '2217051902', 'full_name' => 'Mahasiswa Count B', 'study_program_id' => $program->id]);
        $place = InternshipPlace::query()->create(['name' => 'Tempat Count', 'is_active' => true]);

        InternshipEnrollment::query()->create([
            'student_id' => $studentA->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $activePeriod->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
        ]);
        InternshipEnrollment::query()->create([
            'student_id' => $studentB->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $oldPeriod->id,
            'internship_place_id' => $place->id,
            'status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->get(route('management.places.index'))
            ->assertOk()
            ->assertSee('Peserta Total')
            ->assertSee('>2<', false);

        $this->actingAs($admin)
            ->get(route('management.places.index', ['period_id' => $activePeriod->id]))
            ->assertOk()
            ->assertSee('Peserta Periode')
            ->assertSee('>1<', false);
    }

    public function test_place_bulk_merge_moves_enrollments_to_target_and_deletes_sources(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Merge', 'academic_year' => '2026/2027']);
        $student = Student::query()->create(['npm' => '2217051777', 'full_name' => 'Mahasiswa Merge', 'study_program_id' => $program->id]);
        $targetPlace = InternshipPlace::query()->create(['name' => 'Tempat Utama']);
        $sourcePlace = InternshipPlace::query()->create(['name' => 'Tempat Duplikat']);

        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $sourcePlace->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('management.places.bulk'), [
                'action' => 'merge',
                'place_ids' => [$targetPlace->id, $sourcePlace->id],
                'target_place_id' => $targetPlace->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_enrollments', [
            'student_id' => $student->id,
            'internship_place_id' => $targetPlace->id,
        ]);
        $this->assertDatabaseMissing('internship_places', ['id' => $sourcePlace->id]);
        $this->assertDatabaseHas('internship_places', ['id' => $targetPlace->id]);
    }

    public function test_admin_can_search_places_for_management_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activePlace = InternshipPlace::query()->create(['name' => 'PT Search Management', 'address' => 'Alamat Search', 'is_active' => true]);
        InternshipPlace::query()->create(['name' => 'PT Search Nonaktif', 'is_active' => false]);

        $this->actingAs($admin)
            ->getJson(route('management.places.search', ['q' => 'Search', 'active_only' => 1]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $activePlace->id,
                'name' => 'PT Search Management',
            ])
            ->assertJsonMissing([
                'name' => 'PT Search Nonaktif',
            ]);
    }
}
