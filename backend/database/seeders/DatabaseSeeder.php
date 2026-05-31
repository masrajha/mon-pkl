<?php

namespace Database\Seeders;

use App\Models\InternshipPeriod;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        StudyProgram::firstOrCreate(
            ['code' => 'ILKOM'],
            [
                'name' => 'Ilmu Komputer',
                'faculty' => 'FMIPA',
                'is_active' => true,
            ],
        );

        InternshipPeriod::firstOrCreate(
            [
                'name' => 'Periode Jan 2022',
                'academic_year' => '2021/2022',
                'semester' => 'Genap',
                'batch' => 'Jan 2022',
            ],
            [
                'is_active' => true,
                'is_locked' => false,
            ],
        );

        User::query()->firstOrCreate(
            ['email' => 'admin@monpkl.local'],
            [
                'name' => 'Admin Mon PKL',
                'password' => 'password',
                'role' => 'admin',
            ],
        );

        $this->call(LecturerSeeder::class);
        $this->call(PeriodConfigurationSeeder::class);
    }
}
