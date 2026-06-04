<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Services\EnrollmentEmailNotificationService;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(
        private readonly PeriodConfigurationService $configurations,
        private readonly EnrollmentEmailNotificationService $enrollmentEmails,
    ) {
    }

    public function index(Request $request): View
    {
        $query = InternshipEnrollment::query()->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'lecturer', 'lecturerSupervisor']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->whereHas('student', fn ($query) => $query->where('full_name', 'like', '%'.$search.'%')->orWhere('npm', 'like', '%'.$search.'%'))
                ->orWhereHas('internshipPlace', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('lecturer', fn ($query) => $query->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('period_id')) {
            $query->where('internship_period_id', $request->integer('period_id'));
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('management.enrollments.index', $this->formData($request) + [
            'enrollments' => $this->applyTableSort($query, $request, ['id', 'status'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedPeriod' => $request->integer('period_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function validations(Request $request): View
    {
        $query = InternshipEnrollment::query()
            ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace', 'lecturer'])
            ->whereIn('status', ['pending_verification', 'revision_required']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->whereHas('student', fn ($query) => $query->where('full_name', 'like', '%'.$search.'%')->orWhere('npm', 'like', '%'.$search.'%'))
                ->orWhereHas('internshipPlace', fn ($query) => $query->where('name', 'like', '%'.$search.'%')));
        }

        $this->scopeValidationQuery($query, $request);

        if ($request->filled('period_id')) {
            $query->where('internship_period_id', $request->integer('period_id'));
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        if ($request->filled('program_id')) {
            $query->whereHas('internshipPeriod', fn ($period) => $period->where('program_id', $request->integer('program_id')));
        }

        return view('management.enrollments.validations', $this->formData() + [
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'enrollments' => $this->applyTableSort($query, $request, ['id', 'status'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'quotaWarnings' => $this->quotaWarnings(),
            'selectedPeriod' => $request->integer('period_id') ?: null,
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $enrollment = InternshipEnrollment::query()->create($this->validated($request));
        $this->enrollmentEmails->enrollmentChangedByAdmin($enrollment->refresh(), ['status']);

        return back()->with('status', 'Peserta periode berhasil ditambahkan.');
    }

    public function edit(Request $request, InternshipEnrollment $enrollment): View
    {
        $enrollment->loadMissing(['student.studyProgram', 'studyProgram', 'internshipPeriod.program']);

        return view('management.enrollments.edit', $this->formData($request) + compact('enrollment'));
    }

    public function update(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $enrollment->update($this->validated($request, $enrollment));
        $changed = collect($enrollment->getChanges())
            ->keys()
            ->reject(fn (string $field) => in_array($field, ['updated_at'], true))
            ->values()
            ->all();

        if ($changed !== []) {
            $this->enrollmentEmails->enrollmentChangedByAdmin($enrollment->refresh(), $changed);
        }

        return redirect()->route('management.enrollments.index', [
            'period_id' => $enrollment->internship_period_id,
            'study_program_id' => $enrollment->study_program_id,
        ])->with('status', 'Peserta periode berhasil diperbarui.');
    }

    private function validated(Request $request, ?InternshipEnrollment $enrollment = null): array
    {
        $studentId = $enrollment?->student_id ?: $request->integer('student_id');
        $student = Student::query()->with('studyProgram')->find($studentId);

        if (! $student) {
            throw ValidationException::withMessages(['student_id' => 'Mahasiswa wajib dipilih.']);
        }

        if (! $student->study_program_id) {
            throw ValidationException::withMessages(['student_id' => 'Prodi mahasiswa belum diisi. Lengkapi data mahasiswa terlebih dahulu.']);
        }

        $data = $request->validate([
            'internship_period_id' => ['required', 'exists:internship_periods,id'],
            'internship_place_id' => ['nullable', 'exists:internship_places,id'],
            'lecturer_supervisor_id' => ['nullable', Rule::exists('lecturers', 'id')->where('status', 'active')],
            'field_supervisor' => ['nullable', 'string', 'max:255'],
            'field_supervisor_phone' => ['nullable', 'string', 'max:50'],
            'field_supervisor_email' => ['nullable', 'email', 'max:255'],
            'contact_student_phone' => ['nullable', 'string', 'max:50'],
            'has_krs_pkl' => ['nullable', 'boolean'],
            'total_sks' => ['nullable', 'integer', 'min:0', 'max:250'],
            'current_semester' => ['nullable', 'integer', 'min:1', 'max:20'],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'status' => ['required', Rule::in([
                'draft',
                'pending_verification',
                'revision_required',
                'active',
                'inactive',
                'completed',
                'cancelled',
                'rejected',
            ])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['student_id'] = $student->id;
        $data['study_program_id'] = $student->study_program_id;

        $duplicate = InternshipEnrollment::query()
            ->where('student_id', $data['student_id'])
            ->where('study_program_id', $data['study_program_id'])
            ->where('internship_period_id', $data['internship_period_id'])
            ->when($enrollment, fn ($query) => $query->whereKeyNot($enrollment->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'student_id' => 'Mahasiswa sudah terdaftar pada periode dan prodi ini.',
            ]);
        }

        $lecturerId = $data['lecturer_supervisor_id'] ?? null;
        $lecturer = $lecturerId
            ? Lecturer::query()->where('status', 'active')->find($lecturerId)
            : null;

        $data['lecturer_supervisor_id'] = $lecturer?->id;
        $data['lecturer_supervisor_user_id'] = $lecturer?->user_id;
        $data['lecturer_supervisor'] = $lecturer?->name;
        $data['has_krs_pkl'] = (bool) ($data['has_krs_pkl'] ?? false);
        $this->validateQuota($data, $enrollment);

        return $data;
    }

    private function validateQuota(array $data, ?InternshipEnrollment $enrollment = null): void
    {
        if (empty($data['internship_place_id'])) {
            return;
        }

        $settings = $this->configurations->forPeriod((int) $data['internship_period_id']);
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

    public function studentSearch(Request $request): JsonResponse
    {
        $search = trim($request->string('q')->toString());

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $students = Student::query()
            ->with('studyProgram:id,code,name')
            ->where(function ($query) use ($search): void {
                $query->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('npm', 'like', '%'.$search.'%');
            })
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'study_program_id', 'npm', 'full_name']);

        return response()->json($students->map(fn (Student $student) => [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'npm' => $student->npm,
            'study_program_id' => $student->study_program_id,
            'study_program_name' => $student->studyProgram?->name,
            'study_program_code' => $student->studyProgram?->code,
            'label' => trim($student->full_name.' - '.$student->npm),
        ]));
    }

    private function formData(?Request $request = null): array
    {
        $selectedStudentId = old('student_id', $request?->integer('student_id') ?: null);

        return [
            'selectedStudent' => $selectedStudentId
                ? Student::query()->with('studyProgram:id,code,name')->find($selectedStudentId)
                : null,
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'periods' => InternshipPeriod::query()->with('program')->orderByDesc('is_active')->orderByDesc('id')->get(),
            'places' => InternshipPlace::query()->where('is_active', true)->orderBy('name')->get(),
            'lecturers' => Lecturer::query()->where('status', 'active')->orderBy('name')->get(),
        ];
    }

    public function validateEnrollment(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeValidationScope($enrollment, $request);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'revision_required', 'rejected'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->processValidation($enrollment, $data['status'], $data['admin_note'] ?? null);

        return back()->with('status', 'Validasi pendaftaran berhasil diproses.');
    }

    public function bulkValidateEnrollments(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['active', 'revision_required', 'rejected'])],
            'enrollment_ids' => ['nullable', 'array'],
            'enrollment_ids.*' => ['integer', 'exists:internship_enrollments,id'],
            'single_enrollment_id' => ['nullable', 'integer', 'exists:internship_enrollments,id'],
            'admin_notes' => ['nullable', 'array'],
            'admin_notes.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $enrollmentIds = filled($data['single_enrollment_id'] ?? null)
            ? collect([(int) $data['single_enrollment_id']])
            : collect($data['enrollment_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($enrollmentIds->isEmpty()) {
            throw ValidationException::withMessages([
                'enrollment_ids' => 'Pilih minimal satu pendaftaran untuk diproses.',
            ]);
        }

        $enrollments = InternshipEnrollment::query()
            ->whereIn('id', $enrollmentIds)
            ->whereIn('status', ['pending_verification', 'revision_required'])
            ->get();

        $processed = 0;

        foreach ($enrollments as $enrollment) {
            $this->authorizeValidationScope($enrollment, $request);
            $this->processValidation(
                $enrollment,
                $data['action'],
                $data['admin_notes'][$enrollment->id] ?? null,
            );
            $processed++;
        }

        return back()->with('status', $processed.' pendaftaran berhasil diproses.');
    }

    public function registrationDocument(Request $request, InternshipEnrollment $enrollment)
    {
        $this->authorizeValidationScope($enrollment, $request);

        abort_unless($enrollment->registration_document_path, 404);
        abort_unless(Storage::disk('public')->exists($enrollment->registration_document_path), 404);

        return Storage::disk('public')->response($enrollment->registration_document_path);
    }

    private function quotaWarnings(): array
    {
        return InternshipEnrollment::query()
            ->selectRaw('internship_period_id, study_program_id, internship_place_id, COUNT(*) as total')
            ->whereNotNull('internship_place_id')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->groupBy('internship_period_id', 'study_program_id', 'internship_place_id')
            ->get()
            ->filter(function ($row): bool {
                $settings = $this->configurations->forPeriod((int) $row->internship_period_id);

                return $row->total < (int) $settings['enrollment']['min_place_quota'];
            })
            ->mapWithKeys(fn ($row) => [
                $row->internship_period_id.'-'.$row->study_program_id.'-'.$row->internship_place_id => $row->total,
            ])
            ->all();
    }

    private function scopeValidationQuery($query, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $assignments = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();

        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($query) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $query->orWhere(function ($query) use ($assignment): void {
                    $query->where('internship_period_id', $assignment->internship_period_id)
                        ->where('study_program_id', $assignment->study_program_id);
                });
            }
        });
    }

    private function authorizeValidationScope(InternshipEnrollment $enrollment, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $allowed = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $enrollment->internship_period_id)
            ->where('study_program_id', $enrollment->study_program_id)
            ->exists();

        abort_unless($allowed, 403);
    }

    private function processValidation(InternshipEnrollment $enrollment, string $status, ?string $adminNote): void
    {
        $enrollment->update([
            'status' => $status,
            'admin_note' => $adminNote,
        ]);

        $this->enrollmentEmails->validationProcessed($enrollment->refresh(), $status);
    }
}
