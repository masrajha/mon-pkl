<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        return view('management.students.index', [
            'students' => Student::query()->with(['user', 'studyProgram'])->orderBy('full_name')->paginate(20),
            'users' => User::query()->where('role', 'mahasiswa')->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Student::query()->create($this->validated($request));

        return back()->with('status', 'Mahasiswa berhasil ditambahkan.');
    }

    public function edit(Student $student): View
    {
        return view('management.students.edit', [
            'student' => $student,
            'users' => User::query()->where('role', 'mahasiswa')->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $student->update($this->validated($request, $student));

        return redirect()->route('management.students.index')->with('status', 'Mahasiswa berhasil diperbarui.');
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('students')->ignore($student)],
            'study_program_id' => ['nullable', 'exists:study_programs,id'],
            'npm' => ['required', 'string', 'max:30', Rule::unique('students')->ignore($student)],
            'full_name' => ['required', 'string', 'max:255'],
        ]);
    }
}
