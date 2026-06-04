<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Edit Event Pembekalan') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('management.orientation-events.update', $event) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf @method('PATCH')
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $event->name }}</h3>
                    <p class="text-sm text-gray-500">Perbarui periode, prodi, lokasi, waktu, dan status event pembekalan.</p>
                </div>
                <a class="silat-secondary-link" href="{{ route('management.orientation-events.index') }}">Kembali</a>
            </div>

            <div>
                <x-input-label for="internship_period_id" value="Periode Program" />
                <select id="internship_period_id" name="internship_period_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="">Pilih periode program</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" @selected(old('internship_period_id', $event->internship_period_id) == $period->id)>{{ $period->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="study_program_id" value="Prodi" />
                <select id="study_program_id" name="study_program_id" class="mt-1 block w-full rounded-md border-gray-300">
                    @if (Auth::user()?->hasRole('admin'))
                        <option value="">Semua prodi pada periode</option>
                    @else
                        <option value="">Pilih prodi scope koordinator</option>
                    @endif
                    @foreach ($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected(old('study_program_id', $event->study_program_id) == $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative z-20">
                <x-input-label for="location_name" value="Nama Lokasi" />
                <x-text-input
                    id="location_name"
                    name="location_name"
                    class="mt-1 block w-full"
                    :value="old('location_name', $event->location_name)"
                    data-location-suggest-url="{{ $internalLocationSearchUrl }}"
                    data-external-location-suggest-url="{{ $externalLocationSearchUrl }}"
                    data-map-target="orientation_location_map"
                    autocomplete="off"
                    required
                />
            </div>
            <div class="relative z-10">
                <x-input-label value="Pick Lokasi dari Peta" />
                <div
                    id="orientation_location_map"
                    class="monpkl-map monpkl-form-map mt-1 rounded-lg"
                    data-map-type="place-picker"
                    data-lat-input="latitude"
                    data-lng-input="longitude"
                    data-initial-lat="{{ old('latitude', $event->latitude) }}"
                    data-initial-lng="{{ old('longitude', $event->longitude) }}"
                    data-map-config='@json($mapConfig)'
                ></div>
                <p class="mt-1 text-xs text-gray-500">Klik peta atau geser marker untuk mengisi koordinat lokasi pembekalan.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><x-input-label for="latitude" value="Latitude" /><x-text-input id="latitude" name="latitude" class="mt-1 block w-full" :value="old('latitude', $event->latitude)" required /></div>
                <div><x-input-label for="longitude" value="Longitude" /><x-text-input id="longitude" name="longitude" class="mt-1 block w-full" :value="old('longitude', $event->longitude)" required /></div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><x-input-label for="starts_at" value="Dibuka" /><x-text-input id="starts_at" name="starts_at" type="datetime-local" class="mt-1 block w-full" :value="old('starts_at', $event->starts_at?->format('Y-m-d\TH:i'))" /></div>
                <div><x-input-label for="ends_at" value="Ditutup" /><x-text-input id="ends_at" name="ends_at" type="datetime-local" class="mt-1 block w-full" :value="old('ends_at', $event->ends_at?->format('Y-m-d\TH:i'))" /></div>
            </div>
            <div>
                <x-input-label for="max_distance_meters" value="Radius Maksimum Meter" />
                <x-text-input id="max_distance_meters" name="max_distance_meters" type="number" class="mt-1 block w-full" :value="old('max_distance_meters', $event->max_distance_meters)" />
                <p class="mt-1 text-xs text-gray-500">Kosongkan untuk memakai konfigurasi radius presensi periode.</p>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $event->is_active)) class="rounded border-gray-300"> Aktif</label>
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
