<?php

namespace Tests\Feature;

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
        $this->get(route('maps.monitoring.data'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_map_pages_and_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('maps.places'))->assertOk();
        $this->actingAs($admin)->get(route('maps.monitoring'))->assertOk();
        $this->actingAs($admin)->getJson(route('maps.places.data'))->assertOk()->assertJson(['type' => 'FeatureCollection']);
        $this->actingAs($admin)->getJson(route('maps.monitoring.data'))->assertOk()->assertJsonStructure(['check_ins']);
    }
}
