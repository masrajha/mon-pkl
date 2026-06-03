<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Mahasiswa</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Usulan Mitra Baru') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Tentukan titik lokasi pada peta, lalu lengkapi identitas instansi.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell">
            @if ($errors->any())
                <x-alert variant="danger" class="mb-6">{{ $errors->first() }}</x-alert>
            @endif

            <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
                <section class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Titik Lokasi Mitra</h3>
                            <p class="silat-section-description">Klik peta atau geser marker untuk menentukan koordinat.</p>
                        </div>
                    </div>
                    <div
                        id="student_proposal_location_map"
                        class="monpkl-map monpkl-form-map"
                        data-map-type="place-picker"
                        data-lat-input="latitude"
                        data-lng-input="longitude"
                        data-initial-lat="{{ old('latitude') }}"
                        data-initial-lng="{{ old('longitude') }}"
                        data-map-config='@json($mapConfig)'
                    ></div>
                </section>

                <section class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Data Usulan</h3>
                            <p class="silat-section-description">Prodi mengikuti profil mahasiswa.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('student.proposals.store') }}" class="space-y-5 p-5">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="internship_period_id" value="Periode Program" />
                                <x-select-input id="internship_period_id" name="internship_period_id" class="mt-1" required>
                                    <option value="">Pilih periode program</option>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->id }}" @selected(old('internship_period_id') == $period->id)>{{ $period->display_name }}</option>
                                    @endforeach
                                </x-select-input>
                            </div>

                            <div>
                                <x-input-label value="Prodi" />
                                <div class="mt-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">
                                    <span class="font-medium text-gray-900">{{ $student->studyProgram?->name ?: 'Belum diisi' }}</span>
                                    @if ($student->studyProgram?->code)
                                        <span class="text-gray-500">({{ $student->studyProgram->code }})</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="relative z-20">
                            <x-input-label for="name" value="Nama Instansi/Perusahaan" />
                            <x-text-input
                                id="name"
                                name="name"
                                class="mt-1 block w-full"
                                :value="old('name')"
                                data-location-suggest-url="{{ $internalLocationSearchUrl }}"
                                data-external-location-suggest-url="{{ $externalLocationSearchUrl }}"
                                data-map-target="student_proposal_location_map"
                                data-address-target="address"
                                autocomplete="off"
                                required
                            />
                        </div>

                        <div>
                            <x-input-label for="address" value="Alamat Lengkap" />
                            <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address') }}</textarea>
                        </div>

                        <div
                            data-region-picker
                            data-province-select="province_id"
                            data-regency-select="regency_id"
                            data-city-name-input="city_name"
                            data-status-target="region_status"
                            data-initial-city="{{ old('city_name') }}"
                        >
                            <x-input-label for="province_id" value="Provinsi" />
                            <select id="province_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Memuat provinsi...</option>
                            </select>

                            <x-input-label for="regency_id" value="Kab/Kota" class="mt-3" />
                            <select id="regency_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" disabled>
                                <option value="">Pilih provinsi terlebih dahulu</option>
                            </select>

                            <input id="city_name" name="city_name" type="hidden" value="{{ old('city_name') }}">
                            <input name="city_id" type="hidden" value="{{ old('city_id') }}">
                            <p id="region_status" class="mt-2 text-xs text-gray-500">
                                Pilih kab/kota atau pilih titik pada peta untuk deteksi otomatis.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="latitude" value="Latitude" />
                                <x-text-input id="latitude" name="latitude" class="mt-1 block w-full" :value="old('latitude')" required />
                            </div>
                            <div>
                                <x-input-label for="longitude" value="Longitude" />
                                <x-text-input id="longitude" name="longitude" class="mt-1 block w-full" :value="old('longitude')" required />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="field_supervisor_name" value="Kontak Umum Instansi" />
                            <x-text-input id="field_supervisor_name" name="field_supervisor_name" class="mt-1 block w-full" :value="old('field_supervisor_name')" />
                            <p class="mt-1 text-xs text-gray-500">Pembimbing lapangan per mahasiswa akan dikonfirmasi pada data peserta periode.</p>
                        </div>

                        <div>
                            <x-input-label for="field_supervisor_phone" value="HP Kontak Instansi" />
                            <x-text-input id="field_supervisor_phone" name="field_supervisor_phone" class="mt-1 block w-full" :value="old('field_supervisor_phone')" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button><x-icon name="fa-paper-plane" /> Kirim Usulan</x-primary-button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
