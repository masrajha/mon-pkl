<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Pembekalan Program') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.orientation-events.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Buka Event Pembekalan</h3>
                <div>
                    <x-input-label for="internship_period_id" value="Periode Program" />
                    <select id="internship_period_id" name="internship_period_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                        <option value="">Pilih periode program</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected(old('internship_period_id', $selectedPeriod) == $period->id)>{{ $period->display_name }}</option>
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
                            <option value="{{ $program->id }}" @selected(old('study_program_id', $selectedStudyProgram) == $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="location_name" value="Nama Lokasi" />
                    <x-text-input id="location_name" name="location_name" class="mt-1 block w-full" :value="old('location_name')" required />
                </div>
                <div>
                    <x-input-label value="Pick Lokasi dari Peta" />
                    <div
                        class="monpkl-map monpkl-form-map mt-1 rounded-lg"
                        data-map-type="place-picker"
                        data-lat-input="latitude"
                        data-lng-input="longitude"
                        data-initial-lat="{{ old('latitude') }}"
                        data-initial-lng="{{ old('longitude') }}"
                        data-map-config='@json($mapConfig)'
                    ></div>
                    <p class="mt-1 text-xs text-gray-500">Klik peta atau geser marker untuk mengisi koordinat lokasi pembekalan.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><x-input-label for="latitude" value="Latitude" /><x-text-input id="latitude" name="latitude" class="mt-1 block w-full" :value="old('latitude')" required /></div>
                    <div><x-input-label for="longitude" value="Longitude" /><x-text-input id="longitude" name="longitude" class="mt-1 block w-full" :value="old('longitude')" required /></div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><x-input-label for="starts_at" value="Dibuka" /><x-text-input id="starts_at" name="starts_at" type="datetime-local" class="mt-1 block w-full" :value="old('starts_at')" /></div>
                    <div><x-input-label for="ends_at" value="Ditutup" /><x-text-input id="ends_at" name="ends_at" type="datetime-local" class="mt-1 block w-full" :value="old('ends_at')" /></div>
                </div>
                <div>
                    <x-input-label for="max_distance_meters" value="Radius Maksimum Meter" />
                    <x-text-input id="max_distance_meters" name="max_distance_meters" type="number" class="mt-1 block w-full" :value="old('max_distance_meters')" />
                    <p class="mt-1 text-xs text-gray-500">Kosongkan untuk memakai konfigurasi radius presensi periode.</p>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300"> Aktif</label>
                <x-primary-button>Simpan Event</x-primary-button>
            </form>

            <section class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Event Pembekalan" description="Kelola event dan rekap presensi pembekalan." search-placeholder="Cari event...">
                    <x-slot name="filters">
                        <div><x-input-label value="Periode Program" /><select name="period_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua periode</option>@foreach ($periods as $period)<option value="{{ $period->id }}" @selected($selectedPeriod === $period->id)>{{ $period->display_name }}</option>@endforeach</select></div>
                        <div class="mt-3"><x-input-label value="Prodi" /><select name="study_program_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected($selectedStudyProgram === $program->id)>{{ $program->name }}</option>@endforeach</select></div>
                    </x-slot>
                </x-table-controls>
                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head"><tr><th class="silat-table-cell">Event</th><th class="silat-table-cell">Lokasi</th><th class="silat-table-cell">Waktu</th><th class="silat-table-cell">Presensi</th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                        <tbody>
                            @forelse ($events as $event)
                                <tr>
                                    <td class="silat-table-cell"><div class="font-medium text-gray-900">{{ $event->name }}</div><div class="text-xs text-gray-500">{{ $event->studyProgram?->name ?: 'Semua prodi' }}</div></td>
                                    <td class="silat-table-cell">{{ $event->location_name }}<div class="text-xs text-gray-500">{{ $event->latitude }}, {{ $event->longitude }}</div></td>
                                    <td class="silat-table-cell">{{ $event->starts_at?->format('d/m/Y H:i') ?: '-' }}<div class="text-xs text-gray-500">s.d. {{ $event->ends_at?->format('d/m/Y H:i') ?: '-' }}</div></td>
                                    <td class="silat-table-cell"><x-badge>{{ number_format($event->attendances_count, 0, ',', '.') }} hadir</x-badge></td>
                                    <td class="silat-table-cell text-right"><a class="silat-secondary-link justify-end" href="{{ route('management.orientation-events.show', $event) }}">Rekap</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="silat-table-cell"><x-empty-state title="Belum ada event pembekalan" icon="fa-users-viewfinder" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-table-pagination :paginator="$events" />
            </section>
        </div>
    </div></div>
</x-app-layout>
