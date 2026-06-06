<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\City;
use App\Models\InternshipCoordinator;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_pages_require_authentication(): void
    {
        $this->get(route('maps.places'))->assertRedirect(route('login'));
        $this->get(route('maps.monitoring'))->assertRedirect(route('login'));
        $this->get(route('maps.places.data'))->assertRedirect(route('login'));
        $this->get(route('maps.places.route'))->assertRedirect(route('login'));
        $this->get(route('maps.monitoring.data'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_map_pages_and_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('maps.places'))->assertOk();
        $this->actingAs($admin)->get(route('maps.monitoring'))->assertOk();
        $this->actingAs($admin)->getJson(route('maps.places.data'))->assertOk()->assertJson(['type' => 'FeatureCollection']);
        $this->actingAs($admin)->getJson(route('maps.places.route', ['start_lat' => -5.3971, 'start_lng' => 105.2668]))->assertOk();
        $this->actingAs($admin)->getJson(route('maps.monitoring.data'))->assertOk()->assertJsonStructure(['check_ins']);
    }

    public function test_monitoring_map_defaults_to_active_period_and_shows_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        InternshipPeriod::query()->create([
            'name' => 'Periode Lama',
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-06-30',
        ]);
        InternshipPeriod::query()->create([
            'name' => 'Periode Aktif',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('maps.monitoring'))
            ->assertOk()
            ->assertSee('Periode Aktif')
            ->assertSee('Hari Ini')
            ->assertSee('Data Marker');
    }

    public function test_monitoring_data_respects_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studyProgram = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051001',
            'full_name' => 'Mahasiswa Peta',
        ]);
        $period = InternshipPeriod::query()->create([
            'name' => 'Periode Aktif',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-06-30',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra Peta', 'latitude' => -5.3971, 'longitude' => 105.2668]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
        ]);

        $included = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'checked_at' => '2026-03-02 08:00:00',
            'student_latitude' => -5.3972,
            'student_longitude' => 105.2669,
            'photo_path' => 'check-in-photos/map-presensi.jpg',
        ]);
        $excluded = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'checked_at' => '2026-04-02 08:00:00',
            'student_latitude' => -5.3972,
            'student_longitude' => 105.2669,
        ]);

        $this->actingAs($admin)
            ->getJson(route('maps.monitoring.data', [
                'period_id' => $period->id,
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertJsonPath('check_ins.0.id', $included->id)
            ->assertJsonPath('check_ins.0.photo_url', route('media.public', ['path' => 'check-in-photos/map-presensi.jpg']))
            ->assertJsonMissing(['id' => $excluded->id]);
    }

    public function test_coordinator_places_map_counts_active_period_participants(): void
    {
        $user = User::factory()->create(['role' => 'dosen']);
        $studyProgram = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $lecturer = Lecturer::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Koordinator Peta',
            'status' => 'active',
        ]);
        $activePeriod = InternshipPeriod::query()->create(['name' => 'Periode Aktif', 'is_active' => true]);
        $oldPeriod = InternshipPeriod::query()->create(['name' => 'Periode Lama']);
        InternshipCoordinator::query()->create([
            'lecturer_id' => $lecturer->id,
            'internship_period_id' => $activePeriod->id,
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
        ]);
        $place = InternshipPlace::query()->create([
            'name' => 'Mitra Aktif',
            'latitude' => -5.3971,
            'longitude' => 105.2668,
            'is_active' => true,
        ]);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051099',
            'full_name' => 'Mahasiswa Koordinator',
        ]);

        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $activePeriod->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
        ]);
        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $oldPeriod->id,
            'internship_place_id' => $place->id,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get(route('maps.places'))
            ->assertOk()
            ->assertSee('Periode Aktif');

        $this->actingAs($user)
            ->getJson(route('maps.places.data'))
            ->assertOk()
            ->assertJsonPath('features.0.properties.id', $place->id)
            ->assertJsonPath('features.0.properties.enrollments_count', 1);
    }

    public function test_coordinator_places_route_uses_scoped_places_with_haversine_fallback(): void
    {
        config()->set('monpkl.routing.osrm.enabled', false);

        $user = User::factory()->create(['role' => 'dosen']);
        $studyProgram = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $otherStudyProgram = StudyProgram::query()->create(['code' => 'SI', 'name' => 'Sistem Informasi', 'is_active' => true]);
        $lecturer = Lecturer::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $studyProgram->id,
            'name' => 'Koordinator Rute',
            'status' => 'active',
        ]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Aktif', 'is_active' => true]);
        InternshipCoordinator::query()->create([
            'lecturer_id' => $lecturer->id,
            'internship_period_id' => $period->id,
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
        ]);

        $includedPlace = InternshipPlace::query()->create([
            'name' => 'Mitra Dalam Scope',
            'latitude' => -5.3971,
            'longitude' => 105.2668,
            'is_active' => true,
        ]);
        $excludedPlace = InternshipPlace::query()->create([
            'name' => 'Mitra Luar Scope',
            'latitude' => -5.4100,
            'longitude' => 105.2800,
            'is_active' => true,
        ]);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051007',
            'full_name' => 'Mahasiswa Rute',
        ]);
        $otherStudent = Student::query()->create([
            'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
            'study_program_id' => $otherStudyProgram->id,
            'npm' => '2217051008',
            'full_name' => 'Mahasiswa Luar Rute',
        ]);

        InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $includedPlace->id,
            'status' => 'active',
        ]);
        InternshipEnrollment::query()->create([
            'student_id' => $otherStudent->id,
            'study_program_id' => $otherStudyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $excludedPlace->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->getJson(route('maps.places.route', [
                'start_lat' => -5.3900,
                'start_lng' => 105.2600,
            ]))
            ->assertOk()
            ->assertJsonPath('source', 'haversine')
            ->assertJsonPath('fallback', true)
            ->assertJsonPath('stops.0.id', $includedPlace->id)
            ->assertJsonMissing(['id' => $excludedPlace->id]);

        $this->actingAs($user)
            ->get(route('maps.places', ['all_study_programs' => 1]))
            ->assertOk()
            ->assertSee('Tampilkan semua prodi');

        $this->actingAs($user)
            ->getJson(route('maps.places.data', ['all_study_programs' => 1]))
            ->assertOk()
            ->assertJsonFragment(['id' => $includedPlace->id])
            ->assertJsonFragment(['id' => $excludedPlace->id]);

        $this->actingAs($user)
            ->getJson(route('maps.places.route', [
                'all_study_programs' => 1,
                'start_lat' => -5.3900,
                'start_lng' => 105.2600,
            ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $includedPlace->id])
            ->assertJsonFragment(['id' => $excludedPlace->id]);
    }

    public function test_places_map_can_filter_by_city(): void
    {
        config()->set('monpkl.routing.osrm.enabled', false);

        $admin = User::factory()->create(['role' => 'admin']);
        $studyProgram = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Aktif', 'is_active' => true]);
        $bandarLampung = City::query()->create(['name' => 'KOTA BANDAR LAMPUNG']);
        $metro = City::query()->create(['name' => 'KOTA METRO']);
        $includedPlace = InternshipPlace::query()->create([
            'city_id' => $bandarLampung->id,
            'name' => 'Mitra Bandar Lampung',
            'latitude' => -5.3971,
            'longitude' => 105.2668,
            'is_active' => true,
        ]);
        $excludedPlace = InternshipPlace::query()->create([
            'city_id' => $metro->id,
            'name' => 'Mitra Metro',
            'latitude' => -5.1130,
            'longitude' => 105.3067,
            'is_active' => true,
        ]);
        foreach ([[$includedPlace, '2217051010'], [$excludedPlace, '2217051011']] as [$place, $npm]) {
            $student = Student::query()->create([
                'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
                'study_program_id' => $studyProgram->id,
                'npm' => $npm,
                'full_name' => 'Mahasiswa Kota '.$npm,
            ]);

            InternshipEnrollment::query()->create([
                'student_id' => $student->id,
                'study_program_id' => $studyProgram->id,
                'internship_period_id' => $period->id,
                'internship_place_id' => $place->id,
                'status' => 'active',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('maps.places'))
            ->assertOk()
            ->assertSee('Kab/Kota')
            ->assertSee('KOTA BANDAR LAMPUNG')
            ->assertSee('KOTA METRO');

        $this->actingAs($admin)
            ->getJson(route('maps.places.data', ['city_id' => $bandarLampung->id]))
            ->assertOk()
            ->assertJsonFragment(['id' => $includedPlace->id])
            ->assertJsonMissing(['id' => $excludedPlace->id]);

        $this->actingAs($admin)
            ->getJson(route('maps.places.route', [
                'city_id' => $bandarLampung->id,
                'start_lat' => -5.3900,
                'start_lng' => 105.2600,
            ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $includedPlace->id])
            ->assertJsonMissing(['id' => $excludedPlace->id]);
    }

    public function test_places_map_city_filter_falls_back_to_address_when_city_id_is_empty(): void
    {
        config()->set('monpkl.routing.osrm.enabled', false);

        $admin = User::factory()->create(['role' => 'admin']);
        $studyProgram = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $period = InternshipPeriod::query()->create(['name' => 'Periode Aktif', 'is_active' => true]);
        $includedPlace = InternshipPlace::query()->create([
            'name' => 'Mitra Alamat Bandar Lampung',
            'address' => 'Jl. Wolter Monginsidi, Bandar Lampung, Lampung',
            'latitude' => -5.3971,
            'longitude' => 105.2668,
            'is_active' => true,
        ]);
        $excludedPlace = InternshipPlace::query()->create([
            'name' => 'Mitra Alamat Metro',
            'address' => 'Jl. AH Nasution, Metro, Lampung',
            'latitude' => -5.1130,
            'longitude' => 105.3067,
            'is_active' => true,
        ]);

        foreach ([[$includedPlace, '2217051012'], [$excludedPlace, '2217051013']] as [$place, $npm]) {
            $student = Student::query()->create([
                'user_id' => User::factory()->create(['role' => 'mahasiswa'])->id,
                'study_program_id' => $studyProgram->id,
                'npm' => $npm,
                'full_name' => 'Mahasiswa Alamat '.$npm,
            ]);

            InternshipEnrollment::query()->create([
                'student_id' => $student->id,
                'study_program_id' => $studyProgram->id,
                'internship_period_id' => $period->id,
                'internship_place_id' => $place->id,
                'status' => 'active',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('maps.places'))
            ->assertOk()
            ->assertSee('Bandar Lampung')
            ->assertSee('Metro');

        $this->actingAs($admin)
            ->getJson(route('maps.places.data', ['city_id' => 'address:Bandar Lampung']))
            ->assertOk()
            ->assertJsonFragment(['id' => $includedPlace->id])
            ->assertJsonMissing(['id' => $excludedPlace->id]);
    }
}
