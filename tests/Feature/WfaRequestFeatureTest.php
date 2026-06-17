<?php

namespace Tests\Feature;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\WfaRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WfaRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_wfa_request_with_required_evidence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-17 08:00:00', config('monpkl.timezone')));
        Storage::fake('public');

        [$user, $enrollment] = $this->activeEnrollment();

        $this->actingAs($user)
            ->get(route('student.wfa-requests.create'))
            ->assertOk()
            ->assertSee('Ajukan WFA');

        $response = $this->actingAs($user)
            ->post(route('student.wfa-requests.store'), [
                'internship_enrollment_id' => $enrollment->id,
                'starts_at' => '2026-06-18',
                'ends_at' => '2026-06-19',
                'planned_location' => 'Rumah mahasiswa',
                'planned_latitude' => '-5.3971000',
                'planned_longitude' => '105.2668000',
                'planned_activity' => 'Mengerjakan dokumentasi dan sinkronisasi laporan dengan mitra.',
                'reason' => 'Instruksi mitra untuk WFA pada rentang tanggal tersebut.',
                'evidence_file' => UploadedFile::fake()->create('instruksi-wfa.pdf', 128, 'application/pdf'),
            ]);

        $response->assertRedirect(route('student.wfa-requests.index'));

        $wfaRequest = WfaRequest::query()->firstOrFail();

        $this->assertSame($enrollment->id, $wfaRequest->internship_enrollment_id);
        $this->assertSame('pending', $wfaRequest->status);
        $this->assertSame('2026-06-18', $wfaRequest->starts_at->toDateString());
        $this->assertSame('2026-06-19', $wfaRequest->ends_at->toDateString());
        Storage::disk('public')->assertExists($wfaRequest->evidence_path);

        $this->actingAs($user)
            ->get(route('student.wfa-requests.index'))
            ->assertOk()
            ->assertSee('Rumah mahasiswa')
            ->assertSee('Buka bukti');

        Carbon::setTestNow();
    }

    public function test_wfa_request_requires_evidence_file(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-17 08:00:00', config('monpkl.timezone')));

        [$user, $enrollment] = $this->activeEnrollment();

        $this->actingAs($user)
            ->from(route('student.wfa-requests.create'))
            ->post(route('student.wfa-requests.store'), [
                'internship_enrollment_id' => $enrollment->id,
                'starts_at' => '2026-06-18',
                'ends_at' => '2026-06-18',
                'planned_location' => 'Rumah mahasiswa',
                'planned_activity' => 'Mengerjakan laporan.',
                'reason' => 'Instruksi mitra.',
            ])
            ->assertRedirect(route('student.wfa-requests.create'))
            ->assertSessionHasErrors('evidence_file');

        $this->assertDatabaseCount('wfa_requests', 0);

        Carbon::setTestNow();
    }

    public function test_admin_can_approve_wfa_request(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-17 08:00:00', config('monpkl.timezone')));
        Storage::fake('public');

        [$user, $enrollment] = $this->activeEnrollment();
        $admin = User::factory()->create(['role' => 'admin']);

        $wfaRequest = WfaRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'starts_at' => '2026-06-18',
            'ends_at' => '2026-06-18',
            'planned_location' => 'Rumah mahasiswa',
            'planned_activity' => 'Mengerjakan laporan dan koordinasi daring.',
            'reason' => 'Instruksi mitra untuk WFA.',
            'evidence_path' => 'wfa-evidence/instruksi.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('management.wfa-requests.index'))
            ->assertOk()
            ->assertSee('Rumah mahasiswa')
            ->assertSee('Mahasiswa WFA');

        $this->actingAs($admin)
            ->post(route('wfa-requests.management.approve', $wfaRequest), [
                'review_note' => 'Bukti valid.',
            ])
            ->assertRedirect();

        $wfaRequest->refresh();

        $this->assertSame('approved', $wfaRequest->status);
        $this->assertSame($admin->id, $wfaRequest->reviewed_by);
        $this->assertSame('Bukti valid.', $wfaRequest->review_note);

        Carbon::setTestNow();
    }

    private function activeEnrollment(): array
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $program = Program::query()->firstOrCreate(['code' => 'KP'], ['name' => 'Kerja Praktik', 'is_active' => true]);
        $studyProgram = StudyProgram::query()->firstOrCreate(['code' => 'IF-WFA'], ['name' => 'S1 Ilmu Komputer WFA', 'is_active' => true]);
        $period = InternshipPeriod::query()->create([
            'program_id' => $program->id,
            'name' => 'Periode WFA',
            'starts_at' => '2026-06-17',
            'ends_at' => '2026-06-30',
            'is_active' => true,
            'is_locked' => false,
        ]);
        $student = Student::query()->create([
            'user_id' => $user->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2317051999',
            'full_name' => 'Mahasiswa WFA',
        ]);
        $place = InternshipPlace::query()->create(['name' => 'Mitra WFA']);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'status' => 'active',
            'attendance_starts_at' => '2026-06-17',
            'attendance_ends_at' => '2026-06-30',
        ]);

        return [$user, $enrollment];
    }
}
