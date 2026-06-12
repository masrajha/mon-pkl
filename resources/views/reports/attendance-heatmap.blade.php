<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Analisis & Laporan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">Heatmap Kehadiran</h2>
                <p class="mt-1 text-sm text-gray-500">Pantau pola kehadiran per mahasiswa dan tanggal dalam satu tampilan padat.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @include('reports.partials.export-buttons', ['type' => 'attendance-heatmap'])
                <a class="silat-secondary-link" href="{{ route('reports.risk-scoring', request()->only(['period_id', 'program_id', 'study_program_id'])) }}">
                    <x-icon name="fa-triangle-exclamation" /> Buka risk scoring
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('management.partials.nav')
            @include('reports.partials.report-tabs')

            <form method="GET" action="{{ route('reports.attendance-heatmap') }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm" data-period-date-sync>
                <div class="grid gap-4 md:grid-cols-6">
                    <div>
                        <x-input-label for="period_id" value="Periode Program" />
                        <select id="period_id" name="period_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">Periode aktif/default</option>
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

            <div class="grid gap-3 md:grid-cols-3 lg:grid-cols-6">
                @foreach ($legend as $status => $item)
                    <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                        <div class="flex items-center gap-2">
                            <span class="h-4 w-4 rounded-sm ring-1 {{ $item['class'] }}"></span>
                            <span class="text-xs font-semibold text-gray-700">{{ $item['label'] }}</span>
                        </div>
                        <p class="mt-2 text-xl font-semibold text-gray-900">{{ number_format($summary[$status] ?? 0, 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Matriks Kehadiran</h3>
                        <p class="silat-section-description">{{ count($dates) }} tanggal, {{ number_format($rows->count(), 0, ',', '.') }} peserta. Geser horizontal untuk melihat seluruh rentang.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th class="sticky left-0 z-20 w-72 border-b border-gray-200 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Mahasiswa</th>
                                @foreach ($dates as $date)
                                    <th class="border-b border-gray-200 bg-gray-50 px-1 py-2 text-center text-[11px] font-semibold text-gray-500">
                                        <span class="block">{{ $date->format('d') }}</span>
                                        <span class="block">{{ $date->isoFormat('ddd') }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php($enrollment = $row['enrollment'])
                                <tr>
                                    <td class="sticky left-0 z-10 border-b border-gray-100 bg-white px-4 py-3">
                                        <p class="text-sm font-semibold text-gray-900">{{ $enrollment->student?->full_name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $enrollment->student?->npm ?? '-' }} · {{ $enrollment->studyProgram?->name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($row['valid_days'], 0, ',', '.') }} hari valid, {{ number_format($row['problem_days'], 0, ',', '.') }} bermasalah</p>
                                    </td>
                                    @foreach ($row['cells'] as $cell)
                                        <td class="border-b border-gray-100 px-1 py-2 text-center">
                                            <span
                                                class="inline-flex h-6 w-6 items-center justify-center rounded-sm text-[10px] font-semibold ring-1 {{ $cell['class'] }}"
                                                title="{{ $cell['date'] }} - {{ $cell['label'] }}{{ $cell['check_in'] ? ' | Masuk '.$cell['check_in'] : '' }}{{ $cell['check_out'] ? ' | Pulang '.$cell['check_out'] : '' }}"
                                            >
                                                {{ $cell['status'] === 'present' ? 'H' : ($cell['status'] === 'forgotten_approved' ? 'L' : ($cell['status'] === 'incomplete' ? '!' : ($cell['status'] === 'absent' ? 'A' : '')) ) }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-8" colspan="{{ count($dates) + 1 }}">
                                        <x-empty-state title="Belum ada peserta" description="Ubah filter periode/prodi atau rentang tanggal." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('reports.partials.period-date-sync')
</x-app-layout>
