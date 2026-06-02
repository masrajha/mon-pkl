<x-app-layout>
    <x-slot name="header">
        <div class="space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Laporan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Rekap Monitoring Program') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Filter periode, program, prodi, tanggal, dan aturan hari kerja untuk membaca aktivitas presensi.</p>
            </div>
            <form method="GET" class="silat-card grid gap-4 p-4 md:grid-cols-3 xl:grid-cols-7">
                @if (Auth::user()->hasRole(['admin', 'dosen']) || $periods->count() > 1)
                    <div>
                        <x-input-label for="period_id" :value="__('Periode Program')" />
                        <x-select-input id="period_id" name="period_id" class="mt-1 text-sm">
                            @if (Auth::user()->hasRole(['admin', 'dosen']))<option value="">{{ __('Semua') }}</option>@endif
                            @foreach ($periods as $period)<option value="{{ $period->id }}" @selected((string) $selectedPeriod === (string) $period->id)>{{ $period->display_name }}</option>@endforeach
                        </x-select-input>
                    </div>
                @endif
                @if (Auth::user()->hasRole(['admin', 'dosen']))
                    <div>
                        <x-input-label for="program_id" :value="__('Program')" />
                        <x-select-input id="program_id" name="program_id" class="mt-1 text-sm">
                            <option value="">{{ __('Semua') }}</option>
                            @foreach ($programs as $program)<option value="{{ $program->id }}" @selected((string) $selectedProgram === (string) $program->id)>{{ $program->name }}</option>@endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="study_program_id" :value="__('Prodi')" />
                        <x-select-input id="study_program_id" name="study_program_id" class="mt-1 text-sm">
                            <option value="">{{ __('Semua') }}</option>
                            @foreach ($studyPrograms as $studyProgram)<option value="{{ $studyProgram->id }}" @selected((string) $selectedStudyProgram === (string) $studyProgram->id)>{{ $studyProgram->name }}</option>@endforeach
                        </x-select-input>
                    </div>
                @endif
                <div><x-input-label for="start_date" :value="__('Dari')" /><x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full text-sm" :value="$startDate" /></div>
                <div><x-input-label for="end_date" :value="__('Sampai')" /><x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full text-sm" :value="$endDate" /></div>
                <div class="space-y-2">
                    <x-input-label :value="__('Termasuk')" />
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="include_saturday" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" @checked($includeSaturday)> Sabtu</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="include_sunday" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" @checked($includeSunday)> Minggu</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="include_holidays" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" @checked($includeHolidays)> Libur</label>
                </div>
                <div class="flex items-end"><x-primary-button><x-icon name="fa-chart-column" /> Lihat</x-primary-button></div>
            </form>
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell space-y-6">
        <div class="silat-stat-grid">
            <div class="silat-stat-card"><p class="silat-stat-label">Mahasiswa</p><p class="silat-stat-value">{{ number_format($totals['students'], 0, ',', '.') }}</p></div>
            <div class="silat-stat-card"><p class="silat-stat-label">Total Hari Hadir</p><p class="silat-stat-value">{{ number_format($totals['attendance_days'], 0, ',', '.') }}</p></div>
            <div class="silat-stat-card"><p class="silat-stat-label">Total Durasi</p><p class="silat-stat-value">{{ number_format($totals['duration_hours'], 2, ',', '.') }} jam</p></div>
            <div class="silat-stat-card"><p class="silat-stat-label">Filter Aktif</p><p class="mt-2 text-base font-semibold text-gray-900">{{ $selectedPeriod ? 'Periode spesifik' : 'Semua periode' }}</p></div>
        </div>

        <section class="silat-card">
            <div class="silat-section-header">
                <div><h3 class="silat-section-title">Tabel Rekap Monitoring</h3><p class="silat-section-description">Ringkasan kehadiran, jarak, durasi, dan rentang jam presensi.</p></div>
                <div class="flex gap-2"><button type="button" class="silat-btn-secondary" disabled>Ekspor PDF</button><button type="button" class="silat-btn-secondary" disabled>Ekspor Excel</button></div>
            </div>
            <div class="overflow-x-auto">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell">Mahasiswa</th><th class="silat-table-cell">NPM</th><th class="silat-table-cell">Email</th><th class="silat-table-cell">Mitra</th><th class="silat-table-cell">Hari</th><th class="silat-table-cell">Check-in</th><th class="silat-table-cell">Rata-rata Jarak</th><th class="silat-table-cell">Durasi</th><th class="silat-table-cell">Jam Masuk</th><th class="silat-table-cell">Jam Pulang</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 bg-white text-gray-700">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="silat-table-cell"><div class="flex items-center gap-3">@if ($row['photo_url'])<img src="{{ $row['photo_url'] }}" alt="" class="h-9 w-9 rounded-full object-cover">@else<div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-500">{{ Str::of($row['name'])->substr(0, 1)->upper() }}</div>@endif<div><p class="font-medium text-gray-900">{{ $row['name'] }}</p><p class="text-xs text-gray-500">{{ $row['study_program'] }} / {{ $row['period'] }}</p></div></div></td>
                                <td class="silat-table-cell">{{ $row['npm'] }}</td><td class="silat-table-cell">{{ $row['email'] }}</td><td class="silat-table-cell">{{ $row['place'] }}</td><td class="silat-table-cell">{{ $row['attendance_days'] }}</td><td class="silat-table-cell">{{ $row['check_ins_count'] }}</td><td class="silat-table-cell">{{ $row['average_distance_meters'] === null ? '-' : number_format($row['average_distance_meters'], 2, ',', '.').' m' }}</td><td class="silat-table-cell">{{ number_format($row['duration_hours'], 2, ',', '.') }} jam</td><td class="silat-table-cell">{{ $row['min_check_in'] }} - {{ $row['max_check_in'] }}</td><td class="silat-table-cell">{{ $row['min_check_out'] }} - {{ $row['max_check_out'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="silat-table-cell"><x-empty-state title="Tidak ada data pada filter ini" icon="fa-chart-column" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
