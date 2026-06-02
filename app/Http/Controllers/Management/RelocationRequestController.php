<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\RelocationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RelocationRequestController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = RelocationRequest::query()
            ->with(['enrollment.student', 'enrollment.internshipPeriod.program', 'enrollment.studyProgram', 'currentPlace', 'newPlace', 'reviewer']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('reason', 'like', '%'.$search.'%')
                ->orWhereHas('enrollment.student', fn ($query) => $query->where('full_name', 'like', '%'.$search.'%')->orWhere('npm', 'like', '%'.$search.'%'))
                ->orWhereHas('currentPlace', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('newPlace', fn ($query) => $query->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('management.relocations.index', [
            'requests' => $this->applyTableSort($query, $request, ['id', 'status'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function update(Request $request, RelocationRequest $relocation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $relocation, $data): void {
            $relocation->update([
                'status' => $data['status'],
                'admin_note' => $data['admin_note'] ?? null,
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);

            if ($data['status'] === 'approved') {
                $relocation->enrollment()->update([
                    'internship_place_id' => $relocation->new_internship_place_id,
                    'admin_note' => $data['admin_note'] ?? 'Permohonan pindah tempat disetujui.',
                ]);
            }
        });

        return back()->with('status', 'Permohonan pindah tempat berhasil diproses.');
    }
}
