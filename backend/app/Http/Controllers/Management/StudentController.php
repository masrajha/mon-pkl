<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = Student::query()->with(['user', 'studyProgram']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($query) use ($search): void {
                $query->where('npm', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($query) => $query->where('email', 'like', '%'.$search.'%'));
            });
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        return view('management.students.index', [
            'students' => $this->applyTableSort($query, $request, ['npm', 'full_name'], 'full_name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'users' => User::query()->where('role', 'mahasiswa')->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
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
