<?php

namespace Database\Seeders;

use App\Models\InternshipPeriod;
use App\Models\Program;
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
        $activityProgram = Program::firstOrCreate(
            ['code' => 'KP'],
            [
                'name' => 'Kerja Praktik',
                'description' => 'Program Kerja Praktik dengan rule operasional default.',
                'rule_key' => 'kerja_praktik',
                'is_active' => true,
            ],
        );

        foreach ([
            ['code' => 'MAGANG', 'name' => 'Magang'],
            ['code' => 'RISET', 'name' => 'Riset'],
        ] as $program) {
            Program::firstOrCreate(
                ['code' => $program['code']],
                [
                    'name' => $program['name'],
                    'description' => 'Program MBKM. Sementara memakai rule Kerja Praktik.',
                    'rule_key' => 'kerja_praktik',
                    'is_active' => true,
                ],
            );
        }

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
                'program_id' => $activityProgram->id,
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
