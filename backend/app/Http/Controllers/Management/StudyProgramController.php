<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudyProgramController extends Controller
{
    public function index(): View
    {
        return view('management.study-programs.index', [
            'studyPrograms' => StudyProgram::query()->orderBy('name')->paginate(20),
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
