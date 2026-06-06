@php
    $filters = $participantProgress['filters'];
    $options = $participantProgress['options'];
    $cards = $participantProgress['cards'];
    $cardItems = [
        ['label' => 'Total Peserta', 'value' => $cards['total'], 'icon' => 'fa-users', 'tone' => 'bg-blue-600'],
        ['label' => 'Aktif', 'value' => $cards['active'], 'icon' => 'fa-person-running', 'tone' => 'bg-emerald-600'],
        ['label' => 'Selesai', 'value' => $cards['completed'], 'icon' => 'fa-circle-check', 'tone' => 'bg-slate-700'],
        ['label' => 'Presensi Belum Lengkap', 'value' => $cards['incomplete_attendance'], 'icon' => 'fa-calendar-xmark', 'tone' => 'bg-amber-500'],
        ['label' => 'Catatan Harian Belum Divalidasi', 'value' => $cards['pending_daily_validation'], 'icon' => 'fa-clipboard-check', 'tone' => 'bg-orange-500'],
        ['label' => 'Laporan Terlambat', 'value' => $cards['late_reports'], 'icon' => 'fa-file-circle-exclamation', 'tone' => 'bg-red-600'],
        ['label' => 'Seminar Belum Diajukan', 'value' => $cards['seminar_missing'], 'icon' => 'fa-person-chalkboard', 'tone' => 'bg-indigo-600'],
        ['label' => 'Nilai Belum Lengkap', 'value' => $cards['incomplete_scores'], 'icon' => 'fa-star-half-stroke', 'tone' => 'bg-purple-600'],
        ['label' => 'Nilai Final', 'value' => $cards['final_scores'], 'icon' => 'fa-award', 'tone' => 'bg-cyan-700'],
        ['label' => 'Sanksi Tertinggi', 'value' => $cards['highest_sanction'], 'icon' => 'fa-gavel', 'tone' => 'bg-rose-700'],
    ];
@endphp

<section class="silat-card overflow-hidden">
    <div class="silat-section-header">
        <div>
            <h3 class="silat-section-title">Dashboard Progres Peserta Kegiatan</h3>
            <p class="silat-section-description">Filter peserta dan pantau indikator utama pelaksanaan program.</p>
        </div>
        <a class="silat-secondary-link" href="{{ route('reports.progress-funnel', ['period_id' => $filters['period_id'], 'program_id' => $filters['program_id'], 'study_program_id' => $filters['study_program_id']]) }}">Buka funnel</a>
    </div>

    <form method="GET" class="grid gap-4 border-b border-gray-100 px-5 py-4 md:grid-cols-2 xl:grid-cols-4">
        <div>
            <x-input-label for="progress_period_id" value="Periode" />
            <x-select-input id="progress_period_id" name="progress_period_id" class="mt-1 text-sm">
                <option value="">Semua tersedia</option>
                @foreach ($options['periods'] as $period)
                    <option value="{{ $period->id }}" @selected((string) $filters['period_id'] === (string) $period->id)>{{ $period->display_name }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="progress_program_id" value="Program" />
            <x-select-input id="progress_program_id" name="progress_program_id" class="mt-1 text-sm">
                <option value="">Semua program</option>
                @foreach ($options['programs'] as $program)
                    <option value="{{ $program->id }}" @selected((string) $filters['program_id'] === (string) $program->id)>{{ $program->name }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="progress_study_program_id" value="Prodi" />
            <x-select-input id="progress_study_program_id" name="progress_study_program_id" class="mt-1 text-sm">
                <option value="">Semua prodi</option>
                @foreach ($options['studyPrograms'] as $studyProgram)
                    <option value="{{ $studyProgram->id }}" @selected((string) $filters['study_program_id'] === (string) $studyProgram->id)>{{ $studyProgram->name }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="progress_status" value="Status Peserta" />
            <x-select-input id="progress_status" name="progress_status" class="mt-1 text-sm">
                <option value="">Semua status</option>
                @foreach ($options['statuses'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="progress_place_id" value="Mitra" />
            <x-select-input id="progress_place_id" name="progress_place_id" class="mt-1 text-sm">
                <option value="">Semua mitra</option>
                @foreach ($options['places'] as $place)
                    <option value="{{ $place->id }}" @selected((string) $filters['place_id'] === (string) $place->id)>{{ $place->name }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="progress_lecturer_id" value="Dosen Pembimbing" />
            <x-select-input id="progress_lecturer_id" name="progress_lecturer_id" class="mt-1 text-sm">
                <option value="">Semua dosen</option>
                @foreach ($options['lecturers'] as $lecturer)
                    <option value="{{ $lecturer->id }}" @selected((string) $filters['lecturer_id'] === (string) $lecturer->id)>{{ $lecturer->name }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="progress_start_date" value="Dari" />
            <x-text-input id="progress_start_date" name="progress_start_date" type="date" class="mt-1 block w-full text-sm" :value="$filters['start_date']" />
        </div>
        <div>
            <x-input-label for="progress_end_date" value="Sampai" />
            <div class="mt-1 flex gap-2">
                <x-text-input id="progress_end_date" name="progress_end_date" type="date" class="block w-full text-sm" :value="$filters['end_date']" />
                <x-primary-button><x-icon name="fa-filter" /> Filter</x-primary-button>
            </div>
        </div>
    </form>

    <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($cardItems as $item)
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $item['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold text-gray-950">{{ number_format((float) $item['value'], 0, ',', '.') }}</p>
                    </div>
                    <div class="{{ $item['tone'] }} flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white">
                        <x-icon :name="$item['icon']" class="w-4" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 border-t border-gray-100 p-5 xl:grid-cols-[minmax(0,1fr)_minmax(22rem,0.7fr)]">
        <div>
            <h4 class="text-sm font-semibold text-gray-950">Komposisi Status Peserta</h4>
            <div class="mt-3 space-y-3">
                @forelse ($participantProgress['statusBreakdown'] as $row)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-700">{{ Str::headline($row['label']) }}</span>
                            <span class="text-gray-500">{{ number_format($row['count'], 0, ',', '.') }} peserta · {{ number_format($row['percent'], 1, ',', '.') }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-blue-600" style="width: {{ max(2, $row['percent']) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <x-empty-state title="Belum ada peserta pada filter ini" icon="fa-chart-simple" />
                @endforelse
            </div>
        </div>
        <div>
            <h4 class="text-sm font-semibold text-gray-950">Sanksi Tertinggi</h4>
            <div class="mt-3 divide-y divide-gray-100 rounded-lg border border-gray-200">
                @forelse ($participantProgress['highestSanctions'] as $enrollment)
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $enrollment->student?->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ $enrollment->studyProgram?->name }} · {{ $enrollment->internshipPeriod?->display_name }}</p>
                        </div>
                        <x-badge variant="danger">{{ number_format($enrollment->total_sanctions_points, 0, ',', '.') }}</x-badge>
                    </div>
                @empty
                    <div class="p-4"><x-empty-state title="Tidak ada sanksi" icon="fa-circle-check" /></div>
                @endforelse
            </div>
        </div>
    </div>
</section>
