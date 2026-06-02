<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $place->exists ? __('Edit Mitra') : __('Input Mitra') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div
                            class="monpkl-map monpkl-form-map"
                            data-map-type="place-picker"
                            data-lat-input="latitude"
                            data-lng-input="longitude"
                            data-initial-lat="{{ old('latitude', $place->latitude ?? '') }}"
                            data-initial-lng="{{ old('longitude', $place->longitude ?? '') }}"
                            data-map-config='@json($mapConfig)'
                        ></div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <form method="POST" action="{{ $place->exists ? route('internship-places.update', $place) : route('internship-places.store') }}" class="p-6 space-y-5">
                        @csrf
                        @if ($place->exists)
                            @method('PATCH')
                        @endif

                        <div>
                            <x-input-label for="name" :value="__('Nama Instansi/Perusahaan')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $place->name)" required />
                        </div>

                        <div>
                            <x-input-label for="address" :value="__('Alamat Lengkap')" />
                            <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $place->address) }}</textarea>
                        </div>

                        <div
                            data-region-picker
                            data-province-select="province_id"
                            data-regency-select="regency_id"
                            data-city-name-input="city_name"
                            data-status-target="region_status"
                            data-initial-city="{{ old('city_name', $place->city?->name ?? '') }}"
                        >
                            <x-input-label for="province_id" :value="__('Provinsi')" />
                            <select id="province_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Memuat provinsi...') }}</option>
                            </select>

                            <x-input-label for="regency_id" :value="__('Kab/Kota')" class="mt-3" />
                            <select id="regency_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" disabled>
                                <option value="">{{ __('Pilih provinsi terlebih dahulu') }}</option>
                            </select>

                            <input id="city_name" name="city_name" type="hidden" value="{{ old('city_name', $place->city?->name ?? '') }}">
                            <input name="city_id" type="hidden" value="{{ old('city_id', $place->city_id) }}">
                            <p id="region_status" class="mt-2 text-xs text-gray-500">
                                {{ $place->city?->name ? __('Kota tersimpan: :city', ['city' => $place->city->name]) : __('Pilih kab/kota atau pilih titik pada peta untuk deteksi otomatis.') }}
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="latitude" :value="__('Latitude')" />
                                <x-text-input id="latitude" name="latitude" type="text" class="mt-1 block w-full" :value="old('latitude', $place->latitude ?? '')" required />
                            </div>
                            <div>
                                <x-input-label for="longitude" :value="__('Longitude')" />
                                <x-text-input id="longitude" name="longitude" type="text" class="mt-1 block w-full" :value="old('longitude', $place->longitude ?? '')" required />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="field_supervisor_name" :value="__('Kontak Umum Instansi')" />
                            <x-text-input id="field_supervisor_name" name="field_supervisor_name" type="text" class="mt-1 block w-full" :value="old('field_supervisor_name', $place->field_supervisor_name)" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('Pembimbing lapangan per mahasiswa diisi pada menu Peserta Periode.') }}</p>
                        </div>

                        <div>
                            <x-input-label for="field_supervisor_phone" :value="__('HP Kontak Instansi')" />
                            <x-text-input id="field_supervisor_phone" name="field_supervisor_phone" type="text" class="mt-1 block w-full" :value="old('field_supervisor_phone', $place->field_supervisor_phone)" />
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="visited" value="1" @checked(old('visited', $place->visited)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span>{{ __('Sudah dikunjungi') }}</span>
                        </label>

                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $place->is_active ?? true)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span>{{ __('Aktif untuk pendaftaran mahasiswa') }}</span>
                        </label>

                        <x-primary-button>
                            {{ $place->exists ? __('Simpan Perubahan') : __('Simpan Mitra') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
