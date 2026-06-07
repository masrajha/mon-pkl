<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Analisis & Laporan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">Rekap Nilai Akhir</h2>
                <p class="mt-1 text-sm text-gray-500">Pantau nilai dosen, Pembimbing Lapangan, nilai dasar, pengurangan, total nilai, huruf mutu, dan nomor berita acara.</p>
            </div>
            <a class="silat-secondary-link" href="{{ route('reports.sanctions', request()->only(['period_id', 'program_id', 'study_program_id'])) }}">
                <x-icon name="fa-scale-balanced" /> Rekap sanksi
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('management.partials.nav')

            <form method="GET" action="{{ route('reports.final-scores') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
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
                        <x-input-label for="status" value="Status Nilai" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">Semua status</option>
                            <option value="finalized" @selected($selectedStatus === 'finalized')>Sudah final</option>
                            <option value="pending" @selected($selectedStatus === 'pending')>Belum final</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button class="silat-btn w-full justify-center" type="submit"><x-icon name="fa-filter" /> Terapkan</button>
                    </div>
                </div>
            </form>

            <section class="grid gap-4 md:grid-cols-4">
                <div class="silat-stat-card"><p class="silat-stat-label">Peserta</p><p class="silat-stat-value">{{ number_format($totals['students'], 0, ',', '.') }}</p></div>
                <div class="silat-stat-card"><p class="silat-stat-label">Sudah Final</p><p class="silat-stat-value">{{ number_format($totals['finalized'], 0, ',', '.') }}</p></div>
                <div class="silat-stat-card"><p class="silat-stat-label">Belum Final</p><p class="silat-stat-value">{{ number_format($totals['pending'], 0, ',', '.') }}</p></div>
                <div class="silat-stat-card"><p class="silat-stat-label">Rata-rata Nilai Final</p><p class="silat-stat-value">{{ number_format($totals['average_final_score'], 2, ',', '.') }}</p></div>
            </section>

            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Daftar Nilai Peserta</h3>
                        <p class="silat-section-description">Baris belum final tetap ditampilkan agar admin/koordinator mudah melihat komponen nilai yang belum lengkap.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Mahasiswa</th>
                                <th class="silat-table-cell">Pembimbing</th>
                                <th class="silat-table-cell text-right">Dosen</th>
                                <th class="silat-table-cell text-right">Lapangan</th>
                                <th class="silat-table-cell text-right">Dasar</th>
                                <th class="silat-table-cell text-right">Pengurangan</th>
                                <th class="silat-table-cell text-right">Total</th>
                                <th class="silat-table-cell">Huruf</th>
                                <th class="silat-table-cell">Berita Acara</th>
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
                                    <td class="silat-table-cell">
                                        <p>{{ $row['lecturer'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $row['place'] }}</p>
                                    </td>
                                    <td class="silat-table-cell text-right">{{ $row['lecturer_score'] === null ? '-' : number_format($row['lecturer_score'], 2, ',', '.') }}</td>
                                    <td class="silat-table-cell text-right">{{ $row['field_supervisor_score'] === null ? '-' : number_format($row['field_supervisor_score'], 2, ',', '.') }}</td>
                                    <td class="silat-table-cell text-right">{{ $row['base_score'] === null ? '-' : number_format($row['base_score'], 2, ',', '.') }}</td>
                                    <td class="silat-table-cell text-right">{{ $row['final_deduction'] === null ? '-' : number_format($row['final_deduction'], 2, ',', '.') }}</td>
                                    <td class="silat-table-cell text-right text-base font-semibold text-gray-950">{{ $row['final_score'] === null ? '-' : number_format($row['final_score'], 2, ',', '.') }}</td>
                                    <td class="silat-table-cell">
                                        <span class="inline-flex rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $row['letter_grade'] ?: '-' }}</span>
                                    </td>
                                    <td class="silat-table-cell">
                                        <p class="font-medium text-gray-900">{{ $row['document_number'] ?: '-' }}</p>
                                        <p class="text-xs {{ $row['is_final'] ? 'text-emerald-600' : 'text-amber-600' }}">{{ $row['is_final'] ? 'Sudah final' : 'Belum final' }}</p>
                                    </td>
                                    <td class="silat-table-cell text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a class="silat-secondary-link" href="{{ route('management.final-assessments.index', ['period_id' => $row['enrollment']->internship_period_id, 'q' => $row['npm']]) }}">Finalisasi</a>
                                            @if ($row['is_final'])
                                                <a class="silat-secondary-link" href="{{ route('reports.final-scores.print', $row['enrollment']) }}" target="_blank">Cetak</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="silat-table-cell" colspan="10"><x-empty-state title="Tidak ada data nilai pada filter ini" icon="fa-calculator" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
