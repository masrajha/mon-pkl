<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Analisis & Laporan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">Risk Scoring Peserta</h2>
                <p class="mt-1 text-sm text-gray-500">Prioritaskan peserta yang perlu tindak lanjut berdasarkan presensi, catatan harian, laporan, seminar, nilai, dan sanksi.</p>
            </div>
            <a class="silat-secondary-link" href="{{ route('reports.progress-funnel', request()->only(['period_id', 'program_id', 'study_program_id'])) }}">
                <x-icon name="fa-chart-simple" /> Buka funnel
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('management.partials.nav')

            <form method="GET" action="{{ route('reports.risk-scoring') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="grid gap-4 md:grid-cols-5">
                    <div>
                        <x-input-label for="period_id" value="Periode Program" />
                        <select id="period_id" name="period_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">Semua periode</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected((string) $selectedPeriod === (string) $period->id)>{{ $period->display_name }}</option>
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
                        <x-input-label for="risk" value="Kategori Risiko" />
                        <select id="risk" name="risk" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">Semua kategori</option>
                            <option value="safe" @selected($selectedRisk === 'safe')>Aman</option>
                            <option value="watch" @selected($selectedRisk === 'watch')>Perlu Dipantau</option>
                            <option value="risky" @selected($selectedRisk === 'risky')>Berisiko</option>
                            <option value="critical" @selected($selectedRisk === 'critical')>Kritis</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button class="silat-btn w-full justify-center" type="submit"><x-icon name="fa-filter" /> Terapkan</button>
                    </div>
                </div>
            </form>

            <div class="grid gap-4 md:grid-cols-4">
                @foreach ([
                    ['key' => 'safe', 'label' => 'Aman', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
                    ['key' => 'watch', 'label' => 'Perlu Dipantau', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
                    ['key' => 'risky', 'label' => 'Berisiko', 'class' => 'border-orange-200 bg-orange-50 text-orange-700'],
                    ['key' => 'critical', 'label' => 'Kritis', 'class' => 'border-red-200 bg-red-50 text-red-700'],
                ] as $item)
                    <a href="{{ route('reports.risk-scoring', array_filter(request()->only(['period_id', 'program_id', 'study_program_id']) + ['risk' => $item['key']])) }}" class="rounded-lg border p-4 shadow-sm {{ $item['class'] }}">
                        <p class="text-xs font-semibold uppercase tracking-wide">{{ $item['label'] }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ number_format($summary[$item['key']] ?? 0, 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs">peserta</p>
                    </a>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Peserta Perlu Tindak Lanjut</h3>
                        <p class="silat-section-description">Default menampilkan peserta non-Aman. Klik kartu kategori untuk drill-down sesuai status risiko.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ number_format($rows->count(), 0, ',', '.') }}/{{ number_format($totalRows, 0, ',', '.') }} peserta</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="silat-table-heading">Mahasiswa</th>
                                <th class="silat-table-heading">Scope</th>
                                <th class="silat-table-heading">Dosen</th>
                                <th class="silat-table-heading">Risiko</th>
                                <th class="silat-table-heading">Masalah Utama</th>
                                <th class="silat-table-heading">Presensi</th>
                                <th class="silat-table-heading">Sanksi</th>
                                <th class="silat-table-heading text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($rows as $row)
                                @php($enrollment = $row['enrollment'])
                                <tr>
                                    <td class="silat-table-cell">
                                        <p class="font-semibold text-gray-900">{{ $enrollment->student?->full_name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $enrollment->student?->npm ?? '-' }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        <p class="text-sm text-gray-900">{{ $enrollment->studyProgram?->name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $enrollment->internshipPeriod?->display_name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $enrollment->internshipPlace?->name ?? '-' }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        <p class="text-sm font-medium text-gray-900">{{ $enrollment->lecturer?->name ?? $enrollment->lecturer_supervisor ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $enrollment->lecturer?->nip ?? $enrollment->lecturerSupervisor?->email ?? '' }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $row['category_tone'] }}">{{ $row['category'] }}</span>
                                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($row['score'], 0, ',', '.') }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        @if ($row['main_issues']->isEmpty())
                                            <span class="text-sm text-gray-500">Tidak ada indikator risiko aktif.</span>
                                        @else
                                            <div class="flex max-w-md flex-wrap gap-2">
                                                @foreach ($row['main_issues'] as $issue)
                                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $issue }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell">
                                        <p class="text-sm text-gray-900">{{ number_format($row['valid_attendance_days'], 0, ',', '.') }}/{{ number_format($row['expected_working_days'], 0, ',', '.') }} hari valid</p>
                                        <p class="text-xs text-gray-500">{{ number_format($row['incomplete_attendance_days'], 0, ',', '.') }} tidak lengkap, streak absen {{ number_format($row['absence_streak'], 0, ',', '.') }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        <span class="font-semibold text-gray-900">{{ number_format($row['total_sanctions'], 0, ',', '.') }}</span>
                                    </td>
                                    <td class="silat-table-cell text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a class="silat-secondary-link" href="{{ route('reports.monitoring', ['period_id' => $enrollment->internship_period_id, 'study_program_id' => $enrollment->study_program_id]) }}">Presensi</a>
                                            <a class="silat-secondary-link" href="{{ route('management.submission-progress.index', ['period_id' => $enrollment->internship_period_id, 'q' => $enrollment->student?->npm]) }}">Laporan</a>
                                            <a class="silat-secondary-link" href="{{ route('management.seminar-requests.index', ['period_id' => $enrollment->internship_period_id, 'q' => $enrollment->student?->npm]) }}">Seminar</a>
                                            <a class="silat-secondary-link" href="{{ route('management.forgotten-attendance-requests.index', ['period_id' => $enrollment->internship_period_id, 'q' => $enrollment->student?->npm]) }}">Lupa Presensi</a>
                                            <a class="silat-secondary-link" href="{{ route('management.final-assessments.index', ['period_id' => $enrollment->internship_period_id, 'q' => $enrollment->student?->npm]) }}">Finalisasi</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="silat-table-cell" colspan="8">
                                        <x-empty-state title="Belum ada peserta perlu tindak lanjut" description="Ubah filter atau pilih kategori Aman jika ingin melihat peserta tanpa indikator risiko aktif." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
