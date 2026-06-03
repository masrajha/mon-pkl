<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Koordinator</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Dashboard Koordinator Program') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Validasi dan monitoring mahasiswa pada periode/prodi tugas.</p>
            </div>
            <a class="silat-btn" href="{{ route('management.enrollment-validations.index') }}">
                <x-icon name="fa-user-check" />
                Validasi Pendaftaran
            </a>
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell space-y-6">
        <div class="silat-stat-grid">
            @foreach ([
                ['label' => 'Mahasiswa Terdaftar', 'value' => $stats['students'] ?? 0, 'color' => 'bg-cyan-700', 'icon' => 'fa-user-graduate'],
                ['label' => 'Check-in Hari Ini', 'value' => $stats['checkInsToday'] ?? 0, 'color' => 'bg-emerald-500', 'icon' => 'fa-fingerprint'],
                ['label' => 'Laporan Selesai', 'value' => $stats['completedReports'] ?? 0, 'color' => 'bg-amber-500', 'icon' => 'fa-file-alt'],
                ['label' => 'Total Sanksi', 'value' => $stats['sanctions'] ?? 0, 'color' => 'bg-rose-600', 'icon' => 'fa-gavel'],
            ] as $stat)
                <div class="silat-stat-card flex items-center gap-4">
                    <div class="{{ $stat['color'] }} flex h-11 w-11 items-center justify-center rounded-full text-white">
                        <x-icon :name="$stat['icon']" />
                    </div>
                    <div>
                        <p class="silat-stat-label">{{ $stat['label'] }}</p>
                        <p class="silat-stat-value">{{ number_format($stat['value'], 0, ',', '.') }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Peserta Terbaru</h3>
                        <p class="silat-section-description">Mahasiswa dalam periode/prodi yang Anda koordinasikan.</p>
                    </div>
                    <a class="silat-secondary-link" href="{{ route('reports.monitoring') }}">Buka rekap</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head"><tr><th class="silat-table-cell">Mahasiswa</th><th class="silat-table-cell">Periode</th><th class="silat-table-cell">Mitra</th><th class="silat-table-cell">Status</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentEnrollments as $enrollment)
                                <tr>
                                    <td class="silat-table-cell font-medium text-gray-900">{{ $enrollment->student?->full_name }}<div class="text-xs text-gray-500">{{ $enrollment->student?->npm }}</div></td>
                                    <td class="silat-table-cell">{{ $enrollment->internshipPeriod?->display_name }}<div class="text-xs text-gray-500">{{ $enrollment->studyProgram?->name }}</div></td>
                                    <td class="silat-table-cell">{{ $enrollment->internshipPlace?->name ?: '-' }}</td>
                                    <td class="silat-table-cell"><x-badge variant="{{ $enrollment->status === 'active' ? 'success' : 'neutral' }}">{{ $enrollment->status }}</x-badge></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="silat-table-cell"><x-empty-state title="Belum ada peserta" description="Peserta akan tampil setelah pendaftaran masuk ke scope koordinasi." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Rekap Pembekalan</h3>
                        <p class="silat-section-description">Presensi pembekalan pada scope koordinasi Anda.</p>
                    </div>
                    <a class="silat-secondary-link" href="{{ route('management.orientation-events.index') }}">Kelola pembekalan</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($orientationEvents as $event)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $event->name }}</p>
                                <p class="text-sm text-gray-500">{{ $event->studyProgram?->name ?: 'Semua prodi' }} · {{ $event->location_name }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-badge variant="success">{{ number_format($event->attendances_count, 0, ',', '.') }} hadir</x-badge>
                                <x-badge variant="danger">{{ number_format($event->absent_count, 0, ',', '.') }} belum</x-badge>
                                <a class="silat-secondary-link" href="{{ route('management.orientation-events.show', $event) }}">Detail</a>
                            </div>
                        </div>
                    @empty
                        <div class="p-5"><x-empty-state title="Belum ada pembekalan pada scope Anda" icon="fa-users-viewfinder" /></div>
                    @endforelse
                </div>
            </section>

            <section class="silat-card border-t-4 border-t-red-500">
                <div class="silat-section-header">
                    <div class="flex items-center gap-3">
                        <x-icon name="fa-triangle-exclamation" class="text-red-500" />
                        <div>
                            <h3 class="silat-section-title">Sanksi Tertinggi</h3>
                            <p class="silat-section-description">Prioritaskan mahasiswa yang perlu ditindaklanjuti.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($highestSanctions as $enrollment)
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $enrollment->student?->full_name }}</p>
                                <p class="text-sm text-gray-500">{{ $enrollment->internshipPeriod?->display_name }}</p>
                            </div>
                            <x-badge variant="danger">{{ number_format($enrollment->total_sanctions_points, 0, ',', '.') }} poin</x-badge>
                        </div>
                    @empty
                        <div class="p-5"><x-empty-state title="Tidak ada sanksi aktif" icon="fa-circle-check" /></div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="silat-card">
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">Penugasan Koordinator</h3>
                    <p class="silat-section-description">Scope periode dan prodi yang melekat pada akun Anda.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell">Periode</th><th class="silat-table-cell">Prodi</th><th class="silat-table-cell">Monitoring</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($assignments as $assignment)
                            <tr>
                                <td class="silat-table-cell">{{ $assignment->internshipPeriod?->display_name }}</td>
                                <td class="silat-table-cell">{{ $assignment->studyProgram?->name }}</td>
                                <td class="silat-table-cell space-x-3">
                                    <a class="silat-secondary-link" href="{{ route('maps.monitoring', ['period_id' => $assignment->internship_period_id, 'study_program_id' => $assignment->study_program_id]) }}">Peta</a>
                                    <a class="silat-secondary-link" href="{{ route('reports.monitoring', ['period_id' => $assignment->internship_period_id, 'study_program_id' => $assignment->study_program_id]) }}">Rekap</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="silat-table-cell"><x-empty-state title="Belum ada penugasan aktif" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
