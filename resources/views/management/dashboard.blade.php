<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Admin</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Dashboard Manajemen') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Ringkasan validasi, periode aktif, dan master data SiLAT.</p>
            </div>
            <a class="silat-btn" href="{{ route('management.enrollment-validations.index') }}">
                <x-icon name="fa-user-check" />
                Validasi Pendaftaran
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            <div class="silat-stat-grid">
                @foreach ([
                    ['label' => 'Total Mahasiswa MBKM/KP', 'value' => $counts['students'], 'color' => 'bg-blue-600', 'icon' => 'fa-users'],
                    ['label' => 'Total Dosen Pembimbing', 'value' => $counts['lecturers'], 'color' => 'bg-green-600', 'icon' => 'fa-chalkboard-user'],
                    ['label' => 'Total Mitra', 'value' => $counts['places'], 'color' => 'bg-yellow-500', 'icon' => 'fa-building'],
                    ['label' => 'Rata-rata Kehadiran', 'value' => ($counts['attendanceRate'] ?? 0).'%', 'color' => 'bg-purple-600', 'icon' => 'fa-calendar-check'],
                ] as $stat)
                    <div class="silat-stat-card flex items-center justify-between gap-4">
                        <div>
                            <p class="silat-stat-label">{{ $stat['label'] }}</p>
                            <p class="silat-stat-value">{{ is_numeric($stat['value']) ? number_format($stat['value'], 0, ',', '.') : $stat['value'] }}</p>
                        </div>
                        <div class="{{ $stat['color'] }} flex h-11 w-11 items-center justify-center rounded-full text-white">
                            <x-icon :name="$stat['icon']" />
                        </div>
                    </div>
                @endforeach
            </div>

            @include('management.partials.action-required', ['summary' => $actionRequiredSummary])

            <section class="silat-card border-l-4 border-l-red-500">
                <div class="silat-section-header">
                    <div class="flex items-center gap-3">
                        <x-icon name="fa-hourglass-half" class="text-red-500" />
                        <div>
                            <h3 class="silat-section-title">Pendaftaran Perlu Validasi</h3>
                            <p class="silat-section-description">{{ number_format($counts['pendingEnrollments'] ?? 0, 0, ',', '.') }} pengajuan menunggu keputusan admin/koordinator.</p>
                        </div>
                    </div>
                    <x-badge variant="danger">{{ number_format($counts['pendingEnrollments'] ?? 0, 0, ',', '.') }} pending</x-badge>
                </div>
                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head"><tr><th class="silat-table-cell">Mahasiswa</th><th class="silat-table-cell">Program/Periode</th><th class="silat-table-cell">Mitra</th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($pendingEnrollments as $enrollment)
                                <tr>
                                    <td class="silat-table-cell font-medium text-gray-900">{{ $enrollment->student?->full_name }}<div class="text-xs text-gray-500">{{ $enrollment->student?->npm }}</div></td>
                                    <td class="silat-table-cell">{{ $enrollment->internshipPeriod?->display_name }}<div class="text-xs text-gray-500">{{ $enrollment->studyProgram?->name }}</div></td>
                                    <td class="silat-table-cell">{{ $enrollment->internshipPlace?->name ?: 'Belum dipilih' }}</td>
                                    <td class="silat-table-cell text-right"><a class="silat-secondary-link" href="{{ route('management.enrollment-validations.index', ['period_id' => $enrollment->internship_period_id]) }}">Tinjau</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="silat-table-cell"><x-empty-state title="Tidak ada pendaftaran pending" description="Semua pengajuan terbaru sudah diproses." icon="fa-circle-check" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Rekap Pembekalan</h3>
                        <p class="silat-section-description">Ringkasan presensi pembekalan program terbaru.</p>
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
                                <a class="silat-secondary-link" href="{{ route('management.orientation-events.edit', $event) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a>
                                @if ($event->attendances_count > 0)
                                    <button type="button" class="silat-secondary-link text-gray-400" title="Tidak dapat dihapus karena sudah ada presensi" disabled><x-icon name="fa-trash" class="mr-1" /> Delete</button>
                                @else
                                    <form method="POST" action="{{ route('management.orientation-events.destroy', $event) }}" onsubmit="return confirm('Hapus event pembekalan ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="silat-secondary-link text-red-700 hover:text-red-900"><x-icon name="fa-trash" class="mr-1" /> Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-5"><x-empty-state title="Belum ada event pembekalan" icon="fa-users-viewfinder" /></div>
                    @endforelse
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
                <section class="silat-card">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">Periode Aktif</h3>
                            <p class="silat-section-description">Pantau periode program yang sedang berjalan.</p>
                        </div>
                        <a class="silat-secondary-link" href="{{ route('management.periods.index') }}">Kelola periode</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($activePeriods as $period)
                            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $period->display_name }}</p>
                                    <p class="text-sm text-gray-500">{{ $period->starts_at?->format('d/m/Y') ?: '-' }} - {{ $period->ends_at?->format('d/m/Y') ?: '-' }}</p>
                                </div>
                                <x-badge>{{ number_format($period->enrollments_count, 0, ',', '.') }} peserta</x-badge>
                            </div>
                        @empty
                            <div class="p-5"><x-empty-state title="Belum ada periode aktif" icon="fa-calendar-days" /></div>
                        @endforelse
                    </div>
                </section>

                <section class="silat-card p-5">
                    <h3 class="silat-section-title">Master Data</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            ['label' => 'User', 'value' => $counts['users'], 'route' => 'management.users.index'],
                            ['label' => 'Koordinator', 'value' => $counts['coordinators'], 'route' => 'management.coordinators.index'],
                            ['label' => 'Prodi', 'value' => $counts['studyPrograms'], 'route' => 'management.study-programs.index'],
                            ['label' => 'Periode', 'value' => $counts['periods'], 'route' => 'management.periods.index'],
                        ] as $item)
                            <a class="silat-action-card" href="{{ route($item['route']) }}">
                                <p class="silat-stat-label">{{ $item['label'] }}</p>
                                <p class="silat-stat-value">{{ number_format($item['value'], 0, ',', '.') }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
