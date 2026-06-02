<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LecturerController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = Lecturer::query()->with(['user', 'studyProgram']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('nip', 'like', '%'.$search.'%')
                ->orWhere('nidn', 'like', '%'.$search.'%'));
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('management.lecturers.index', $this->formData() + [
            'lecturers' => $this->applyTableSort($query, $request, ['name', 'nip', 'nidn', 'status'], 'name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $lecturer = Lecturer::query()->create($this->validated($request));
        $this->syncUserRole($lecturer);

        return back()->with('status', 'Dosen berhasil ditambahkan.');
    }

    public function edit(Lecturer $lecturer): View
    {
        return view('management.lecturers.edit', $this->formData() + compact('lecturer'));
    }

    public function update(Request $request, Lecturer $lecturer): RedirectResponse
    {
        $lecturer->update($this->validated($request, $lecturer));
        $this->syncUserRole($lecturer);

        return redirect()->route('management.lecturers.index')->with('status', 'Dosen berhasil diperbarui.');
    }

    private function validated(Request $request, ?Lecturer $lecturer = null): array
    {
        return $request->validate([
            'user_id' => ['nullable', Rule::exists('users', 'id'), Rule::unique('lecturers')->ignore($lecturer)],
            'study_program_id' => ['nullable', 'exists:study_programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('lecturers')->ignore($lecturer)],
            'nidn' => ['nullable', 'string', 'max:50', Rule::unique('lecturers')->ignore($lecturer)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function formData(): array
    {
        return [
            'users' => User::query()->where('role', 'dosen')->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function syncUserRole(Lecturer $lecturer): void
    {
        if ($lecturer->user_id) {
            $lecturer->user()->update(['role' => 'dosen']);
        }
    }
}
