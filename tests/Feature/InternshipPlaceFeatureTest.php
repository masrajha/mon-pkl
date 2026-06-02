<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternshipPlaceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_internship_place_from_leaflet_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $city = City::query()->create(['name' => 'Bandar Lampung']);

        $this->actingAs($admin)
            ->post(route('internship-places.store'), [
                'name' => 'PT Contoh',
                'address' => 'Jl. Contoh',
                'city_id' => $city->id,
                'latitude' => -5.3971,
                'longitude' => 105.2668,
                'field_supervisor_name' => 'Pembimbing',
            ])
            ->assertRedirect(route('maps.places'));

        $this->assertDatabaseHas('internship_places', [
            'name' => 'PT Contoh',
            'city_id' => $city->id,
        ]);
    }

    public function test_mahasiswa_cannot_access_internship_place_form(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->get(route('internship-places.create'))
            ->assertForbidden();
    }
}
