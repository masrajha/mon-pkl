<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPlace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlaceController extends Controller
{
    public function index(Request $request): View
    {
        $query = InternshipPlace::query()->with('city')->withCount('enrollments');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q')->toString().'%');
        }

        return view('management.places.index', [
            'places' => $query->orderBy('name')->paginate(20)->withQueryString(),
            'allPlaces' => InternshipPlace::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:delete,merge'],
            'place_ids' => ['required', 'array', 'min:1'],
            'place_ids.*' => ['integer', 'exists:internship_places,id'],
            'target_place_id' => ['nullable', 'integer', 'exists:internship_places,id'],
        ]);

        $placeIds = collect($validated['place_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        if ($validated['action'] === 'delete') {
            return $this->bulkDelete($placeIds);
        }

        return $this->bulkMerge($placeIds, (int) $validated['target_place_id']);
    }

    private function bulkDelete($placeIds): RedirectResponse
    {
        $blocked = InternshipPlace::query()
            ->whereIn('id', $placeIds)
            ->withCount('enrollments')
            ->get()
            ->filter(fn (InternshipPlace $place) => $place->enrollments_count > 0);

        if ($blocked->isNotEmpty()) {
            throw ValidationException::withMessages([
                'place_ids' => 'Tempat PKL hanya dapat dihapus jika jumlah peserta 0. Masih ada peserta pada: '.$blocked->pluck('name')->join(', '),
            ]);
        }

        $deleted = InternshipPlace::query()->whereIn('id', $placeIds)->delete();

        return back()->with('status', $deleted.' tempat PKL tanpa peserta berhasil dihapus.');
    }

    private function bulkMerge($placeIds, int $targetPlaceId): RedirectResponse
    {
        if (! $targetPlaceId) {
            throw ValidationException::withMessages(['target_place_id' => 'Pilih tempat PKL tujuan merge.']);
        }

        if (! $placeIds->contains($targetPlaceId)) {
            throw ValidationException::withMessages(['target_place_id' => 'Tempat tujuan merge harus termasuk dalam data yang dipilih.']);
        }

        if ($placeIds->count() < 2) {
            throw ValidationException::withMessages(['place_ids' => 'Pilih minimal dua tempat PKL untuk merge.']);
        }

        $sourceIds = $placeIds->reject(fn ($id) => $id === $targetPlaceId)->values();

        DB::transaction(function () use ($sourceIds, $targetPlaceId): void {
            InternshipEnrollment::query()
                ->whereIn('internship_place_id', $sourceIds)
                ->update(['internship_place_id' => $targetPlaceId]);

            InternshipPlace::query()
                ->whereIn('id', $sourceIds)
                ->delete();
        });

        return back()->with('status', 'Merge tempat PKL berhasil. Peserta dari '.count($sourceIds).' data sumber sudah dipindahkan.');
    }
}
