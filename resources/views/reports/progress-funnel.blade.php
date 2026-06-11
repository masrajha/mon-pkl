<x-app-layout>
    <x-slot name="header">
        <div class="space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Analisis & Laporan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Progress Funnel Pelaksanaan') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Pantau alur peserta dari pendaftaran disetujui sampai nilai final untuk menemukan bottleneck proses.</p>
            </div>

            <form method="GET" class="silat-card grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <x-input-label for="period_id" :value="__('Periode Program')" />
                    <x-select-input id="period_id" name="period_id" class="mt-1 text-sm">
                        <option value="">{{ __('Semua') }}</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected((string) $selectedPeriod === (string) $period->id)>{{ $period->display_name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="program_id" :value="__('Program')" />
                    <x-select-input id="program_id" name="program_id" class="mt-1 text-sm">
                        <option value="">{{ __('Semua') }}</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) $selectedProgram === (string) $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="study_program_id" :value="__('Prodi')" />
                    <x-select-input id="study_program_id" name="study_program_id" class="mt-1 text-sm">
                        <option value="">{{ __('Semua') }}</option>
                        @foreach ($studyPrograms as $studyProgram)
                            <option value="{{ $studyProgram->id }}" @selected((string) $selectedStudyProgram === (string) $studyProgram->id)>{{ $studyProgram->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div class="flex items-end">
                    <x-primary-button><x-icon name="fa-filter" /> Terapkan Filter</x-primary-button>
                </div>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            @include('reports.partials.report-tabs')

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Peserta Disetujui</p>
                    <p class="silat-stat-value">{{ number_format($total, 0, ',', '.') }}</p>
                    <p class="silat-stat-note">Basis funnel: enrollment aktif atau selesai.</p>
                </div>
                <div class="silat-stat-card lg:col-span-2">
                    <p class="silat-stat-label">Bottleneck Terbesar</p>
                    @if ($bottleneck && $bottleneck['drop_from_previous'] > 0)
                        <p class="mt-2 text-xl font-semibold text-gray-950">{{ $bottleneck['label'] }}</p>
                        <p class="silat-stat-note">{{ number_format($bottleneck['drop_from_previous'], 0, ',', '.') }} peserta belum mencapai tahap ini dari tahap sebelumnya.</p>
                    @else
                        <p class="mt-2 text-xl font-semibold text-gray-950">Belum terlihat bottleneck</p>
                        <p class="silat-stat-note">Semua tahap memiliki konversi penuh atau data belum tersedia.</p>
                    @endif
                </div>
            </div>

            <section class="silat-card overflow-hidden">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Funnel Progres Peserta</h3>
                        <p class="silat-section-description">Persentase dihitung dari total peserta yang pendaftarannya sudah disetujui.</p>
                    </div>
                </div>

                <div class="grid gap-3 p-6 lg:grid-cols-2">
                    @forelse ($stages as $index => $stage)
                        @php
                            $barWidth = max(4, (float) $stage['percent_of_total']);
                        @endphp
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-950">{{ $index + 1 }}. {{ $stage['label'] }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $stage['description'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-bold text-gray-950">{{ number_format($stage['count'], 0, ',', '.') }}</p>
                                    <p class="text-xs font-medium text-gray-500">{{ number_format($stage['percent_of_total'], 1, ',', '.') }}% dari total</p>
                                </div>
                            </div>
                            <div class="mt-4 h-3 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ $barWidth }}%;"></div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                <span>Konversi tahap sebelumnya: <strong class="text-gray-800">{{ number_format($stage['conversion_from_previous'], 1, ',', '.') }}%</strong></span>
                                @if ($index > 0)
                                    <span>Drop: <strong class="{{ $stage['drop_from_previous'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ number_format($stage['drop_from_previous'], 0, ',', '.') }}</strong></span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="lg:col-span-2">
                            <x-empty-state title="Belum ada data funnel" icon="fa-chart-simple" />
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="silat-card overflow-hidden">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Breakdown per Prodi</h3>
                        <p class="silat-section-description">Gunakan tabel ini untuk melihat prodi mana yang tertahan pada tahap tertentu.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Prodi</th>
                                <th class="silat-table-cell">Disetujui</th>
                                <th class="silat-table-cell">Presensi</th>
                                <th class="silat-table-cell">Laporan</th>
                                <th class="silat-table-cell">Nilai Pembimbing Lapangan</th>
                                <th class="silat-table-cell">Seminar</th>
                                <th class="silat-table-cell">Nilai Dosen</th>
                                <th class="silat-table-cell">Final</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white text-gray-700">
                            @forelse ($breakdownRows as $row)
                                <tr>
                                    <td class="silat-table-cell font-medium text-gray-900">{{ $row['name'] }}</td>
                                    <td class="silat-table-cell">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                    <td class="silat-table-cell">{{ number_format($row['active_attendance'], 0, ',', '.') }}</td>
                                    <td class="silat-table-cell">{{ number_format($row['full_report'], 0, ',', '.') }}</td>
                                    <td class="silat-table-cell">{{ number_format($row['field_supervisor_score'], 0, ',', '.') }}</td>
                                    <td class="silat-table-cell">{{ number_format($row['seminar'], 0, ',', '.') }}</td>
                                    <td class="silat-table-cell">{{ number_format($row['lecturer_score'], 0, ',', '.') }}</td>
                                    <td class="silat-table-cell">{{ number_format($row['final_score'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="silat-table-cell">
                                        <x-empty-state title="Belum ada data pada filter ini" icon="fa-chart-simple" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
