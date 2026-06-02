<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudyProgramController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = StudyProgram::query();

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$search.'%')
                ->orWhere('name', 'like', '%'.$search.'%')
                ->orWhere('faculty', 'like', '%'.$search.'%'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        return view('management.study-programs.index', [
            'studyPrograms' => $this->applyTableSort($query, $request, ['code', 'name', 'faculty', 'is_active'], 'name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        StudyProgram::query()->create($this->validated($request));

        return back()->with('status', 'Prodi berhasil ditambahkan.');
    }

    public function edit(StudyProgram $studyProgram): View
    {
        return view('management.study-programs.edit', compact('studyProgram'));
    }

    public function update(Request $request, StudyProgram $studyProgram): RedirectResponse
    {
        $studyProgram->update($this->validated($request, $studyProgram));

        return redirect()->route('management.study-programs.index')->with('status', 'Prodi berhasil diperbarui.');
    }

    private function validated(Request $request, ?StudyProgram $studyProgram = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('study_programs')->ignore($studyProgram)],
            'name' => ['required', 'string', 'max:255', Rule::unique('study_programs')->ignore($studyProgram)],
            'faculty' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }
}
