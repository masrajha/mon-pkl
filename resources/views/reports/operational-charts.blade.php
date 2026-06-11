<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Analisis & Laporan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">Grafik Operasional</h2>
                <p class="mt-1 text-sm text-gray-500">Pantau tren presensi, status peserta, laporan, sanksi, dan progres nilai dalam satu layar.</p>
            </div>
            <a class="silat-secondary-link" href="{{ route('reports.risk-scoring', request()->only(['period_id', 'program_id', 'study_program_id'])) }}">
                <x-icon name="fa-triangle-exclamation" /> Buka risk scoring
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('management.partials.nav')
            @include('reports.partials.report-tabs')

            <form method="GET" action="{{ route('reports.operational-charts') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" data-period-date-sync>
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

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Peserta</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($totalEnrollments, 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-gray-500">Sesuai filter aktif.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tanggal Analisis</p>
                    <p class="mt-2 text-xl font-semibold text-gray-950">{{ \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                    <p class="mt-1 text-sm text-gray-500">Untuk tren presensi harian.</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nilai Final</p>
                    @php($finalProgress = collect($assessmentProgress)->firstWhere('label', 'Nilai Final'))
                    <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($finalProgress['done'] ?? 0, 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ number_format($finalProgress['percent'] ?? 0, 1, ',', '.') }}% peserta sudah final.</p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(22rem,0.75fr)]">
                <div class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Tren Presensi Harian</h3>
                            <p class="silat-section-description">Jumlah mahasiswa check-in, check-out, dan pasangan presensi valid per tanggal.</p>
                        </div>
                    </div>
                    @php($maxAttendance = max(1, collect($attendanceTrend)->flatMap(fn ($row) => [$row['check_in'], $row['check_out'], $row['valid_pairs']])->max()))
                    <div class="space-y-3 p-5">
                        @forelse ($attendanceTrend as $row)
                            <div class="grid gap-2 md:grid-cols-[5rem_minmax(0,1fr)_4rem] md:items-center">
                                <div class="text-xs font-semibold text-gray-600">{{ $row['label'] }}</div>
                                <div class="space-y-1.5">
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $row['check_in'] > 0 ? max(2, $row['check_in'] / $maxAttendance * 100) : 0 }}%;"></div></div>
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $row['check_out'] > 0 ? max(2, $row['check_out'] / $maxAttendance * 100) : 0 }}%;"></div></div>
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-indigo-500" style="width: {{ $row['valid_pairs'] > 0 ? max(2, $row['valid_pairs'] / $maxAttendance * 100) : 0 }}%;"></div></div>
                                </div>
                                <div class="text-right text-xs text-gray-500">{{ $row['valid_pairs'] }} valid</div>
                            </div>
                        @empty
                            <x-empty-state title="Belum ada tren presensi" icon="fa-chart-line" />
                        @endforelse
                        <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-3 text-xs text-gray-500">
                            <span><span class="inline-block h-2 w-5 rounded-full bg-blue-600"></span> Check-in</span>
                            <span><span class="inline-block h-2 w-5 rounded-full bg-emerald-500"></span> Check-out</span>
                            <span><span class="inline-block h-2 w-5 rounded-full bg-indigo-500"></span> Valid pasangan</span>
                        </div>
                    </div>
                </div>

                <div class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Status Laporan</h3>
                            <p class="silat-section-description">Distribusi status laporan lengkap.</p>
                        </div>
                    </div>
                    <div class="grid gap-5 p-5 sm:grid-cols-[12rem_minmax(0,1fr)] sm:items-center">
                        <div class="mx-auto h-44 w-44 rounded-full" style="background: conic-gradient({{ $reportGradient }});"></div>
                        <div class="space-y-2">
                            @foreach ($reportStatusLabels as $key => $label)
                                <div class="flex items-center justify-between gap-3 rounded-md border border-gray-100 px-3 py-2 text-sm">
                                    <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background: {{ $reportColors[$key] ?? '#64748b' }}"></span>{{ $label }}</span>
                                    <strong>{{ number_format($reportStatus[$key] ?? 0, 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Status Peserta per Prodi</h3>
                            <p class="silat-section-description">Stacked bar status enrollment pada setiap prodi.</p>
                        </div>
                    </div>
                    @php($statusColors = ['active' => 'bg-blue-600', 'completed' => 'bg-emerald-600'])
                    <div class="space-y-4 p-5">
                        @forelse ($statusByStudyProgram as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                    <span class="font-semibold text-gray-900">{{ $row['name'] }}</span>
                                    <span class="text-gray-500">{{ $row['total'] }} peserta</span>
                                </div>
                                <div class="flex h-5 overflow-hidden rounded-full bg-gray-100">
                                    @foreach ($statusKeys as $status)
                                        @php($count = $row['statuses'][$status] ?? 0)
                                        @if ($count > 0)
                                            <div class="{{ $statusColors[$status] ?? 'bg-slate-400' }}" title="{{ $statusLabels[$status] }}: {{ $count }}" style="width: {{ max(3, $count / max(1, $row['total']) * 100) }}%;"></div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <x-empty-state title="Belum ada peserta" icon="fa-chart-simple" />
                        @endforelse
                    </div>
                </div>

                <div class="silat-card overflow-hidden">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Top Sanksi</h3>
                            <p class="silat-section-description">Mahasiswa dengan total poin sanksi tertinggi.</p>
                        </div>
                    </div>
                    @php($maxSanction = max(1, collect($topSanctions)->max('points') ?: 1))
                    <div class="space-y-3 p-5">
                        @forelse ($topSanctions as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                    <span class="min-w-0 truncate font-semibold text-gray-900">{{ $row['student'] }} <span class="font-normal text-gray-500">({{ $row['npm'] }})</span></span>
                                    <span class="shrink-0 text-gray-700">{{ $row['points'] }} poin</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-rose-600" style="width: {{ max(4, $row['points'] / $maxSanction * 100) }}%;"></div></div>
                            </div>
                        @empty
                            <x-empty-state title="Tidak ada sanksi aktif" icon="fa-circle-check" />
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="silat-card overflow-hidden">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Progress Status Nilai</h3>
                        <p class="silat-section-description">Perbandingan nilai yang sudah masuk dan yang masih kosong.</p>
                    </div>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-3">
                    @foreach ($assessmentProgress as $row)
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $row['label'] }}</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-950">{{ number_format($row['done'], 0, ',', '.') }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($row['percent'], 1, ',', '.') }}% selesai, {{ $row['missing'] }} belum.</p>
                                </div>
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ number_format($row['percent'], 1, ',', '.') }}%</span>
                            </div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ max(3, $row['percent']) }}%;"></div></div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
    @include('reports.partials.period-date-sync')
</x-app-layout>
