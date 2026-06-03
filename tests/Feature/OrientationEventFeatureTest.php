<?php

namespace Tests\Feature;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrientationEventFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_orientation_event_and_student_attends_once(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $activityProgram = Program::query()->create([
            'code' => 'MAGANG-UJI',
            'name' => 'Magang',
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
            'program_id' => $activityProgram->id,
            'name' => 'Juli 2026',
            'academic_year' => '2026/2027',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051001',
            'full_name' => 'Mahasiswa Pembekalan',
            'student_email' => '2217051001@student.unila.ac.id',
            'phone' => '081234567890',
        ]);
        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('management.orientation-events.store'), [
                'internship_period_id' => $period->id,
                'study_program_id' => $studyProgram->id,
                'location_name' => 'Aula FMIPA',
                'latitude' => -5.3640000,
                'longitude' => 105.2430000,
                'max_distance_meters' => 100,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $event = \App\Models\OrientationEvent::query()->firstOrFail();
        $this->assertSame('Pembekalan Magang Juli 2026', $event->name);

        $this->actingAs($studentUser)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Presensi Pembekalan');

        $this->actingAs($studentUser)
            ->post(route('student.orientation-attendances.store', $event), [
                'student_latitude' => -5.3640000,
                'student_longitude' => 105.2430000,
                'photo_capture' => 'data:image/jpeg;base64,'.base64_encode('photo'),
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('orientation_attendances', [
            'orientation_event_id' => $event->id,
            'student_id' => $student->id,
            'distance_meters' => 0,
        ]);

        $this->actingAs($studentUser)
            ->from(route('student.orientation-attendances.create', $event))
            ->post(route('student.orientation-attendances.store', $event), [
                'student_latitude' => -5.3640000,
                'student_longitude' => 105.2430000,
                'photo_capture' => 'data:image/jpeg;base64,'.base64_encode('photo'),
            ])
            ->assertRedirect(route('student.orientation-attendances.create', $event))
            ->assertSessionHasErrors('student_latitude');

        $this->actingAs($admin)
            ->get(route('management.orientation-events.show', $event))
            ->assertOk()
            ->assertSee('Mahasiswa Pembekalan')
            ->assertSee('Presensi')
            ->assertSee('0 m');
    }
}
