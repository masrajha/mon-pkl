<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\FieldSupervisorAccessToken;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FieldSupervisorDailyLogValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_field_supervisor_can_validate_daily_log_from_token_portal(): void
    {
        [$enrollment, $checkOut, $checkIn] = $this->enrollmentWithDailyLog();
        $plainToken = 'token-validasi-catatan-harian';

        FieldSupervisorAccessToken::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'email' => 'pl@example.test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(7),
        ]);

        $this->get(route('field-supervisor.token', $plainToken))
            ->assertOk()
            ->assertSee('Validasi')
            ->assertSee('Rencana hari ini')
            ->assertSee('Realisasi hari ini');

        $this->post(route('field-supervisor.token.daily-logs.validate', [$plainToken, $checkOut]), [
            'note' => 'Aktivitas sesuai.',
        ])->assertRedirect();

        $this->assertDatabaseHas('check_ins', [
            'id' => $checkOut->id,
            'daily_log_validated_by_email' => 'pl@example.test',
            'daily_log_validation_mode' => 'token',
            'daily_log_validation_note' => 'Aktivitas sesuai.',
        ]);
        $this->assertDatabaseHas('check_ins', [
            'id' => $checkIn->id,
            'daily_log_validated_by_email' => 'pl@example.test',
            'daily_log_validation_mode' => 'token',
            'daily_log_validation_note' => 'Aktivitas sesuai.',
        ]);

        $studentUser = $enrollment->student->user;

        $this->actingAs($studentUser)
            ->get(route('student.reports.show', ['enrollment' => $enrollment, 'tab' => 'presensi']))
            ->assertOk()
            ->assertSee('Tervalidasi')
            ->assertSee('Aktivitas sesuai.');
    }

    public function test_field_supervisor_login_can_validate_only_own_daily_log(): void
    {
        [$enrollment, $checkOut, $checkIn] = $this->enrollmentWithDailyLog();
        $fieldSupervisor = User::factory()->create([
            'role' => 'pembimbing_lapangan',
            'email' => 'pl@example.test',
            'name' => 'Pembimbing Login',
        ]);

        $this->actingAs($fieldSupervisor)
            ->get(route('field-supervisor.index'))
            ->assertOk()
            ->assertSeeText('Mahasiswa Bimbingan')
            ->assertSee('Dashboard');

        $this->actingAs($fieldSupervisor)
            ->post(route('field-supervisor.daily-logs.validate', $checkOut), [
                'note' => 'Sudah dicek.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('check_ins', [
            'id' => $checkOut->id,
            'daily_log_validated_by_name' => 'Pembimbing Login',
            'daily_log_validated_by_email' => 'pl@example.test',
            'daily_log_validation_mode' => 'login',
        ]);
        $this->assertDatabaseHas('check_ins', [
            'id' => $checkIn->id,
            'daily_log_validated_by_name' => 'Pembimbing Login',
            'daily_log_validated_by_email' => 'pl@example.test',
            'daily_log_validation_mode' => 'login',
        ]);

        $otherSupervisor = User::factory()->create([
            'role' => 'pembimbing_lapangan',
            'email' => 'other-pl@example.test',
        ]);

        $this->actingAs($otherSupervisor)
            ->post(route('field-supervisor.daily-logs.validate', $checkOut))
            ->assertForbidden();
    }

    public function test_field_supervisor_validation_keeps_other_daily_rows_visible(): void
    {
        [$enrollment, $checkOut, $checkIn] = $this->enrollmentWithDailyLog();
        $fieldSupervisor = User::factory()->create([
            'role' => 'pembimbing_lapangan',
            'email' => 'pl@example.test',
            'name' => 'Pembimbing Login',
        ]);

        foreach ([
            ['2026-06-04 08:00:00', 'Rencana empat Juni'],
            ['2026-06-03 08:00:00', 'Rencana tiga Juni'],
        ] as [$checkedAt, $note]) {
            CheckIn::query()->create([
                'internship_enrollment_id' => $enrollment->id,
                'type' => 'Masuk',
                'action' => 'check_in',
                'note' => $note,
                'checked_at' => $checkedAt,
            ]);
        }

        $this->actingAs($fieldSupervisor)
            ->post(route('field-supervisor.daily-logs.validate', $checkOut), [
                'note' => 'Satu klik validasi satu baris.',
            ])
            ->assertRedirect();

        $this->actingAs($fieldSupervisor)
            ->get(route('field-supervisor.enrollments.show', $enrollment))
            ->assertOk()
            ->assertSee('05/06/2026')
            ->assertSee('04/06/2026')
            ->assertSee('03/06/2026')
            ->assertSee('Tervalidasi')
            ->assertSee('Satu klik validasi satu baris.');
    }

    public function test_field_supervisor_login_can_bulk_validate_daily_logs(): void
    {
        [$enrollment, $checkOut, $checkIn] = $this->enrollmentWithDailyLog();
        $fieldSupervisor = User::factory()->create([
            'role' => 'pembimbing_lapangan',
            'email' => 'pl@example.test',
            'name' => 'Pembimbing Login',
        ]);
        $secondCheckIn = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'note' => 'Rencana empat Juni',
            'checked_at' => '2026-06-04 08:00:00',
        ]);
        $secondCheckOut = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'note' => 'Realisasi empat Juni',
            'checked_at' => '2026-06-04 16:00:00',
            'pair_id' => $secondCheckIn->id,
            'duration_minutes' => 480,
        ]);

        $this->actingAs($fieldSupervisor)
            ->get(route('field-supervisor.enrollments.show', $enrollment))
            ->assertOk()
            ->assertSee('Validasi Terpilih');

        $this->actingAs($fieldSupervisor)
            ->post(route('field-supervisor.daily-logs.bulk-validate', $enrollment), [
                'check_in_ids' => [$checkOut->id, $secondCheckOut->id],
                'note' => 'Validasi massal sesuai.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', '2 catatan harian berhasil divalidasi.');

        foreach ([$checkOut->id, $checkIn->id, $secondCheckOut->id, $secondCheckIn->id] as $checkInId) {
            $this->assertDatabaseHas('check_ins', [
                'id' => $checkInId,
                'daily_log_validated_by_name' => 'Pembimbing Login',
                'daily_log_validated_by_email' => 'pl@example.test',
                'daily_log_validation_mode' => 'login',
                'daily_log_validation_note' => 'Validasi massal sesuai.',
            ]);
        }
    }

    public function test_field_supervisor_assessment_requires_finished_attendance_period_and_unblocks_completion(): void
    {
        [$enrollment, $checkOut] = $this->enrollmentWithDailyLog();
        $fieldSupervisor = User::factory()->create([
            'role' => 'pembimbing_lapangan',
            'email' => 'pl@example.test',
            'name' => 'Pembimbing Nilai',
        ]);
        $assessmentTimezone = config('monpkl.timezone') ?: 'Asia/Jakarta';

        $enrollment->update(['attendance_ends_at' => Carbon::today($assessmentTimezone)->addDay()->toDateString()]);

        $this->actingAs($fieldSupervisor)
            ->post(route('field-supervisor.assessment.store', $enrollment), $this->assessmentPayload())
            ->assertSessionHasErrors('assessment');

        $enrollment->update(['attendance_ends_at' => Carbon::today($assessmentTimezone)->toDateString()]);
        $studentUser = $enrollment->student->user;

        Storage::fake('public');

        $this->actingAs($studentUser)
            ->post(route('student.reports.progress.store', $enrollment), [
                'deadline_type' => 'hardcopy',
                'file' => UploadedFile::fake()->create('hardcopy.pdf', 128, 'application/pdf'),
            ])
            ->assertSessionHasErrors('deadline_type');

        $this->actingAs($fieldSupervisor)
            ->post(route('field-supervisor.daily-logs.validate', $checkOut))
            ->assertRedirect();

        $this->actingAs($fieldSupervisor)
            ->post(route('field-supervisor.assessment.store', $enrollment), $this->assessmentPayload([
                'student_general_note' => 'Mahasiswa aktif dan komunikatif.',
                'student_recommendation' => 'Terus tingkatkan dokumentasi pekerjaan.',
                'institution_note' => 'Pembekalan teknis perlu ditambah.',
                'note' => 'Catatan internal.',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('field_supervisor_assessments', [
            'internship_enrollment_id' => $enrollment->id,
            'final_score' => 91.67,
            'assessed_by_email' => 'pl@example.test',
            'assessment_mode' => 'login',
            'student_general_note' => 'Mahasiswa aktif dan komunikatif.',
            'student_recommendation' => 'Terus tingkatkan dokumentasi pekerjaan.',
            'institution_note' => 'Pembekalan teknis perlu ditambah.',
            'note' => 'Catatan internal.',
        ]);

        $assessment = $enrollment->fresh()->fieldSupervisorAssessment;
        $this->assertSame('Sangat baik', data_get($assessment->institution_feedback, 'student_preparation.answer'));
        $this->assertSame('Bersedia', data_get($assessment->institution_feedback, 'future_acceptance.answer'));

        $this->actingAs($studentUser)
            ->get(route('student.reports.show', ['enrollment' => $enrollment, 'tab' => 'seminar']))
            ->assertOk()
            ->assertSee('Nilai Program Pembimbing Lapangan')
            ->assertSee('91,67')
            ->assertSee('Mahasiswa aktif dan komunikatif.')
            ->assertSee('Terus tingkatkan dokumentasi pekerjaan.')
            ->assertDontSee('Pembekalan teknis perlu ditambah.');

        $this->actingAs($studentUser)
            ->post(route('student.reports.progress.store', $enrollment), [
                'deadline_type' => 'hardcopy',
                'file' => UploadedFile::fake()->create('hardcopy-ready.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submission_progress', [
            'internship_enrollment_id' => $enrollment->id,
            'deadline_type' => 'hardcopy',
            'status' => 'pending',
        ]);
    }

    public function test_field_supervisor_assessment_uses_period_end_when_enrollment_override_is_empty(): void
    {
        [$enrollment] = $this->enrollmentWithDailyLog();
        $fieldSupervisor = User::factory()->create([
            'role' => 'pembimbing_lapangan',
            'email' => 'pl@example.test',
            'name' => 'Pembimbing Nilai',
        ]);

        $enrollment->internshipPeriod->update(['ends_at' => now()->toDateString()]);
        $enrollment->update(['attendance_ends_at' => null]);

        $this->actingAs($fieldSupervisor)
            ->get(route('field-supervisor.enrollments.show', ['enrollment' => $enrollment, 'tab' => 'assessment']))
            ->assertOk()
            ->assertSee('Belum dinilai')
            ->assertDontSee('Belum dibuka');

        $this->actingAs($fieldSupervisor)
            ->post(route('field-supervisor.assessment.store', $enrollment), $this->assessmentPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('field_supervisor_assessments', [
            'internship_enrollment_id' => $enrollment->id,
            'final_score' => 91.67,
            'assessed_by_email' => 'pl@example.test',
        ]);
    }

    public function test_field_supervisor_assessment_opens_on_local_calendar_date_even_before_utc_midnight(): void
    {
        $this->travelTo(Carbon::parse('2026-06-05 18:00:00', 'UTC'));

        [$enrollment] = $this->enrollmentWithDailyLog();
        $fieldSupervisor = User::factory()->create([
            'role' => 'pembimbing_lapangan',
            'email' => 'pl@example.test',
            'name' => 'Pembimbing Nilai',
        ]);

        $enrollment->internshipPeriod->update(['ends_at' => '2026-06-06']);
        $enrollment->update(['attendance_ends_at' => null]);

        $this->actingAs($fieldSupervisor)
            ->get(route('field-supervisor.enrollments.show', ['enrollment' => $enrollment, 'tab' => 'assessment']))
            ->assertOk()
            ->assertSee('Belum dinilai')
            ->assertDontSee('Belum dibuka');

        $this->actingAs($fieldSupervisor)
            ->post(route('field-supervisor.assessment.store', $enrollment), $this->assessmentPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('field_supervisor_assessments', [
            'internship_enrollment_id' => $enrollment->id,
            'final_score' => 91.67,
            'assessed_by_email' => 'pl@example.test',
        ]);

        $this->travelBack();
    }

    public function test_field_supervisor_attendance_score_ignores_weekends_and_holidays(): void
    {
        $this->travelTo(Carbon::parse('2026-06-10 08:00:00', 'Asia/Jakarta'));

        [$enrollment] = $this->enrollmentWithDailyLog();
        $fieldSupervisor = User::factory()->create([
            'role' => 'pembimbing_lapangan',
            'email' => 'pl@example.test',
            'name' => 'Pembimbing Nilai',
        ]);

        $enrollment->internshipPeriod->update([
            'starts_at' => '2026-06-05',
            'ends_at' => '2026-06-09',
        ]);
        $enrollment->internshipPeriod->setting()->create([
            'settings' => [
                'calendar' => [
                    'holidays' => ['2026-06-08'],
                ],
            ],
        ]);

        foreach ([
            '2026-06-06 08:00:00',
            '2026-06-07 08:00:00',
            '2026-06-08 08:00:00',
        ] as $checkedAt) {
            CheckIn::query()->create([
                'internship_enrollment_id' => $enrollment->id,
                'type' => 'Masuk',
                'action' => 'check_in',
                'note' => 'Catatan di luar hari kerja efektif',
                'checked_at' => $checkedAt,
            ]);
        }

        $this->actingAs($fieldSupervisor)
            ->get(route('field-supervisor.enrollments.show', ['enrollment' => $enrollment, 'tab' => 'assessment']))
            ->assertOk()
            ->assertSeeText('1/2 hari kerja');

        $this->actingAs($fieldSupervisor)
            ->post(route('field-supervisor.assessment.store', $enrollment), $this->assessmentPayload())
            ->assertRedirect();

        $assessment = $enrollment->fresh()->fieldSupervisorAssessment;

        $this->assertSame(50.0, (float) data_get($assessment->scores, 'attendance.score'));
        $this->assertSame(83.33, (float) $assessment->final_score);

        $this->travelBack();
    }

    private function enrollmentWithDailyLog(): array
    {
        $studyProgram = StudyProgram::query()->create(['code' => 'ILKOM', 'name' => 'Ilmu Komputer', 'is_active' => true]);
        $studentUser = User::factory()->create(['role' => 'mahasiswa']);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'study_program_id' => $studyProgram->id,
            'npm' => '2217051001',
            'full_name' => 'Mahasiswa Catatan',
        ]);
        $period = InternshipPeriod::query()->create([
            'name' => 'Periode Aktif',
            'starts_at' => '2026-06-05',
            'ends_at' => '2026-06-05',
            'is_active' => true,
        ]);
        $place = InternshipPlace::query()->create([
            'name' => 'Mitra Validasi',
            'latitude' => -5.3971,
            'longitude' => 105.2668,
        ]);
        $enrollment = InternshipEnrollment::query()->create([
            'student_id' => $student->id,
            'study_program_id' => $studyProgram->id,
            'internship_period_id' => $period->id,
            'internship_place_id' => $place->id,
            'field_supervisor' => 'Pembimbing Lapangan',
            'field_supervisor_email' => 'pl@example.test',
            'status' => 'active',
        ]);
        $checkIn = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Masuk',
            'action' => 'check_in',
            'note' => 'Rencana hari ini',
            'checked_at' => '2026-06-05 08:00:00',
        ]);
        $checkOut = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => 'Pulang',
            'action' => 'check_out',
            'note' => 'Realisasi hari ini',
            'checked_at' => '2026-06-05 16:00:00',
            'pair_id' => $checkIn->id,
            'duration_minutes' => 480,
        ]);

        $enrollment->load('student.user');

        return [$enrollment, $checkOut, $checkIn];
    }

    private function assessmentPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'scores' => [
                'attendance' => 90,
                'rules_compliance' => 90,
                'group_teamwork' => 90,
                'other_teamwork' => 90,
                'supervisor_teamwork' => 90,
                'innovation' => 90,
                'task_ability' => 90,
                'seriousness' => 90,
            ],
            'institution_feedback' => [
                'student_preparation' => 'very_good',
                'competency_fit' => 'suitable',
                'campus_communication' => 'good',
                'supervision_support' => 'helpful',
                'future_acceptance' => 'yes',
            ],
        ], $overrides);
    }
}
