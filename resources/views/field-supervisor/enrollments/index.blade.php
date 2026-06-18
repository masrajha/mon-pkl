<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Pembimbing Lapangan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">Mahasiswa Bimbingan</h2>
                <p class="mt-1 text-sm text-gray-500">Pilih mahasiswa untuk validasi catatan harian atau pengisian nilai.</p>
            </div>
            <a href="{{ route('field-supervisor.index') }}" class="silat-btn-secondary">
                <x-icon name="fa-gauge-high" />
                Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            <section class="silat-card p-5">
                <form method="GET" action="{{ route('field-supervisor.enrollments.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap gap-2">
                        @foreach ([
                            'active' => 'Periode Aktif',
                            'completed' => 'Periode Selesai',
                            'all' => 'Semua',
                        ] as $value => $label)
                            <a
                                href="{{ route('field-supervisor.enrollments.index', ['period' => $value, 'q' => $search]) }}"
                                class="inline-flex items-center gap-2 rounded-md border px-3.5 py-2.5 text-sm font-semibold shadow-sm transition {{ $period === $value ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"
                            >
                                <x-icon :name="$value === 'completed' ? 'fa-circle-check' : ($value === 'all' ? 'fa-layer-group' : 'fa-calendar-day')" />
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                    <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                        <input type="hidden" name="period" value="{{ $period }}">
                        <x-text-input name="q" value="{{ $search }}" class="w-full sm:w-72" placeholder="Cari nama, NPM, periode, mitra" />
                        <button class="silat-btn" type="submit">
                            <x-icon name="fa-magnifying-glass" />
                            Cari
                        </button>
                    </div>
                </form>
            </section>

            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Daftar Mahasiswa</h3>
                        <p class="silat-section-description">{{ number_format($summaries->count(), 0, ',', '.') }} data ditampilkan.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Mahasiswa</th>
                                <th class="silat-table-cell">Program / Periode</th>
                                <th class="silat-table-cell">Mitra</th>
                                <th class="silat-table-cell">Presensi</th>
                                <th class="silat-table-cell">Validasi</th>
                                <th class="silat-table-cell">Penilaian</th>
                                <th class="silat-table-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($summaries as $summary)
                                @php($enrollment = $summary['enrollment'])
                                <tr>
                                    <td class="silat-table-cell min-w-[220px]">
                                        <p class="font-semibold text-gray-900">{{ $summary['student_name'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $summary['student_npm'] }} · {{ $summary['study_program'] }}</p>
                                    </td>
                                    <td class="silat-table-cell min-w-[220px]">
                                        <p class="font-medium text-gray-900">{{ $summary['period_name'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $summary['attendance_range'] }}</p>
                                    </td>
                                    <td class="silat-table-cell min-w-[220px]">{{ $summary['place_name'] }}</td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        <p class="font-semibold text-gray-900">{{ $summary['attendance_score']['present_days'] }}/{{ $summary['attendance_score']['working_days'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500">hari kerja efektif</p>
                                    </td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        @if ($summary['pending_daily_validations'] > 0)
                                            <x-badge variant="warning">{{ number_format($summary['pending_daily_validations'], 0, ',', '.') }} perlu validasi</x-badge>
                                        @else
                                            <x-badge variant="success">Lengkap</x-badge>
                                        @endif
                                        @if (($summary['daily_flagged'] ?? 0) > 0)
                                            <div class="mt-1"><x-badge variant="danger">{{ number_format($summary['daily_flagged'], 0, ',', '.') }} bermasalah</x-badge></div>
                                        @endif
                                        <p class="mt-1 text-xs text-gray-500">{{ $summary['daily_validated'] }}/{{ $summary['daily_total'] }} catatan</p>
                                    </td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        @if ($summary['has_assessment'])
                                            <x-badge variant="success">Nilai {{ number_format((float) $summary['assessment_score'], 2, ',', '.') }}</x-badge>
                                        @else
                                            <x-badge variant="{{ $summary['can_assess'] ? 'warning' : 'neutral' }}">{{ $summary['can_assess'] ? 'Belum dinilai' : 'Belum dibuka' }}</x-badge>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell min-w-[220px]">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('field-supervisor.enrollments.show', ['enrollment' => $enrollment, 'tab' => 'daily']) }}" class="silat-btn-success px-3 py-2 text-xs">
                                                <x-icon name="fa-clipboard-check" />
                                                Validasi Catatan Harian
                                            </a>
                                            <a href="{{ route('field-supervisor.enrollments.show', ['enrollment' => $enrollment, 'tab' => 'assessment']) }}" class="silat-btn px-3 py-2 text-xs">
                                                <x-icon name="fa-star-half-stroke" />
                                                Nilai
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="silat-table-cell">
                                        <x-empty-state title="Tidak ada mahasiswa" description="Data tidak ditemukan untuk filter saat ini." icon="fa-user-check" />
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
