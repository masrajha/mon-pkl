<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\City;
use App\Models\InternshipPlace;
use App\Models\InternshipPlaceProposal;
use App\Services\PlaceProposalEmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlaceProposalController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(private readonly PlaceProposalEmailNotificationService $proposalEmails)
    {
    }

    public function index(Request $request): View
    {
        $query = InternshipPlaceProposal::query()->with(['student', 'internshipPeriod.program', 'studyProgram', 'city', 'approvedPlace', 'reviewer']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('address', 'like', '%'.$search.'%')
                ->orWhere('city_name', 'like', '%'.$search.'%')
                ->orWhereHas('student', fn ($query) => $query->where('full_name', 'like', '%'.$search.'%')->orWhere('npm', 'like', '%'.$search.'%')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $this->scopeByCoordinator($query, $request);

        return view('management.place-proposals.index', [
            'proposals' => $this->applyTableSort($query, $request, ['id', 'status', 'name'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function approve(Request $request, InternshipPlaceProposal $proposal): RedirectResponse
    {
        $this->authorizeScope($proposal, $request);
        $this->ensurePending($proposal);

        $data = $request->validate([
            'mode' => ['required', Rule::in(['new', 'merge'])],
            'internship_place_id' => ['required_if:mode,merge', 'nullable', 'exists:internship_places,id'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($proposal, $data, $request): void {
            $place = $data['mode'] === 'merge'
                ? InternshipPlace::query()->findOrFail($data['internship_place_id'])
                : $this->createPlace($proposal);

            $proposal->update([
                'status' => $data['mode'] === 'merge' ? 'merged' : 'approved',
                'admin_note' => $data['admin_note'] ?? null,
                'approved_internship_place_id' => $place->id,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        });
        $this->proposalEmails->reviewed($proposal->refresh(), $proposal->status);

        return back()->with('status', 'Usulan mitra berhasil divalidasi.');
    }

    public function reject(Request $request, InternshipPlaceProposal $proposal): RedirectResponse
    {
        $this->authorizeScope($proposal, $request);
        $this->ensurePending($proposal);

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $proposal->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        $this->proposalEmails->reviewed($proposal->refresh(), 'rejected');

        return back()->with('status', 'Usulan mitra ditolak.');
    }

    private function createPlace(InternshipPlaceProposal $proposal): InternshipPlace
    {
        $cityId = $proposal->city_id;

        if (! $cityId && $proposal->city_name) {
            $cityId = City::query()->firstOrCreate(['name' => $proposal->city_name])->id;
        }

        return InternshipPlace::query()->create([
            'city_id' => $cityId,
            'name' => $proposal->name,
            'address' => $proposal->address,
            'field_supervisor_name' => $proposal->field_supervisor_name,
            'field_supervisor_phone' => $proposal->field_supervisor_phone,
            'latitude' => $proposal->latitude,
            'longitude' => $proposal->longitude,
            'visited' => false,
        ]);
    }

    private function ensurePending(InternshipPlaceProposal $proposal): void
    {
        if ($proposal->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Usulan hanya dapat diproses saat masih Menunggu.']);
        }
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

        $query->where(function ($query) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $query->orWhere(function ($query) use ($assignment): void {
                    $query->where('internship_period_id', $assignment->internship_period_id)
                        ->where('study_program_id', $assignment->study_program_id);
                });
            }
        });
    }

    private function authorizeScope(InternshipPlaceProposal $proposal, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $allowed = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $proposal->internship_period_id)
            ->where('study_program_id', $proposal->study_program_id)
            ->exists();

        abort_unless($allowed, 403);
    }
}
