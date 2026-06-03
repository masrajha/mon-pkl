<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\InternshipPlace;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InternshipPlaceController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function create(): View
    {
        return view('internship-places.form', [
            'place' => new InternshipPlace(),
            'cities' => $this->cities(),
            'mapConfig' => $this->configurations->frontendMapConfig(null),
            'internalLocationSearchUrl' => route('locations.search'),
            'externalLocationSearchUrl' => $this->externalLocationSearchUrl(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        InternshipPlace::query()->create($this->validated($request));

        return redirect()
            ->route('maps.places')
            ->with('status', 'Lokasi mitra berhasil disimpan.');
    }

    public function edit(InternshipPlace $internshipPlace): View
    {
        return view('internship-places.form', [
            'place' => $internshipPlace,
            'cities' => $this->cities(),
            'mapConfig' => $this->configurations->frontendMapConfig(null),
            'internalLocationSearchUrl' => route('locations.search'),
            'externalLocationSearchUrl' => $this->externalLocationSearchUrl(),
        ]);
    }

    public function update(Request $request, InternshipPlace $internshipPlace): RedirectResponse
    {
        $internshipPlace->update($this->validated($request, $internshipPlace));

        return redirect()
            ->route('maps.places')
            ->with('status', 'Lokasi mitra berhasil diperbarui.');
    }

    private function validated(Request $request, ?InternshipPlace $place = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'field_supervisor_name' => ['nullable', 'string', 'max:255'],
            'field_supervisor_phone' => ['nullable', 'string', 'max:50'],
            'contact_student_phone' => ['nullable', 'string', 'max:50'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'visited' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['visited' => false, 'is_active' => false];

        $validated['visited'] = $request->boolean('visited');
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : ! $place;

        if (! empty($validated['city_name'])) {
            $validated['city_id'] = City::query()->firstOrCreate([
                'name' => $validated['city_name'],
            ])->id;
        }

        unset($validated['city_name']);

        return $validated;
    }

    private function cities()
    {
        return City::query()->orderBy('name')->get();
    }

    private function externalLocationSearchUrl(): string
    {
        return 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&addressdetails=1&countrycodes=id&q={query}';
    }
}
