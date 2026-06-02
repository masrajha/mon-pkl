<?php

namespace Database\Seeders;

use App\Services\PeriodConfigurationService;
use Illuminate\Database\Seeder;

class PeriodConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        app(PeriodConfigurationService::class)->seedMissing();
    }
}
