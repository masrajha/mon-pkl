<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Peta Mitra') }}
            </h2>
            @include('maps.partials.filters')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.25fr)_minmax(24rem,0.75fr)]">
                <div class="silat-card overflow-hidden">
                    <div
                        id="places-map"
                        class="monpkl-map"
                        data-map-type="places"
                        data-data-url="{{ route('maps.places.data', request()->query()) }}"
                        data-route-url="{{ route('maps.places.route', request()->query()) }}"
                        data-table-target="places-table-body"
                        data-route-form="places-route-form"
                        data-route-table-target="places-route-table-body"
                        data-route-status-target="places-route-status"
                        data-route-summary-target="places-route-summary"
                        data-map-config='@json($mapConfig)'
                    ></div>
                </div>

                <div class="space-y-4">
                    <div class="silat-card overflow-hidden">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <h3 class="text-sm font-semibold text-gray-900">{{ __('Rute Kunjungan') }}</h3>
                            <p id="places-route-summary" class="text-xs text-gray-500">{{ __('Tentukan titik awal untuk menghitung urutan mitra.') }}</p>
                        </div>
                        <form id="places-route-form" class="grid gap-3 border-b border-gray-100 p-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <p class="text-sm font-medium text-gray-900">{{ __('Start Awal') }}</p>
                            </div>
                            <div>
                                <x-input-label for="route_start_lat" :value="__('Latitude')" />
                                <x-text-input id="route_start_lat" name="start_lat" type="text" class="mt-1 block w-full text-sm" value="-5.3642946" placeholder="-5.3642946" />
                            </div>
                            <div>
                                <x-input-label for="route_start_lng" :value="__('Longitude')" />
                                <x-text-input id="route_start_lng" name="start_lng" type="text" class="mt-1 block w-full text-sm" value="105.2430467" placeholder="105.2430467" />
                            </div>
                            <div class="flex items-end gap-2 sm:col-span-2">
                                <button type="submit" class="silat-btn"><x-icon name="fa-route" /> {{ __('Hitung Rute') }}</button>
                                <button type="button" class="silat-btn-secondary" data-route-current-location><x-icon name="fa-location-crosshairs" /> {{ __('Lokasi Saya') }}</button>
                            </div>
                            <p id="places-route-status" class="hidden text-sm sm:col-span-2"></p>
                        </form>
                        <div class="max-h-80 overflow-auto">
                            <table class="silat-table">
                                <thead class="silat-table-head sticky top-0">
                                    <tr>
                                        <th class="silat-table-cell">{{ __('Urut') }}</th>
                                        <th class="silat-table-cell">{{ __('Mitra') }}</th>
                                        <th class="silat-table-cell">{{ __('Jarak') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="places-route-table-body">
                                    <tr>
                                        <td colspan="3" class="silat-table-cell text-center text-gray-500">{{ __('Rute belum dihitung.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="silat-card overflow-hidden">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <h3 class="text-sm font-semibold text-gray-900">{{ __('Daftar Mitra') }}</h3>
                            <p class="text-xs text-gray-500">{{ __('Klik baris untuk mengarahkan peta ke lokasi.') }}</p>
                        </div>
                        <div class="max-h-[36rem] overflow-auto">
                            <table class="silat-table">
                                <thead class="silat-table-head sticky top-0">
                                    <tr>
                                        <th class="silat-table-cell">{{ __('Mitra') }}</th>
                                        <th class="silat-table-cell">{{ __('Kota') }}</th>
                                        <th class="silat-table-cell">{{ __('Peserta') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="places-table-body">
                                    <tr>
                                        <td colspan="3" class="silat-table-cell text-center text-gray-500">{{ __('Memuat data mitra...') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
