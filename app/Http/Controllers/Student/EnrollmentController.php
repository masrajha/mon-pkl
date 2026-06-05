<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Program;
use App\Services\EnrollmentEmailNotificationService;
use App\Services\PeriodConfigurationService;
use App\Services\StudentWorkflowAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly PeriodConfigurationService $configurations,
        private readonly EnrollmentEmailNotificationService $enrollmentEmails,
        private readonly StudentWorkflowAccessService $workflowAccess,
    ) {
    }

    public function create(Request $request): View
    {
        $student = $this->studentOrRedirect($request);

        return view('student.enrollments.create', $this->formData($student) + [
            'enrollment' => null,
        ]);
    }

    public function edit(Request $request, InternshipEnrollment $enrollment): View
    {
        $student = $this->studentOrRedirect($request);

        abort_unless((int) $enrollment->student_id === (int) $student->id, 403);
        abort_unless($enrollment->status === 'revision_required', 403);

        $enrollment->loadMissing(['internshipPeriod.program', 'internshipPeriod.deadlines', 'internshipPlace']);

        return view('student.enrollments.create', $this->formData($student, $enrollment) + [
            'enrollment' => $enrollment,
        ]);
    }

    private function formData($student, ?InternshipEnrollment $enrollment = null): array
    {
        $periods = InternshipPeriod::query()
            ->with(['program', 'deadlines'])
            ->where('is_locked', false)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (InternshipPeriod $period) => $this->workflowAccess->registrationOpen($period))
            ->values();

        if ($enrollment && ! $periods->contains('id', $enrollment->internship_period_id)) {
            $periods->push($enrollment->internshipPeriod);
        }

        return [
            'student' => $student,
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'periods' => $periods->filter()->values(),
            'places' => InternshipPlace::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $this->studentOrRedirect($request);

        $data = $this->validated($request);
        $period = $this->validatedPeriod($data);
        $this->ensureRegistrationOpen($period);

        $data['program_id'] = $period->program_id;
        $data['study_program_id'] = $student->study_program_id;
        $settings = $this->configurations->forPeriod($period);
        $this->validateEligibilityByRule($data, $settings, $period->program?->rule_key, $student->studyProgram?->degree_level);

        $existingEnrollment = InternshipEnrollment::query()
            ->where('student_id', $student->id)
            ->where('study_program_id', $data['study_program_id'])
            ->where('internship_period_id', $data['internship_period_id'])
            ->first();

        if ($existingEnrollment && ! in_array($existingEnrollment->status, ['cancelled', 'rejected', 'revision_required'], true)) {
            throw ValidationException::withMessages([
                'internship_period_id' => 'Anda sudah memiliki pendaftaran pada periode ini. Jika ingin mengganti mitra, gunakan menu Pindah Mitra.',
            ]);
        }

        $this->validateQuota($data, $settings, $existingEnrollment);

        $enrollment = $this->saveEnrollment($request, $data, $student->id, $existingEnrollment);
        $this->enrollmentEmails->studentSubmitted($enrollment, revision: (bool) $existingEnrollment);

        return redirect()->route('student.dashboard')->with('status', 'Pendaftaran program dikirim dan menunggu verifikasi admin.');
    }

    public function update(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $student = $this->studentOrRedirect($request);

        abort_unless((int) $enrollment->student_id === (int) $student->id, 403);
        abort_unless($enrollment->status === 'revision_required', 403);

        $data = $this->validated($request, $enrollment);
        $period = $this->validatedPeriod($data);

        if ((int) $enrollment->internship_period_id !== (int) $period->id) {
            throw ValidationException::withMessages([
                'internship_period_id' => 'Periode tidak dapat diubah pada pengiriman revisi. Buat pendaftaran baru jika perlu mengganti periode.',
            ]);
        }

        $data['program_id'] = $period->program_id;
        $data['study_program_id'] = $student->study_program_id;
        $settings = $this->configurations->forPeriod($period);
        $this->validateEligibilityByRule($data, $settings, $period->program?->rule_key, $student->studyProgram?->degree_level);
        $this->validateQuota($data, $settings, $enrollment);

        $enrollment = $this->saveEnrollment($request, $data, $student->id, $enrollment);
        $this->enrollmentEmails->studentSubmitted($enrollment, revision: true);

        return redirect()->route('student.dashboard')->with('status', 'Revisi pendaftaran dikirim kembali dan menunggu verifikasi admin.');
    }

    private function validated(Request $request, ?InternshipEnrollment $enrollment = null): array
    {
        $data = $request->validate([
            'internship_period_id' => ['required', 'exists:internship_periods,id'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'internship_place_id' => ['nullable', Rule::exists('internship_places', 'id')->where('is_active', true)],
            'contact_student_phone' => ['required', 'string', 'max:50'],
            'field_supervisor' => ['nullable', 'string', 'max:255'],
            'field_supervisor_phone' => ['nullable', 'string', 'max:50'],
            'field_supervisor_email' => ['nullable', 'email', 'max:255'],
            'has_krs_pkl' => ['accepted'],
            'total_sks' => ['required', 'integer', 'min:0', 'max:250'],
            'current_semester' => ['required', 'integer', 'min:1', 'max:20'],
            'gpa' => ['required', 'numeric', 'min:0', 'max:4'],
            'registration_document' => [
                $enrollment?->registration_document_path ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
                'max:5120',
            ],
        ]);

        return $data;
    }

    private function validatedPeriod(array $data): InternshipPeriod
    {
        $period = InternshipPeriod::query()->with(['program', 'deadlines'])->findOrFail($data['internship_period_id']);

        if ($period->is_locked) {
            throw ValidationException::withMessages(['internship_period_id' => 'Periode yang dipilih sudah terkunci.']);
        }

        if (! empty($data['program_id']) && $period->program_id && (int) $data['program_id'] !== (int) $period->program_id) {
            throw ValidationException::withMessages(['program_id' => 'Program kegiatan tidak sesuai dengan periode yang dipilih.']);
        }

        return $period;
    }

    private function ensureRegistrationOpen(InternshipPeriod $period): void
    {
        if (! $this->workflowAccess->registrationOpen($period)) {
            throw ValidationException::withMessages([
                'internship_period_id' => 'Pendaftaran untuk periode ini belum dibuka atau sudah ditutup.',
            ]);
        }
    }

    private function saveEnrollment(Request $request, array $data, int $studentId, ?InternshipEnrollment $enrollment = null): InternshipEnrollment
    {
        unset($data['program_id']);
        unset($data['registration_document']);

        $payload = $data + [
            'student_id' => $studentId,
            'has_krs_pkl' => true,
            'status' => 'pending_verification',
            'admin_note' => null,
        ];

        if ($request->hasFile('registration_document')) {
            $payload['registration_document_path'] = $request->file('registration_document')
                ->store('registration-documents', 'public');

            if ($enrollment?->registration_document_path) {
                Storage::disk('public')->delete($enrollment->registration_document_path);
            }
        }

        if ($enrollment) {
            $enrollment->update($payload);

            return $enrollment->refresh()->loadMissing(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace']);
        } else {
            return InternshipEnrollment::query()
                ->create($payload)
                ->loadMissing(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace']);
        }
    }

    private function studentOrRedirect(Request $request)
    {
        $student = $request->user()->student()->first();

        abort_if(! $student || ! $student->student_email || ! $student->phone || ! $student->study_program_id, 403, 'Lengkapi profil mahasiswa sebelum mendaftar program.');

        return $student->loadMissing('studyProgram');
    }

    private function validateEligibilityByRule(array $data, array $settings, ?string $ruleKey, ?string $degreeLevel): void
    {
        match ($ruleKey ?: 'kerja_praktik') {
            'kerja_praktik' => $this->validateAcademicEligibility($data, $settings, $degreeLevel),
            default => throw ValidationException::withMessages([
                'program_id' => 'Rule program belum tersedia. Hubungi admin untuk mengaktifkan aturan program ini.',
            ]),
        };
    }

    private function validateAcademicEligibility(array $data, array $settings, ?string $degreeLevel): void
    {
        $degreeKey = strtolower((string) ($degreeLevel ?: 'S1'));
        $minimumSemester = (int) ($settings['enrollment']["minimum_semester_{$degreeKey}"] ?? $settings['enrollment']['minimum_semester_s1']);
        $minimumSks = (int) ($settings['enrollment']["minimum_total_sks_{$degreeKey}"] ?? $settings['enrollment']['minimum_total_sks'] ?? $settings['enrollment']['minimum_total_sks_s1']);
        $minimumGpa = (float) $settings['enrollment']['minimum_gpa'];

        if ((int) $data['total_sks'] < $minimumSks) {
            throw ValidationException::withMessages(['total_sks' => "Total SKS minimal {$minimumSks}."]);
        }

        if ((int) $data['current_semester'] < $minimumSemester) {
            throw ValidationException::withMessages(['current_semester' => "Semester minimal {$minimumSemester}."]);
        }

        if ((float) $data['gpa'] < $minimumGpa) {
            throw ValidationException::withMessages(['gpa' => 'IPK belum memenuhi syarat minimal '.number_format($minimumGpa, 2, ',', '.').'.']);
        }
    }

    private function validateQuota(array $data, array $settings, ?InternshipEnrollment $enrollment = null): void
    {
        if (empty($data['internship_place_id'])) {
            return;
        }

        $used = InternshipEnrollment::query()
            ->where('internship_period_id', $data['internship_period_id'])
            ->where('study_program_id', $data['study_program_id'])
            ->where('internship_place_id', $data['internship_place_id'])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->when($enrollment, fn ($query) => $query->whereKeyNot($enrollment->id))
            ->count();

        if ($used >= (int) $settings['enrollment']['max_place_quota']) {
            throw ValidationException::withMessages([
                'internship_place_id' => 'Kuota maksimal mitra ini sudah terpenuhi untuk periode dan prodi yang dipilih.',
            ]);
        }
    }
}
