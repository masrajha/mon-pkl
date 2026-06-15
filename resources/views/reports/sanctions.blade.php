<x-app-layout>
    <style>
        @media (min-width: 900px) {
            .sanctions-filter-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Analisis & Laporan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">Rekap Pelanggaran & Sanksi</h2>
                <p class="mt-1 text-sm text-gray-500">Pisahkan sumber sanksi dari presensi, keterlambatan laporan, dan pengurangan final.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @include('reports.partials.export-buttons', ['type' => 'sanctions'])
                <a class="silat-secondary-link" href="{{ route('reports.final-scores', request()->only(['scope', 'period_id', 'program_id', 'study_program_id'])) }}">
                    <x-icon name="fa-calculator" /> Rekap nilai akhir
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('management.partials.nav')
            @include('reports.partials.report-tabs')
            @php($drillBase = request()->only(['scope', 'period_id', 'program_id', 'study_program_id', 'start_date', 'end_date']))

            <form method="GET" action="{{ route('reports.sanctions') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" data-period-date-sync>
                @if (request()->filled('scope'))<input type="hidden" name="scope" value="{{ request('scope') }}">@endif
                <div class="sanctions-filter-grid grid grid-cols-1 gap-4">
                    <div>
                        <x-input-label for="period_id" value="Periode Program" />
                        <select id="period_id" name="period_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">Semua periode</option>
                            @foreach ($periods as $period)
                                @php($dateRange = $periodDateRanges[$period->id] ?? null)
                                <option value="{{ $period->id }}" data-start-date="{{ $dateRange['start'] ?? '' }}" data-end-date="{{ $dateRange['end'] ?? '' }}" @selected((string) $selectedPeriod === (string) $period->id)>{{ $period->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="program_id" value="Program" />
                        <select id="program_id" name="program_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">Semua program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected((string) $selectedProgram === (string) $program->id)>{{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="study_program_id" value="Prodi" />
                        <select id="study_program_id" name="study_program_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">Semua prodi</option>
                            @foreach ($studyPrograms as $studyProgram)
                                <option value="{{ $studyProgram->id }}" @selected((string) $selectedStudyProgram === (string) $studyProgram->id)>{{ $studyProgram->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="start_date" value="Dari" />
                        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full text-sm" :value="$startDate" />
                    </div>
                    <div>
                        <x-input-label for="end_date" value="Sampai" />
                        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full text-sm" :value="$endDate" />
                    </div>
                    <input type="hidden" name="only_with_sanctions" value="0">
                    <div class="flex flex-col justify-end gap-3">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="only_with_sanctions" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" @checked($onlyWithSanctions)>
                            Hanya ada sanksi
                        </label>
                        <button class="silat-btn w-full justify-center" type="submit"><x-icon name="fa-filter" /> Terapkan</button>
                    </div>
                </div>
            </form>

            <section class="grid gap-4 md:grid-cols-5">
                <a class="silat-stat-card transition hover:border-blue-300 hover:bg-blue-50/40 hover:shadow-sm" href="{{ route('reports.drill-down', array_filter($drillBase + ['source' => 'sanctions'])) }}"><p class="silat-stat-label">Peserta Terdampak</p><p class="silat-stat-value">{{ number_format($totals['students'], 0, ',', '.') }}</p><p class="silat-stat-note">Lihat peserta</p></a>
                <div class="silat-stat-card"><p class="silat-stat-label">Sanksi Presensi</p><p class="silat-stat-value">{{ number_format($totals['attendance'], 2, ',', '.') }}</p></div>
                <div class="silat-stat-card"><p class="silat-stat-label">Sanksi Laporan</p><p class="silat-stat-value">{{ number_format($totals['reports'], 2, ',', '.') }}</p></div>
                <div class="silat-stat-card"><p class="silat-stat-label">Pengurangan Final</p><p class="silat-stat-value">{{ number_format($totals['final_deduction'], 2, ',', '.') }}</p></div>
                <div class="silat-stat-card"><p class="silat-stat-label">Total</p><p class="silat-stat-value">{{ number_format($totals['total'], 2, ',', '.') }}</p></div>
            </section>

            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Daftar Sanksi per Peserta</h3>
                        <p class="silat-section-description">Nilai total adalah akumulasi sanksi presensi, sanksi laporan, dan pengurangan final pada rentang filter.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Mahasiswa</th>
                                <th class="silat-table-cell">Mitra</th>
                                <th class="silat-table-cell text-right">Presensi</th>
                                <th class="silat-table-cell text-right">Laporan</th>
                                <th class="silat-table-cell text-right">Pengurangan Final</th>
                                <th class="silat-table-cell text-right">Total</th>
                                <th class="silat-table-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="silat-table-cell">
                                        <p class="font-semibold text-gray-900">{{ $row['student'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $row['npm'] }} · {{ $row['study_program'] }} · {{ $row['period'] }}</p>
                                    </td>
                                    <td class="silat-table-cell">{{ $row['place'] }}</td>
                                    <td class="silat-table-cell text-right">
                                        <p class="font-semibold text-gray-900">{{ number_format($row['attendance_sanctions'], 2, ',', '.') }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($row['attendance_count'], 0, ',', '.') }} kejadian</p>
                                    </td>
                                    <td class="silat-table-cell text-right">
                                        <p class="font-semibold text-gray-900">{{ number_format($row['report_sanctions'], 2, ',', '.') }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($row['report_count'], 0, ',', '.') }} dokumen</p>
                                    </td>
                                    <td class="silat-table-cell text-right">{{ number_format($row['final_deduction'], 2, ',', '.') }}</td>
                                    <td class="silat-table-cell text-right text-base font-semibold text-gray-950">{{ number_format($row['total'], 2, ',', '.') }}</td>
                                    <td class="silat-table-cell text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a class="silat-secondary-link" href="{{ route('reports.monitoring', array_filter(['scope' => request('scope'), 'period_id' => $row['enrollment']->internship_period_id, 'study_program_id' => $row['enrollment']->study_program_id])) }}">Presensi</a>
                                            @if ($canReviewReports)
                                                <a class="silat-secondary-link" href="{{ route('management.submission-progress.index', ['period_id' => $row['enrollment']->internship_period_id, 'q' => $row['npm']]) }}">Laporan</a>
                                            @endif
                                            @if ($canFinalizeScores)
                                                <a class="silat-secondary-link" href="{{ route('management.final-assessments.index', ['period_id' => $row['enrollment']->internship_period_id, 'q' => $row['npm']]) }}">Finalisasi</a>
                                            @else
                                                <a class="silat-secondary-link" href="{{ route('reports.final-scores', array_filter(request()->only(['scope', 'period_id', 'program_id', 'study_program_id']) + ['status' => $row['finalized_at'] ? 'finalized' : 'pending'])) }}">Nilai</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="silat-table-cell" colspan="7"><x-empty-state title="Tidak ada sanksi pada filter ini" icon="fa-scale-balanced" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
    @include('reports.partials.period-date-sync')
</x-app-layout>
