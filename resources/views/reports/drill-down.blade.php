<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Analisis & Laporan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ $context['title'] }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $context['description'] }}</p>
            </div>
            <a class="silat-secondary-link" href="{{ route('reports.operational-charts', request()->only(['scope', 'period_id', 'program_id', 'study_program_id', 'start_date', 'end_date'])) }}">
                <x-icon name="fa-chart-pie" /> Kembali ke grafik
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('management.partials.nav')
            @include('reports.partials.report-tabs')

            <form method="GET" action="{{ route('reports.drill-down') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" data-period-date-sync>
                @foreach (request()->except(['period_id', 'program_id', 'study_program_id', 'start_date', 'end_date']) as $key => $value)
                    @if (! is_array($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div class="grid gap-4 md:grid-cols-6">
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
                        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="$startDate" />
                    </div>
                    <div>
                        <x-input-label for="end_date" value="Sampai" />
                        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="$endDate" />
                    </div>
                    <div class="flex items-end">
                        <button class="silat-btn w-full justify-center" type="submit"><x-icon name="fa-filter" /> Terapkan</button>
                    </div>
                </div>
            </form>

            <section class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Peserta</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($rows->count(), 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-gray-500">Sesuai titik data yang dipilih.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Sanksi</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($rows->sum('total_sanctions'), 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-gray-500">Akumulasi pada peserta tampil.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nilai Final</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($rows->where('is_final', true)->count(), 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-gray-500">Sudah difinalisasi.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tanggal Analisis</p>
                    <p class="mt-2 text-lg font-semibold text-gray-950">{{ \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                    <p class="mt-1 text-sm text-gray-500">Rentang filter aktif.</p>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Daftar Peserta Terkait</h3>
                        <p class="silat-section-description">Gunakan aksi cepat untuk membuka modul tindak lanjut sesuai masalah utama peserta.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ number_format($rows->count(), 0, ',', '.') }} peserta</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="silat-table-heading">Mahasiswa</th>
                                <th class="silat-table-heading">Program</th>
                                <th class="silat-table-heading">Risiko</th>
                                <th class="silat-table-heading">Presensi & Sanksi</th>
                                <th class="silat-table-heading">Nilai</th>
                                <th class="silat-table-heading text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($rows as $row)
                                @php($enrollment = $row['enrollment'])
                                <tr>
                                    <td class="silat-table-cell">
                                        <p class="font-semibold text-gray-900">{{ $row['student'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $row['npm'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $row['study_program'] }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        <p class="text-sm font-medium text-gray-900">{{ $row['program'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $row['period'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $row['place'] }}</p>
                                        <span class="mt-2 inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ ucfirst($row['status']) }}</span>
                                    </td>
                                    <td class="silat-table-cell">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $row['risk_tone'] }}">{{ $row['risk_category'] }}</span>
                                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($row['risk_score'], 0, ',', '.') }}</p>
                                        @if ($row['main_issues']->isNotEmpty())
                                            <div class="mt-2 flex max-w-xs flex-wrap gap-1.5">
                                                @foreach ($row['main_issues'] as $issue)
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $issue }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell">
                                        <p class="text-sm text-gray-900">{{ number_format($row['valid_attendance_days'], 0, ',', '.') }} hari valid</p>
                                        <p class="text-xs text-gray-500">{{ number_format($row['incomplete_attendance_days'], 0, ',', '.') }} hari tidak lengkap</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ number_format($row['total_sanctions'], 0, ',', '.') }} poin</p>
                                        <p class="text-xs text-gray-500">Laporan: {{ str_replace('_', ' ', $row['full_report_status']) }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        <p class="text-xs text-gray-500">Dosen</p>
                                        <p class="text-sm font-semibold text-gray-900">{{ $row['lecturer_score'] !== null ? number_format($row['lecturer_score'], 2, ',', '.') : '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">Pembimbing Lapangan</p>
                                        <p class="text-sm font-semibold text-gray-900">{{ $row['field_supervisor_score'] !== null ? number_format($row['field_supervisor_score'], 2, ',', '.') : '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">Final</p>
                                        <p class="text-sm font-semibold text-gray-900">{{ $row['final_score'] !== null ? number_format($row['final_score'], 2, ',', '.') : '-' }} {{ $row['letter_grade'] ? '('.$row['letter_grade'].')' : '' }}</p>
                                    </td>
                                    <td class="silat-table-cell text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a class="silat-secondary-link" href="{{ route('reports.monitoring', array_filter(['scope' => request('scope'), 'period_id' => $enrollment->internship_period_id, 'study_program_id' => $enrollment->study_program_id])) }}">Presensi</a>
                                            @if ($canReviewReports)
                                                <a class="silat-secondary-link" href="{{ route('management.submission-progress.index', ['period_id' => $enrollment->internship_period_id, 'q' => $enrollment->student?->npm]) }}">Laporan</a>
                                            @endif
                                            @if ($canReviewSeminars)
                                                <a class="silat-secondary-link" href="{{ route('management.seminar-requests.index', ['period_id' => $enrollment->internship_period_id, 'q' => $enrollment->student?->npm]) }}">Seminar</a>
                                            @endif
                                            @if ($canManageForgottenAttendance)
                                                <a class="silat-secondary-link" href="{{ route('management.forgotten-attendance-requests.index', ['period_id' => $enrollment->internship_period_id, 'q' => $enrollment->student?->npm]) }}">Lupa Presensi</a>
                                            @endif
                                            @if ($canFinalizeScores)
                                                <a class="silat-secondary-link" href="{{ route('management.final-assessments.index', ['period_id' => $enrollment->internship_period_id, 'q' => $enrollment->student?->npm]) }}">Finalisasi</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="silat-table-cell" colspan="6">
                                        <x-empty-state title="Tidak ada peserta pada titik data ini" description="Ubah filter atau pilih titik data lain pada grafik." icon="fa-magnifying-glass-chart" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    @include('reports.partials.period-date-sync')
</x-app-layout>
