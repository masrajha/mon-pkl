<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\RelocationRequest;
use App\Services\RelocationEmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RelocationRequestController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(private readonly RelocationEmailNotificationService $relocationEmails)
    {
    }

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

        $this->scopeByCoordinator($query, $request);

        return view('management.relocations.index', [
            'requests' => $this->applyTableSort($query, $request, ['id', 'status'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function update(Request $request, RelocationRequest $relocation): RedirectResponse
    {
        $this->authorizeScope($relocation, $request);

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
                    'admin_note' => $data['admin_note'] ?? 'Permohonan pindah mitra disetujui.',
                ]);
            }
        });
        $this->relocationEmails->reviewed($relocation->refresh());

        return back()->with('status', 'Permohonan pindah mitra berhasil diproses.');
    }

    private function scopeByCoordinator($query, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $assignments = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();

        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('enrollment', function ($query) use ($assignments): void {
            $query->where(function ($query) use ($assignments): void {
                foreach ($assignments as $assignment) {
                    $query->orWhere(function ($query) use ($assignment): void {
                        $query->where('internship_period_id', $assignment->internship_period_id)
                            ->where('study_program_id', $assignment->study_program_id);
                    });
                }
            });
        });
    }

    private function authorizeScope(RelocationRequest $relocation, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $enrollment = $relocation->enrollment;
        $allowed = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $enrollment?->internship_period_id)
            ->where('study_program_id', $enrollment?->study_program_id)
            ->exists();

        abort_unless($allowed, 403);
    }
}
