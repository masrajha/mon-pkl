<?php

namespace Tests\Feature;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\InternshipPlaceProposal;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentWorkflowFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_can_complete_profile_register_and_propose_place(): void
    {
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

        $this->actingAs($user)
            ->post(route('student.enrollments.store'), [
                'internship_period_id' => $period->id,
                'study_program_id' => $program->id,
                'internship_place_id' => $place->id,
                'contact_student_phone' => '081234567890',
                'field_supervisor' => 'Pembimbing Lapangan',
                'field_supervisor_phone' => '081111111111',
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('internship_enrollments', [
            'student_id' => $student->id,
            'internship_period_id' => $period->id,
            'status' => 'pending_verification',
        ]);

        $this->actingAs($user)
            ->post(route('student.proposals.store'), [
                'internship_period_id' => $period->id,
                'study_program_id' => $program->id,
                'name' => 'PT Usulan Baru',
                'address' => 'Alamat usulan',
                'city_name' => 'Bandar Lampung',
                'latitude' => -5.45,
                'longitude' => 105.27,
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('internship_place_proposals', [
            'student_id' => $student->id,
            'name' => 'PT Usulan Baru',
            'status' => 'pending',
        ]);
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

        $this->actingAs($user)
            ->get(route('student.reports.print', $enrollment))
            ->assertOk();
    }
}
