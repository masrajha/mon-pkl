<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('student.profile', [
            'student' => $request->user()->student()->first(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $student = $request->user()->student()->first();

        $data = $request->validate([
            'npm' => ['required', 'string', 'max:50', Rule::unique('students')->ignore($student)],
            'full_name' => ['required', 'string', 'max:255'],
            'student_email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'study_program_id' => ['required', 'exists:study_programs,id'],
        ]);

        Student::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data + ['user_id' => $request->user()->id],
        );

        return redirect()->route('student.dashboard')->with('status', 'Profil mahasiswa berhasil disimpan.');
    }
}
