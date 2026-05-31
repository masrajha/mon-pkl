<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = InternshipEnrollment::query()->with(['student', 'studyProgram', 'internshipPeriod', 'internshipPlace', 'lecturer', 'lecturerSupervisor']);

        if ($request->filled('period_id')) {
            $query->where('internship_period_id', $request->integer('period_id'));
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('management.enrollments.index', $this->formData() + [
            'enrollments' => $query->latest('id')->paginate(20)->withQueryString(),
            'selectedPeriod' => $request->integer('period_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        InternshipEnrollment::query()->create($this->validated($request));

        return back()->with('status', 'Peserta periode berhasil ditambahkan.');
    }

    public function edit(InternshipEnrollment $enrollment): View
    {
        return view('management.enrollments.edit', $this->formData() + compact('enrollment'));
    }

    public function update(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $enrollment->update($this->validated($request, $enrollment));

        return redirect()->route('management.enrollments.index', [
            'period_id' => $enrollment->internship_period_id,
            'study_program_id' => $enrollment->study_program_id,
        ])->with('status', 'Peserta periode berhasil diperbarui.');
    }

    private function validated(Request $request, ?InternshipEnrollment $enrollment = null): array
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'study_program_id' => ['required', 'exists:study_programs,id'],
            'internship_period_id' => ['required', 'exists:internship_periods,id'],
            'internship_place_id' => ['nullable', 'exists:internship_places,id'],
            'lecturer_supervisor_id' => ['nullable', Rule::exists('lecturers', 'id')->where('status', 'active')],
            'field_supervisor' => ['nullable', 'string', 'max:255'],
            'field_supervisor_phone' => ['nullable', 'string', 'max:50'],
            'contact_student_phone' => ['nullable', 'string', 'max:50'],
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
        ]);

        $request->validate([
            'student_id' => [
                Rule::unique('internship_enrollments')
                    ->where('study_program_id', $data['study_program_id'])
                    ->where('internship_period_id', $data['internship_period_id'])
                    ->ignore($enrollment?->id),
            ],
        ]);

        $lecturer = $data['lecturer_supervisor_id']
            ? Lecturer::query()->where('status', 'active')->find($data['lecturer_supervisor_id'])
            : null;

        $data['lecturer_supervisor_user_id'] = $lecturer?->user_id;
        $data['lecturer_supervisor'] = $lecturer?->name;

        return $data;
    }

    private function formData(): array
    {
        return [
            'students' => Student::query()->orderBy('full_name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'periods' => InternshipPeriod::query()->orderByDesc('is_active')->orderByDesc('id')->get(),
            'places' => InternshipPlace::query()->orderBy('name')->get(),
            'lecturers' => Lecturer::query()->where('status', 'active')->orderBy('name')->get(),
        ];
    }
}
