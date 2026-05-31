<?php

namespace Tests\Feature;

use App\Models\InternshipPeriod;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->actingAs($admin)->get(route('management.study-programs.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.periods.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.places.index'))->assertOk();
        $this->actingAs($admin)->get(route('management.enrollments.index'))->assertOk();
    }

    public function test_non_admin_cannot_access_management(): void
    {
        $user = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($user)->get(route('management.dashboard'))->assertForbidden();
    }

    public function test_admin_can_create_period_student_and_enrollment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $lecturerUser = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Uji']);
        $place = InternshipPlace::query()->create(['name' => 'PT Uji', 'latitude' => -5.4, 'longitude' => 105.2]);

        $this->actingAs($admin)
            ->post(route('management.study-programs.store'), [
                'code' => 'TIF',
                'name' => 'Teknik Informatika',
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
                'academic_year' => '2026/2027',
                'semester' => 'Ganjil',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $period = InternshipPeriod::query()->where('name', 'Periode Uji')->firstOrFail();

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
                'study_program_id' => $program->id,
                'internship_period_id' => $period->id,
                'internship_place_id' => $place->id,
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
            'lecturer_supervisor_id' => $lecturer->id,
            'lecturer_supervisor_user_id' => $lecturerUser->id,
            'lecturer_supervisor' => $lecturer->name,
            'field_supervisor_phone' => '081111111111',
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
}
