<?php

namespace Database\Seeders;

use App\Models\InternshipEnrollment;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudyProgramSeeder extends Seeder
{
    private const PROGRAMS = [
        1 => ['code' => 'ILKOM', 'name' => 'S1 Ilmu Komputer', 'degree_level' => 'S1'],
        2 => ['code' => 'SI', 'name' => 'S1 Sistem Informasi', 'degree_level' => 'S1'],
        3 => ['code' => 'D3MI', 'name' => 'D3 Manajemen Informatika', 'degree_level' => 'D3'],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::PROGRAMS as $id => $program) {
                $exists = DB::table('study_programs')->where('id', $id)->exists();
                $values = [
                    'code' => $program['code'],
                    'name' => $program['name'],
                    'degree_level' => $program['degree_level'],
                    'faculty' => 'FMIPA',
                    'is_active' => true,
                    'updated_at' => now(),
                ];

                if ($exists) {
                    DB::table('study_programs')->where('id', $id)->update($values);
                } else {
                    DB::table('study_programs')->insert([
                        'id' => $id,
                        ...$values,
                        'created_at' => now(),
                    ]);
                }
            }

            $this->normalizeStudents();
            $this->normalizeEnrollments();
        });
    }

    private function normalizeStudents(): void
    {
        Student::query()
            ->select(['id', 'npm', 'study_program_id'])
            ->chunkById(500, function ($students): void {
                foreach ($students as $student) {
                    $studyProgramId = $this->studyProgramIdForNpm($student->npm);

                    if ($studyProgramId && (int) $student->study_program_id !== $studyProgramId) {
                        $student->update(['study_program_id' => $studyProgramId]);
                    }
                }
            });
    }

    private function normalizeEnrollments(): void
    {
        InternshipEnrollment::query()
            ->with('student:id,npm')
            ->select(['id', 'student_id', 'study_program_id'])
            ->chunkById(500, function ($enrollments): void {
                foreach ($enrollments as $enrollment) {
                    $studyProgramId = $this->studyProgramIdForNpm($enrollment->student?->npm);

                    if ($studyProgramId && (int) $enrollment->study_program_id !== $studyProgramId) {
                        $enrollment->update(['study_program_id' => $studyProgramId]);
                    }
                }
            });
    }

    private function studyProgramIdForNpm(?string $npm): ?int
    {
        $npm = preg_replace('/\D/', '', (string) $npm);

        if (strlen($npm) < 3) {
            return null;
        }

        $thirdDigit = $npm[2];

        if ($thirdDigit === '0') {
            return 3;
        }

        if (strlen($npm) < 7) {
            return null;
        }

        $seventhDigit = $npm[6];

        if ($thirdDigit === '1' && $seventhDigit === '1') {
            return 1;
        }

        if ($thirdDigit === '1' && $seventhDigit === '2') {
            return 2;
        }

        return null;
    }
}
