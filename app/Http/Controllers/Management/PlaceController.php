<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlaceController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $selectedPeriod = $request->integer('period_id') ?: null;
        $query = InternshipPlace::query()
            ->with('city')
            ->withCount([
                'enrollments' => fn ($query) => $query->when($selectedPeriod, fn ($query) => $query->where('internship_period_id', $selectedPeriod)),
            ]);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('address', 'like', '%'.$search.'%')
                ->orWhereHas('city', fn ($query) => $query->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        return view('management.places.index', [
            'places' => $this->applyTableSort($query, $request, ['name', 'is_active', 'id'], 'name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'periods' => InternshipPeriod::query()->with('program')->orderByDesc('is_active')->orderByDesc('id')->get(),
            'selectedPeriod' => $selectedPeriod,
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $search = trim($request->string('q')->toString());

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $query = InternshipPlace::query()
            ->with('city')
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('address', 'like', '%'.$search.'%')
                ->orWhereHas('city', fn ($query) => $query->where('name', 'like', '%'.$search.'%')));

        return response()->json(
            $query
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'city_id', 'name', 'address', 'is_active'])
                ->map(fn (InternshipPlace $place): array => [
                    'id' => $place->id,
                    'name' => $place->name,
                    'address' => $place->address,
                    'city' => $place->city?->name,
                    'is_active' => $place->is_active,
                    'label' => $place->name.($place->city?->name ? ' - '.$place->city->name : ''),
                ])
                ->values(),
        );
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
                'place_ids' => 'Mitra hanya dapat dihapus jika jumlah peserta 0. Masih ada peserta pada: '.$blocked->pluck('name')->join(', '),
            ]);
        }

        $deleted = InternshipPlace::query()->whereIn('id', $placeIds)->delete();

        return back()->with('status', $deleted.' mitra tanpa peserta berhasil dihapus.');
    }

    private function bulkMerge($placeIds, int $targetPlaceId): RedirectResponse
    {
        if (! $targetPlaceId) {
            throw ValidationException::withMessages(['target_place_id' => 'Pilih mitra tujuan merge.']);
        }

        if (! $placeIds->contains($targetPlaceId)) {
            throw ValidationException::withMessages(['target_place_id' => 'Mitra tujuan merge harus termasuk dalam data yang dipilih.']);
        }

        if ($placeIds->count() < 2) {
            throw ValidationException::withMessages(['place_ids' => 'Pilih minimal dua mitra untuk merge.']);
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

        return back()->with('status', 'Merge mitra berhasil. Peserta dari '.count($sourceIds).' data sumber sudah dipindahkan.');
    }
}
