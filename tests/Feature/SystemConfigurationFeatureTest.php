<?php

namespace Tests\Feature;

use App\Models\InternshipPeriod;
use App\Models\User;
use App\Services\PeriodConfigurationService;
use Database\Seeders\PeriodConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemConfigurationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_period_configuration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $period = InternshipPeriod::query()->create([
            'name' => 'Periode Konfigurasi',
            'academic_year' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $payload = config('monpkl');
        $payload['check_in']['schedule'][1]['start'] = '08:30';
        $payload['calendar']['holidays_text'] = "2026-01-01\n2026-03-20";
        unset($payload['calendar']['holidays']);

        $this->actingAs($admin)
            ->patch(route('system-configurations.update', $period), $payload)
            ->assertRedirect(route('system-configurations.edit', $period));

        $this->assertDatabaseHas('internship_period_settings', [
            'internship_period_id' => $period->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertSame('08:30', $period->fresh('setting')->setting->settings['check_in']['schedule'][1]['start']);
        $this->assertSame(['2026-01-01', '2026-03-20'], $period->fresh('setting')->setting->settings['calendar']['holidays']);
        $this->assertSame(['2026-01-01', '2026-03-20'], app(PeriodConfigurationService::class)->forPeriod($period->fresh())['calendar']['holidays']);
    }

    public function test_admin_can_clear_period_holidays(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $period = InternshipPeriod::query()->create([
            'name' => 'Periode Libur Kosong',
            'academic_year' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $payload = config('monpkl');
        $payload['calendar']['holidays_text'] = '';
        unset($payload['calendar']['holidays']);

        $this->actingAs($admin)
            ->patch(route('system-configurations.update', $period), $payload)
            ->assertRedirect(route('system-configurations.edit', $period));

        $this->assertSame([], $period->fresh('setting')->setting->settings['calendar']['holidays']);
        $this->assertSame([], app(PeriodConfigurationService::class)->forPeriod($period->fresh())['calendar']['holidays']);
    }

    public function test_non_admin_cannot_access_system_configuration(): void
    {
        $user = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($user)
            ->get(route('system-configurations.index'))
            ->assertForbidden();
    }

    public function test_period_configuration_seeder_creates_settings_for_existing_periods(): void
    {
        $period = InternshipPeriod::query()->create([
            'name' => 'Periode Seeder',
            'academic_year' => '2026/2027',
            'semester' => 'Genap',
        ]);

        $this->seed(PeriodConfigurationSeeder::class);

        $this->assertDatabaseHas('internship_period_settings', [
            'internship_period_id' => $period->id,
        ]);
    }
}
