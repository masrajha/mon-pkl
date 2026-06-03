<?php

namespace Tests\Feature;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\Student;
use Database\Seeders\StudyProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyProgramSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_and_normalizes_study_programs_from_npm(): void
    {
        $this->seed(StudyProgramSeeder::class);

        $period = InternshipPeriod::query()->create(['name' => 'Periode Seeder']);

        $d3Student = Student::query()->create([
            'npm' => '2207051001',
            'full_name' => 'Mahasiswa D3',
            'study_program_id' => 1,
        ]);
        $ilkomStudent = Student::query()->create([
            'npm' => '2217051001',
            'full_name' => 'Mahasiswa Ilkom',
            'study_program_id' => 2,
        ]);
        $siStudent = Student::query()->create([
            'npm' => '2217052001',
            'full_name' => 'Mahasiswa SI',
            'study_program_id' => 1,
        ]);

        foreach ([$d3Student, $ilkomStudent, $siStudent] as $student) {
            InternshipEnrollment::query()->create([
                'student_id' => $student->id,
                'study_program_id' => $student->study_program_id,
                'internship_period_id' => $period->id,
            ]);
        }

        $this->seed(StudyProgramSeeder::class);

        $this->assertDatabaseHas('study_programs', ['id' => 1, 'name' => 'S1 Ilmu Komputer']);
        $this->assertDatabaseHas('study_programs', ['id' => 2, 'name' => 'S1 Sistem Informasi']);
        $this->assertDatabaseHas('study_programs', ['id' => 3, 'name' => 'D3 Manajemen Informatika']);

        $this->assertSame(3, $d3Student->fresh()->study_program_id);
        $this->assertSame(1, $ilkomStudent->fresh()->study_program_id);
        $this->assertSame(2, $siStudent->fresh()->study_program_id);

        $this->assertSame(
            [3, 1, 2],
            InternshipEnrollment::query()
                ->whereIn('student_id', [$d3Student->id, $ilkomStudent->id, $siStudent->id])
                ->orderBy('student_id')
                ->pluck('study_program_id')
                ->all(),
        );
    }
}
