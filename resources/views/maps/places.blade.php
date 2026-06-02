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
                        data-table-target="places-table-body"
                        data-map-config='@json($mapConfig)'
                    ></div>
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
                                    <th class="silat-table-cell">{{ __('Tempat') }}</th>
                                    <th class="silat-table-cell">{{ __('Kota') }}</th>
                                    <th class="silat-table-cell">{{ __('Peserta') }}</th>
                                </tr>
                            </thead>
                            <tbody id="places-table-body">
                                <tr>
                                    <td colspan="3" class="silat-table-cell text-center text-gray-500">{{ __('Memuat tempat PKL...') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
