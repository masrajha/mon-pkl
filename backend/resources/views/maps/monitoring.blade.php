<x-app-layout>
    <x-slot name="header">
        <div class="space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Monitoring</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Peta Monitoring') }}</h2>
                <p class="mt-1 text-sm text-gray-500">State awal menampilkan periode aktif; gunakan filter untuk periode lain, prodi, atau rentang tanggal.</p>
            </div>
            @include('maps.partials.filters', ['showDateFilters' => true])
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(24rem,0.8fr)]">
                <section class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Sebaran Check-in</h3>
                            <p class="silat-section-description">{{ $selectedPeriod ? $periods->firstWhere('id', $selectedPeriod)?->display_name : 'Semua periode program' }} &middot; {{ $todayOnly ? 'Hari ini' : $startDate.' s.d. '.$endDate }}</p>
                        </div>
                        <x-badge>{{ $todayOnly ? 'Hari Ini' : 'Range Tanggal' }}</x-badge>
                    </div>
                    <div
                        id="monitoring-map"
                        class="monpkl-map"
                        data-map-type="monitoring"
                        data-data-url="{{ route('maps.monitoring.data', array_merge(request()->query(), ['period_id' => $selectedPeriod, 'start_date' => $startDate, 'end_date' => $endDate, 'today_only' => $todayOnly ? 1 : null, 'limit' => 1000])) }}"
                        data-table-target="monitoring-table-body"
                        data-count-target="monitoring-count"
                        data-map-config='@json($mapConfig)'
                    ></div>
                </section>

                <section class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Data Marker</h3>
                            <p class="silat-section-description"><span id="monitoring-count">0</span> check-in terkait marker pada peta.</p>
                        </div>
                    </div>
                    <div class="max-h-[42rem] overflow-auto">
                        <table class="silat-table">
                            <thead class="silat-table-head sticky top-0">
                                <tr>
                                    <th class="silat-table-cell">Mahasiswa</th>
                                    <th class="silat-table-cell">Waktu</th>
                                    <th class="silat-table-cell">Jarak</th>
                                </tr>
                            </thead>
                            <tbody id="monitoring-table-body">
                                <tr><td colspan="3" class="silat-table-cell text-center text-gray-500">Memuat data monitoring...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
