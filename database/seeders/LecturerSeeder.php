<?php

namespace Database\Seeders;

use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LecturerSeeder extends Seeder
{
    public function run(): void
    {
        $studyProgram = StudyProgram::query()->firstOrCreate(
            ['code' => 'ILKOM'],
            [
                'name' => 'Ilmu Komputer',
                'faculty' => 'FMIPA',
                'is_active' => true,
            ],
        );

        foreach ($this->lecturers() as $lecturerData) {
            $email = strtolower($lecturerData['email']);
            $nip = preg_replace('/\s+/', '', $lecturerData['nip']);

            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $lecturerData['name'],
                    'password' => Str::random(40),
                    'role' => 'dosen',
                ],
            );

            if ($user->role !== 'dosen') {
                $user->update(['role' => 'dosen']);
            }

            Lecturer::query()->updateOrCreate(
                ['nip' => $nip],
                [
                    'user_id' => $user->id,
                    'study_program_id' => $studyProgram->id,
                    'name' => $lecturerData['name'],
                    'email' => $email,
                    'nidn' => null,
                    'status' => 'active',
                ],
            );
        }
    }

    private function lecturers(): array
    {
        return [
            [
                'name' => 'Dr. Aristoteles, S.Si., M.Si',
                'nip' => '19810521 200604 1 002',
                'email' => 'aristoteles.1981@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Anie Rose Irawati, S.T., M.Cs',
                'nip' => '19791031 200604 2 002',
                'email' => 'anie.roseirawati@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Bambang Hermanto, S.Kom., M.Cs',
                'nip' => '19790912 200812 1 002',
                'email' => 'bambang.hermanto@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Didik Kurniawan, S.Si., M.T',
                'nip' => '19800419 200501 1 004',
                'email' => 'didikunila@gmail.com',
            ],
            [
                'name' => 'Prof. Admi Syarif, Ph.D',
                'nip' => '19670103 199203 1 003',
                'email' => 'admi.syarif@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Dr. rer. nat. Akmal Junaidi, M.Sc',
                'nip' => '19710129 199702 1 001',
                'email' => 'akmal.junaidi@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Dwi Sakethi,S.Si., M.Kom',
                'nip' => '19680611 199802 1 001',
                'email' => 'dwijim@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Tristiyanto, S.Kom., M.I.S., Ph.D',
                'nip' => '19810414 200501 1 001',
                'email' => 'tristiyanto.1981@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Yunda Heningtyas, M.Kom',
                'nip' => '19890108 201903 2 014',
                'email' => 'yunda.heningtyas@fmipa.unila.ac.id',
            ],
        ];
    }
}
