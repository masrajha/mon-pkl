<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\Organization;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudyProgramController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = StudyProgram::query()->with('organization.parent');

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$search.'%')
                ->orWhere('name', 'like', '%'.$search.'%')
                ->orWhere('degree_level', 'like', '%'.$search.'%')
                ->orWhereHas('organization', fn ($organization) => $organization
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        return view('management.study-programs.index', [
            'studyPrograms' => $this->applyTableSort($query, $request, ['code', 'name', 'degree_level', 'is_active'], 'name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'departments' => $this->departments(),
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
        return view('management.study-programs.edit', [
            'studyProgram' => $studyProgram,
            'departments' => $this->departments(),
        ]);
    }

    public function update(Request $request, StudyProgram $studyProgram): RedirectResponse
    {
        $studyProgram->update($this->validated($request, $studyProgram));

        return redirect()->route('management.study-programs.index')->with('status', 'Prodi berhasil diperbarui.');
    }

    public function destroy(StudyProgram $studyProgram): RedirectResponse
    {
        $this->ensureCanDelete($studyProgram);
        $studyProgram->delete();

        return back()->with('status', 'Prodi berhasil dihapus.');
    }

    private function validated(Request $request, ?StudyProgram $studyProgram = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('study_programs')->ignore($studyProgram)],
            'name' => ['required', 'string', 'max:255', Rule::unique('study_programs')->ignore($studyProgram)],
            'degree_level' => ['required', 'string', 'max:10'],
            'organization_id' => [
                'required',
                Rule::exists('organizations', 'id')->where('type', 'department'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['degree_level' => 'S1', 'is_active' => false];

        $department = Organization::query()->with('parent')->find($data['organization_id']);
        $data['faculty'] = $department?->parent?->name;

        return $data;
    }

    private function departments()
    {
        return Organization::query()
            ->with('parent')
            ->where('type', 'department')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function ensureCanDelete(StudyProgram $studyProgram): void
    {
        $references = [
            'mahasiswa' => DB::table('students')->where('study_program_id', $studyProgram->id)->exists(),
            'dosen' => DB::table('lecturers')->where('study_program_id', $studyProgram->id)->exists(),
            'peserta periode' => DB::table('internship_enrollments')->where('study_program_id', $studyProgram->id)->exists(),
            'koordinator program' => DB::table('internship_coordinators')->where('study_program_id', $studyProgram->id)->exists(),
            'usulan mitra' => DB::table('internship_place_proposals')->where('study_program_id', $studyProgram->id)->exists(),
            'pembekalan' => DB::table('orientation_events')->where('study_program_id', $studyProgram->id)->exists(),
            'viewer laporan' => DB::table('report_viewer_assignments')->where('study_program_id', $studyProgram->id)->exists(),
        ];

        $usedBy = collect($references)->filter()->keys();

        if ($usedBy->isNotEmpty()) {
            throw ValidationException::withMessages([
                'delete' => 'Prodi tidak dapat dihapus karena masih digunakan oleh: '.$usedBy->join(', ').'. Nonaktifkan prodi jika masih dibutuhkan untuk riwayat data.',
            ]);
        }
    }
}
