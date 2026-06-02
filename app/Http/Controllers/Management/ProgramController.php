<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = Program::query()->withCount('periods');

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$search.'%')
                ->orWhere('name', 'like', '%'.$search.'%')
                ->orWhere('rule_key', 'like', '%'.$search.'%'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        return view('management.programs.index', [
            'programs' => $this->applyTableSort($query, $request, ['code', 'name', 'rule_key', 'is_active'], 'name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Program::query()->create($this->validated($request));

        return back()->with('status', 'Program kegiatan berhasil ditambahkan.');
    }

    public function edit(Program $program): View
    {
        return view('management.programs.edit', compact('program'));
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $program->update($this->validated($request, $program));

        return redirect()->route('management.programs.index')->with('status', 'Program kegiatan berhasil diperbarui.');
    }

    private function validated(Request $request, ?Program $program = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('programs')->ignore($program)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'rule_key' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }
}
