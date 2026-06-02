<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\City;
use App\Models\InternshipPlace;
use App\Models\InternshipPlaceProposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlaceProposalController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = InternshipPlaceProposal::query()->with(['student', 'internshipPeriod.program', 'studyProgram', 'approvedPlace']);

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

        return view('management.place-proposals.index', [
            'proposals' => $this->applyTableSort($query, $request, ['id', 'status', 'name'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'places' => InternshipPlace::query()->orderBy('name')->get(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function approve(Request $request, InternshipPlaceProposal $proposal): RedirectResponse
    {
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

        return back()->with('status', 'Usulan mitra berhasil divalidasi.');
    }

    public function reject(Request $request, InternshipPlaceProposal $proposal): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $proposal->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

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
}
