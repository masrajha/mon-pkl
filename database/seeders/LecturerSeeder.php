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
        $studyProgram = StudyProgram::query()->firstOrNew(['code' => 'ILKOM']);
        $studyProgram->fill([
            'name' => 'S1 Ilmu Komputer',
            'degree_level' => 'S1',
            'faculty' => 'FMIPA',
            'is_active' => true,
        ]);

        if ($studyProgram->isDirty()) {
            $studyProgram->save();
        }

        foreach ($this->lecturers() as $lecturerData) {
            $email = Str::lower(trim($lecturerData['email']));
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

            $lecturer = Lecturer::query()
                ->where('nip', $nip)
                ->orWhere('email', $email)
                ->orWhere('user_id', $user->id)
                ->first();

            if (! $lecturer) {
                Lecturer::query()->create([
                    'user_id' => $user->id,
                    'study_program_id' => $studyProgram->id,
                    'name' => $lecturerData['name'],
                    'email' => $email,
                    'nip' => $nip,
                    'nidn' => null,
                    'status' => 'active',
                ]);

                continue;
            }

            $lecturer->fill([
                'user_id' => $lecturer->user_id ?: $user->id,
                'study_program_id' => $studyProgram->id,
                'name' => $lecturerData['name'],
                'email' => $email,
                'nip' => $nip,
                'status' => 'active',
            ]);

            if ($lecturer->isDirty()) {
                $lecturer->save();
            }
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
                'name' => 'Favorisen R. Lumbanraja, Ph.D',
                'nip' => '19830110 200812 1 002',
                'email' => 'favorisen.lumbanraja@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Febi Eka Febriansyah, M.T',
                'nip' => '19800219 200604 1 001',
                'email' => 'febi.febriansyah@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Ossy Dwi Endah Wulansari, S.Si., M.T',
                'nip' => '19740713 200312 2 002',
                'email' => 'ossy.dwiendah@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Rico Andrian, S.Si., M.Kom',
                'nip' => '19750627 200501 1 001',
                'email' => 'kangrico@gmail.com',
            ],
            [
                'name' => 'Tristiyanto, S.Kom., M.I.S., Ph.D',
                'nip' => '19810414 200501 1 001',
                'email' => 'tristiyanto.1981@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Rizky Prabowo, M.Kom',
                'nip' => '19880807 201903 1 011',
                'email' => 'rizky.prabowo@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Yunda Heningtyas, M.Kom',
                'nip' => '19890108 201903 2 014',
                'email' => 'yunda.heningtyas@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Dewi Asiah Shofiana, S.Komp., M.Kom',
                'nip' => '19950929 20201 2 2030',
                'email' => 'dewi.asiah@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Ridho Sholehurrohman, M. Mat',
                'nip' => '232111970128101',
                'email' => 'ridho.sholehurrohman@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Igit Sabda Ilman, M.Kom',
                'nip' => '232111960101101',
                'email' => 'igit.sabda@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Wartariyus, S.Kom., M.T.I',
                'nip' => '19730122 200604 1 002',
                'email' => 'wartariyus@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Rahman Taufik, S.Pd, M. Kom',
                'nip' => '19930627 202203 1 007',
                'email' => 'rahman.taufik@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Muhaqiqin, S.Kom., M.T.I.',
                'nip' => '19930525 202203 1 009',
                'email' => 'muhaqiqin@fmipa.unila.ac.id',
            ],
            [
                'name' => 'M. Iqbal Parabi, S.SI., M.T.',
                'nip' => '19901130 201504 1 002',
                'email' => 'iqbal.parabi@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Ardiansyah, S.Kom., M.Kom',
                'nip' => '19870128 201803 1 001',
                'email' => 'ardiansyah@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Astria Hijriani, S.Kom., M.Kom, Ph.D.',
                'nip' => '19810308 200812 2 002',
                'email' => 'astria.hijriani@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Yulya Muharmi, M.Kom',
                'nip' => '19920702 202406 2 001',
                'email' => 'yulya.muharmi@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Muhammad Afdhaluddin, M.Kom.',
                'nip' => '19960318 202406 1 001',
                'email' => 'mafdhal.uddin@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Istiana Ruswita, M.Kom.',
                'nip' => '19901026 202406 2 001',
                'email' => 'istiana.ruswita@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Muhammad Ikhsan, S.Kom., M.Cs.',
                'nip' => '19941101 202406 1 002',
                'email' => 'muhikhsan@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Dhella Amelia, M.Kom.',
                'nip' => '19900127 202406 2 001',
                'email' => 'dhellaamelia@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Riska Amalia Praptiwi, S.Kom., M.Cs.',
                'nip' => '19930702 202406 2 001',
                'email' => 'riskamaliatiwi93@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Sandi Badiwibowo Atim, M.Kom.',
                'nip' => '19900603 202406 1 002',
                'email' => 'sandibadiwibowoatim@fmipa.unila.ac.id',
            ],
            [
                'name' => 'M. Yhogha Ismail Ibn Ibrahim, S. Kom., M.T.I.',
                'nip' => '19960403 202406 1 001',
                'email' => 'Yogaismail@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Wahyu Aji Pulungan, S.T., M.T.I.',
                'nip' => '19970108 202406 1 003',
                'email' => 'wahyuaji@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Muhammad Galih Ramaputra, S.Kom., M.T.I',
                'nip' => '19930319 202406 1 001',
                'email' => 'galih.ramaputra@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Erin Eka Citra, M.Kom',
                'nip' => '19970516 202406 2 002',
                'email' => 'erinekacitra@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Allwine, S.Pd., S.Kom., M.Kom',
                'nip' => '19910510 202406 1 002',
                'email' => 'allwine@fmipa.unila.ac.id',
            ],
            [
                'name' => 'Agung Pambudi, M.Kom.',
                'nip' => '199709132025061006',
                'email' => 'agungpambudi@fmipa.unila.ac.id',
            ],
        ];
    }
}
