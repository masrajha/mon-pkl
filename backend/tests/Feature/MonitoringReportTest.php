<?php

namespace Tests\Feature;

use App\Models\User;
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
            ->assertSee('Rekapitulasi Monitoring PKL');
    }
}
