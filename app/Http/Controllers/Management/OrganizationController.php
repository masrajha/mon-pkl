<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = Organization::query()->with('parent');

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$search.'%')
                ->orWhere('name', 'like', '%'.$search.'%')
                ->orWhereHas('parent', fn ($parent) => $parent->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        return view('management.organizations.index', [
            'organizations' => $this->applyTableSort($query, $request, ['code', 'name', 'type', 'is_active'], 'name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'parents' => $this->parentOptions(),
            'types' => $this->types(),
            'selectedType' => $request->string('type')->toString(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Organization::query()->create($this->validated($request));

        return back()->with('status', 'Organisasi berhasil ditambahkan.');
    }

    public function edit(Organization $organization): View
    {
        return view('management.organizations.edit', [
            'organization' => $organization,
            'parents' => $this->parentOptions($organization),
            'types' => $this->types(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $organization->update($this->validated($request, $organization));

        return redirect()->route('management.organizations.index')->with('status', 'Organisasi berhasil diperbarui.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->ensureCanDelete($organization);
        $organization->delete();

        return back()->with('status', 'Organisasi berhasil dihapus.');
    }

    private function validated(Request $request, ?Organization $organization = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80', Rule::unique('organizations')->ignore($organization)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'parent_id' => ['nullable', 'exists:organizations,id'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];

        $data['parent_id'] = $this->normalizedParentId($data['type'], $data['parent_id'] ?? null);
        $data['is_active'] = $request->boolean('is_active');

        if ($organization && $data['parent_id'] && (int) $data['parent_id'] === (int) $organization->id) {
            return throw ValidationException::withMessages(['parent_id' => 'Parent organisasi tidak boleh organisasi yang sama.']);
        }

        $this->ensureParentMatchesType($data['type'], $data['parent_id']);

        return $data;
    }

    private function normalizedParentId(string $type, mixed $parentId): ?int
    {
        if ($type === 'university') {
            return null;
        }

        return $parentId ? (int) $parentId : null;
    }

    private function ensureParentMatchesType(string $type, ?int $parentId): void
    {
        if ($type === 'university') {
            return;
        }

        if (! $parentId) {
            throw ValidationException::withMessages(['parent_id' => 'Pilih parent organisasi.']);
        }

        $parentType = Organization::query()->whereKey($parentId)->value('type');
        $expected = $type === 'faculty' ? 'university' : 'faculty';

        if ($parentType !== $expected) {
            throw ValidationException::withMessages(['parent_id' => 'Parent organisasi tidak sesuai dengan jenis organisasi.']);
        }
    }

    private function parentOptions(?Organization $except = null)
    {
        $query = Organization::query()
            ->whereIn('type', ['university', 'faculty'])
            ->orderBy('name');

        if ($except) {
            $query->whereKeyNot($except->id);
        }

        $typeOrder = ['faculty' => 0, 'university' => 1];

        return $query->get()
            ->sortBy(fn (Organization $organization): string => ($typeOrder[$organization->type] ?? 9).'|'.$organization->name)
            ->values();
    }

    private function types(): array
    {
        return [
            'department' => 'Jurusan',
            'faculty' => 'Fakultas',
            'university' => 'Universitas',
        ];
    }

    private function ensureCanDelete(Organization $organization): void
    {
        $references = [
            'organisasi turunan' => $organization->children()->exists(),
            'prodi' => $organization->studyPrograms()->exists(),
            'viewer laporan' => $organization->reportViewerAssignments()->exists(),
        ];

        $usedBy = collect($references)->filter()->keys();

        if ($usedBy->isNotEmpty()) {
            throw ValidationException::withMessages([
                'delete' => 'Organisasi tidak dapat dihapus karena masih digunakan oleh: '.$usedBy->join(', ').'. Nonaktifkan organisasi jika masih dibutuhkan untuk riwayat data.',
            ]);
        }
    }
}
