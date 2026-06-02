<x-app-layout>
    <x-slot name="header">
        <div class="space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Mitra</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Data Mitra') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Pilih mitra dari peta atau tabel, lalu lanjutkan pendaftaran program.</p>
                <p class="mt-1 text-xs text-gray-500">Jumlah peserta dihitung untuk periode aktif: {{ $activePeriod?->display_name ?: 'belum ada periode aktif' }}.</p>
            </div>

            <form method="GET" class="silat-card p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <x-input-label for="q" value="Cari Mitra" />
                        <div class="silat-table-search mt-1 sm:max-w-none">
                            <x-icon name="fa-magnifying-glass" class="silat-table-search-icon" />
                            <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari instansi, alamat, atau kota..." class="silat-table-search-input" data-table-search-input>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('student.places.index') }}" class="silat-btn-secondary">Reset</a>
                        <x-primary-button><x-icon name="fa-filter" /> Filter</x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(24rem,0.8fr)]">
                <section class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Peta Mitra</h3>
                            <p class="silat-section-description">Klik marker atau baris tabel untuk melihat posisi mitra.</p>
                        </div>
                    </div>
                    <div
                        id="student-places-map"
                        class="monpkl-map"
                        data-map-type="places"
                        data-data-url="{{ $dataUrl }}"
                        data-table-target="student-places-table-body"
                        data-map-config='@json($mapConfig)'
                    ></div>
                </section>

                <section class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Daftar Mitra</h3>
                            <p class="silat-section-description">Gunakan aksi Daftar untuk membawa pilihan mitra ke form pendaftaran.</p>
                        </div>
                    </div>
                    <div class="max-h-[42rem] overflow-auto">
                        <table class="silat-table">
                            <thead class="silat-table-head sticky top-0">
                                <tr>
                                    <th class="silat-table-cell">Mitra</th>
                                    <th class="silat-table-cell">Kota</th>
                                    <th class="silat-table-cell">Peserta Periode Aktif</th>
                                    <th class="silat-table-cell text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="student-places-table-body" data-colspan="4">
                                <tr>
                                    <td colspan="4" class="silat-table-cell text-center text-gray-500">Memuat data mitra...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
