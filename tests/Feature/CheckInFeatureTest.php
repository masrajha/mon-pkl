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
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('public');

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
                'action' => 'check_in',
                'note' => 'Menyusun rencana dokumentasi sistem hari ini.',
                'photo_capture' => $this->capturedPhoto(),
            ])
            ->assertRedirect(route('check-ins.create'));

        $this->assertDatabaseHas('check_ins', [
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Datang Terlambat',
            'action' => 'check_in',
            'note' => 'Menyusun rencana dokumentasi sistem hari ini.',
        ]);

        $checkIn = CheckIn::query()->first();

        $this->assertGreaterThan(0, $checkIn->distance_meters);
        $this->assertNotNull($checkIn->photo_path);
        Storage::disk('public')->assertExists($checkIn->photo_path);
    }

    public function test_check_in_is_rejected_outside_working_hours(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 31, 20, 0, 0, config('monpkl.timezone')));

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Uji', 'academic_year' => '2025/2026', 'semester' => 'Genap', 'is_active' => true]);
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
                'action' => 'check_in',
                'note' => 'Menyusun rencana aktivitas pengujian hari ini.',
                'photo_capture' => $this->capturedPhoto(),
            ])
            ->assertRedirect(route('check-ins.create'))
            ->assertSessionHasErrors('student_latitude');

        $this->assertDatabaseCount('check_ins', 0);
    }

    public function test_check_out_pairs_with_check_in_and_records_duration_sanction(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'mahasiswa']);
        [$enrollment] = $this->activeEnrollmentFor($user);

        Carbon::setTestNow(Carbon::create(2026, 5, 31, 8, 0, 0, config('monpkl.timezone')));
        $this->actingAs($user)
            ->post(route('check-ins.store'), [
                'student_latitude' => -5.3972,
                'student_longitude' => 105.2669,
                'action' => 'check_in',
                'note' => 'Menyusun rencana aktivitas pengujian hari ini.',
                'photo_capture' => $this->capturedPhoto(),
            ])
            ->assertRedirect(route('check-ins.create'));

        Carbon::setTestNow(Carbon::create(2026, 5, 31, 13, 0, 0, config('monpkl.timezone')));
        $this->actingAs($user)
            ->post(route('check-ins.store'), [
                'student_latitude' => -5.3972,
                'student_longitude' => 105.2669,
                'action' => 'check_out',
                'note' => 'Menyelesaikan realisasi aktivitas pengujian hari ini.',
                'photo_capture' => $this->capturedPhoto(),
            ])
            ->assertRedirect(route('check-ins.create'));

        $checkIn = CheckIn::query()->where('action', 'check_in')->firstOrFail();
        $checkOut = CheckIn::query()->where('action', 'check_out')->firstOrFail();

        $this->assertSame($checkIn->id, $checkOut->pair_id);
        $this->assertSame(300, $checkOut->duration_minutes);
        $this->assertSame(1, $checkOut->sanction_points);
        $this->assertSame(1, $enrollment->fresh()->total_sanctions_points);
    }

    public function test_daily_check_in_pair_allows_only_one_check_in_and_one_check_out(): void
    {
        Storage::fake('public');
        Carbon::setTestNow(Carbon::create(2026, 5, 31, 8, 30, 0, config('monpkl.timezone')));

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $this->activeEnrollmentFor($user);

        $payload = [
            'student_latitude' => -5.3972,
            'student_longitude' => 105.2669,
            'action' => 'check_in',
            'note' => 'Menyusun rencana aktivitas pengujian hari ini.',
            'photo_capture' => $this->capturedPhoto(),
        ];

        $this->actingAs($user)->post(route('check-ins.store'), $payload)->assertRedirect(route('check-ins.create'));

        $this->actingAs($user)
            ->from(route('check-ins.create'))
            ->post(route('check-ins.store'), $payload)
            ->assertRedirect(route('check-ins.create'))
            ->assertSessionHasErrors('action');

        $this->assertDatabaseCount('check_ins', 1);
    }

    public function test_check_in_rejects_locations_outside_configured_radius(): void
    {
        Storage::fake('public');
        Carbon::setTestNow(Carbon::create(2026, 5, 31, 8, 30, 0, config('monpkl.timezone')));

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $this->activeEnrollmentFor($user);

        $this->actingAs($user)
            ->from(route('check-ins.create'))
            ->post(route('check-ins.store'), [
                'student_latitude' => 0,
                'student_longitude' => 0,
                'action' => 'check_in',
                'note' => 'Menyusun rencana aktivitas pengujian hari ini.',
                'photo_capture' => $this->capturedPhoto(),
            ])
            ->assertRedirect(route('check-ins.create'))
            ->assertSessionHasErrors('student_latitude');

        $this->assertDatabaseCount('check_ins', 0);
    }

    public function test_check_in_uses_enrollment_attendance_override_dates(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'mahasiswa']);
        [$enrollment] = $this->activeEnrollmentFor($user, [
            'starts_at' => '2026-06-15',
            'ends_at' => '2026-07-31',
        ], [
            'attendance_starts_at' => '2026-06-20',
            'attendance_ends_at' => '2026-08-10',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 6, 18, 8, 30, 0, config('monpkl.timezone')));
        $this->actingAs($user)
            ->from(route('check-ins.create'))
            ->post(route('check-ins.store'), [
                'student_latitude' => -5.3972,
                'student_longitude' => 105.2669,
                'action' => 'check_in',
                'note' => 'Menyusun rencana aktivitas pengujian hari ini.',
                'photo_capture' => $this->capturedPhoto(),
            ])
            ->assertRedirect(route('check-ins.create'))
            ->assertSessionHasErrors('student_latitude');

        Carbon::setTestNow(Carbon::create(2026, 8, 5, 8, 30, 0, config('monpkl.timezone')));
        $this->actingAs($user)
            ->post(route('check-ins.store'), [
                'student_latitude' => -5.3972,
                'student_longitude' => 105.2669,
                'action' => 'check_in',
                'note' => 'Menyusun rencana aktivitas pengujian hari ini.',
                'photo_capture' => $this->capturedPhoto(),
            ])
            ->assertRedirect(route('check-ins.create'));

        $this->assertDatabaseHas('check_ins', [
            'internship_enrollment_id' => $enrollment->id,
            'action' => 'check_in',
        ]);
    }

    private function activeEnrollmentFor(User $user, array $periodOverrides = [], array $enrollmentOverrides = []): array
    {
        $program = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(array_merge([
            'name' => 'Periode Uji',
            'academic_year' => '2025/2026',
            'semester' => 'Genap',
            'is_active' => true,
        ], $periodOverrides));
        $place = InternshipPlace::query()->create([
            'name' => 'Instansi Uji',
            'latitude' => -5.3971,
            'longitude' => 105.2668,
        ]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $program->id,
            'npm' => '221705'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'full_name' => $user->name,
        ]);
        $enrollment = InternshipEnrollment::query()->create(array_merge([
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
        ], $enrollmentOverrides));

        return [$enrollment, $student, $period, $place];
    }

    private function capturedPhoto(): string
    {
        return 'data:image/jpeg;base64,'.base64_encode('test-photo');
    }
}
