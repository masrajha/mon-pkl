<x-app-layout>
    <x-slot name="header">
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Analisis & Laporan</p>
                    <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Rekap Progres Laporan') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Statistik pelaporan mahasiswa dari Proposal Rencana Kerja sampai Tahap 4/Laporan Lengkap.</p>
                </div>
                @include('reports.partials.export-buttons', ['type' => 'submission-progress'])
            </div>

            <form method="GET" class="silat-card grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-6">
                @if (request()->filled('scope'))<input type="hidden" name="scope" value="{{ request('scope') }}">@endif
                <div>
                    <x-input-label for="period_id" value="Periode Program" />
                    <x-select-input id="period_id" name="period_id" class="mt-1 text-sm">
                        <option value="">Semua periode</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected((string) $selectedPeriod === (string) $period->id)>{{ $period->display_name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="program_id" value="Program" />
                    <x-select-input id="program_id" name="program_id" class="mt-1 text-sm">
                        <option value="">Semua program</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) $selectedProgram === (string) $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="study_program_id" value="Prodi" />
                    <x-select-input id="study_program_id" name="study_program_id" class="mt-1 text-sm">
                        <option value="">Semua prodi</option>
                        @foreach ($studyPrograms as $studyProgram)
                            <option value="{{ $studyProgram->id }}" @selected((string) $selectedStudyProgram === (string) $studyProgram->id)>{{ $studyProgram->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="deadline_type" value="Tahap" />
                    <x-select-input id="deadline_type" name="deadline_type" class="mt-1 text-sm">
                        <option value="">Semua tahap</option>
                        @foreach ($reportTypes as $key => $label)
                            <option value="{{ $key }}" @selected($selectedDeadlineType === $key)>{{ $label }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="progress_status" value="Status Pelaporan" />
                    <x-select-input id="progress_status" name="progress_status" class="mt-1 text-sm">
                        <option value="">Semua status</option>
                        @foreach ($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected($selectedProgressStatus === $key)>{{ $label }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div class="flex items-end">
                    <x-primary-button><x-icon name="fa-filter" /> Terapkan</x-primary-button>
                </div>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            @include('reports.partials.report-tabs')

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Mahasiswa</p>
                    <p class="silat-stat-value">{{ number_format($totals['students'], 0, ',', '.') }}</p>
                </div>
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Tahap 4 Disetujui</p>
                    <p class="silat-stat-value">{{ number_format($totals['full_report_approved'], 0, ',', '.') }}</p>
                </div>
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Pending Review</p>
                    <p class="silat-stat-value">{{ number_format($totals['pending'], 0, ',', '.') }}</p>
                </div>
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Perlu Revisi</p>
                    <p class="silat-stat-value">{{ number_format($totals['revision_required'], 0, ',', '.') }}</p>
                </div>
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Ditolak</p>
                    <p class="silat-stat-value">{{ number_format($totals['rejected'], 0, ',', '.') }}</p>
                </div>
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Sanksi Laporan</p>
                    <p class="silat-stat-value">{{ number_format($totals['sanctions'], 0, ',', '.') }}</p>
                </div>
            </div>

            <section class="silat-card overflow-hidden">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Statistik Status per Tahap</h3>
                        <p class="silat-section-description">Komposisi status untuk Proposal, Tahap 1, Tahap 2, Tahap 3, dan Tahap 4.</p>
                    </div>
                </div>
                <div class="space-y-5 p-6">
                    @foreach ($stageStats as $stage)
                        @php
                            $stageTotal = max(1, array_sum($stage['statuses']));
                        @endphp
                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-950">{{ $stage['label'] }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($stage['approved_percent'], 1, ',', '.') }}% sudah disetujui</p>
                                </div>
                                <div class="text-sm font-semibold text-gray-700">{{ number_format(array_sum($stage['statuses']), 0, ',', '.') }} data</div>
                            </div>
                            <div class="mt-4 flex h-4 overflow-hidden rounded-full bg-gray-100">
                                @foreach ($statusLabels as $status => $label)
                                    @php
                                        $value = $stage['statuses'][$status] ?? 0;
                                        $width = $stageTotal > 0 ? ($value / $stageTotal * 100) : 0;
                                    @endphp
                                    @if ($value > 0)
                                        <div style="width: {{ $width }}%; background-color: {{ $statusMeta[$status]['color'] }};" title="{{ $label }}: {{ $value }}"></div>
                                    @endif
                                @endforeach
                            </div>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                                @foreach ($statusLabels as $status => $label)
                                    <a href="{{ route('reports.submission-progress', array_filter(request()->only(['scope', 'period_id', 'program_id', 'study_program_id']) + ['deadline_type' => $stage['key'], 'progress_status' => $status], fn ($value) => filled($value))) }}" class="flex items-center justify-between gap-2 rounded-md border border-gray-100 px-3 py-2 text-xs hover:border-blue-200 hover:bg-blue-50">
                                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $statusMeta[$status]['color'] }};"></span>{{ $label }}</span>
                                        <strong>{{ number_format($stage['statuses'][$status] ?? 0, 0, ',', '.') }}</strong>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="silat-card overflow-hidden">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Funnel Persetujuan Laporan</h3>
                        <p class="silat-section-description">Mengukur mahasiswa yang dokumennya sudah disetujui pada tiap tahap.</p>
                    </div>
                </div>
                <div class="grid gap-3 p-6 md:grid-cols-2">
                    @foreach ($stageStats as $stage)
                        <div class="rounded-lg border border-gray-200 p-4">
                            <p class="text-sm font-semibold text-gray-950">{{ $stage['label'] }}</p>
                            <p class="mt-3 text-2xl font-bold text-gray-950">{{ number_format($stage['approved'], 0, ',', '.') }}</p>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ max(2, $stage['approved_percent']) }}%;"></div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">{{ number_format($stage['approved_percent'], 1, ',', '.') }}% disetujui</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="silat-card overflow-hidden">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Detail Progres per Mahasiswa</h3>
                        <p class="silat-section-description">Gunakan tabel ini untuk melihat mahasiswa yang tertahan pada tahap tertentu.</p>
                    </div>
                </div>
                <div class="silat-table-wrap">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Mahasiswa</th>
                                <th class="silat-table-cell">Program</th>
                                <th class="silat-table-cell">Progres</th>
                                @foreach ($reportTypes as $label)
                                    <th class="silat-table-cell">{{ $label }}</th>
                                @endforeach
                                <th class="silat-table-cell">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="silat-table-cell">
                                        <div class="font-semibold text-gray-900">{{ $row['student'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $row['npm'] }} · {{ $row['study_program'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $row['place'] }}</div>
                                    </td>
                                    <td class="silat-table-cell">
                                        <div>{{ $row['period'] }}</div>
                                        <div class="text-xs text-gray-500">Dosen: {{ $row['lecturer'] }}</div>
                                    </td>
                                    <td class="silat-table-cell">
                                        <div class="font-semibold text-gray-950">{{ number_format($row['progress_percent'], 1, ',', '.') }}%</div>
                                        <div class="mt-1 h-2 w-28 overflow-hidden rounded-full bg-gray-100">
                                            <div class="h-full rounded-full bg-blue-600" style="width: {{ max(2, $row['progress_percent']) }}%;"></div>
                                        </div>
                                        @if ($row['report_sanctions'] > 0)
                                            <div class="mt-1 text-xs text-rose-600">{{ number_format($row['report_sanctions'], 0, ',', '.') }} poin sanksi</div>
                                        @endif
                                    </td>
                                    @foreach ($row['stages'] as $stage)
                                        <td class="silat-table-cell min-w-40">
                                            <x-badge :variant="$stage['status_variant']">{{ $stage['status_label'] }}</x-badge>
                                            @if ($stage['uploaded_at'])
                                                <div class="mt-1 text-xs text-gray-500">Upload: {{ $stage['uploaded_at']->format('d/m/Y H:i') }}</div>
                                            @endif
                                            @if ($stage['reviewed_at'])
                                                <div class="mt-1 text-xs text-gray-500">Review: {{ $stage['reviewed_at']->format('d/m/Y H:i') }}</div>
                                            @endif
                                            @if ($stage['sanction_points'] > 0)
                                                <div class="mt-1 text-xs text-rose-600">{{ $stage['sanction_points'] }} poin</div>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="silat-table-cell">
                                        @if ($canReviewReports)
                                            <a class="silat-secondary-link" href="{{ route('management.submission-progress.index', ['q' => $row['npm']]) }}">Review</a>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 4 + count($reportTypes) }}" class="silat-table-cell">
                                        <x-empty-state title="Belum ada data pada filter ini" icon="fa-file-lines" />
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
