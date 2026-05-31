<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function create(Request $request): View
    {
        $student = $this->studentOrRedirect($request);

        return view('student.enrollments.create', [
            'student' => $student,
            'periods' => InternshipPeriod::query()->where('is_locked', false)->orderByDesc('is_active')->orderByDesc('id')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'places' => InternshipPlace::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $this->studentOrRedirect($request);

        $data = $request->validate([
            'internship_period_id' => ['required', 'exists:internship_periods,id'],
            'study_program_id' => ['required', 'exists:study_programs,id'],
            'internship_place_id' => ['nullable', 'exists:internship_places,id'],
            'contact_student_phone' => ['required', 'string', 'max:50'],
            'field_supervisor' => ['nullable', 'string', 'max:255'],
            'field_supervisor_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $request->validate([
            'internship_period_id' => [
                Rule::unique('internship_enrollments')
                    ->where('student_id', $student->id)
                    ->where('study_program_id', $data['study_program_id']),
            ],
        ]);

        InternshipEnrollment::query()->create($data + [
            'student_id' => $student->id,
            'status' => 'pending_verification',
        ]);

        return redirect()->route('student.dashboard')->with('status', 'Pendaftaran PKL dikirim dan menunggu verifikasi admin.');
    }

    private function studentOrRedirect(Request $request)
    {
        $student = $request->user()->student()->first();

        abort_if(! $student || ! $student->student_email || ! $student->phone, 403, 'Lengkapi profil mahasiswa sebelum mendaftar PKL.');

        return $student;
    }
}
