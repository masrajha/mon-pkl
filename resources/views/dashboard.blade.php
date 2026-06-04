<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ __('SiLAT') }}</p>
                <h2 class="mt-1 text-2xl font-semibold leading-tight text-gray-900">{{ __('Dashboard') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Sistem Laporan Aktivitas Terpadu MBKM & Kerja Praktik.') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm">
                <span class="font-semibold text-gray-900">{{ Auth::user()->name }}</span>
                <x-badge class="ml-2">{{ ucfirst(Auth::user()->role) }}</x-badge>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            @if (Auth::user()->hasRole('mahasiswa'))
                <section class="rounded-lg bg-gradient-to-r from-green-500 to-green-700 p-6 text-white shadow-sm">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm text-green-100">{{ now()->translatedFormat('l, d F Y') }}</p>
                            <h3 class="mt-1 text-2xl font-bold">Program Saya</h3>
                            <p class="mt-2 text-green-50">{{ $studentEnrollment?->internshipPeriod?->display_name ?: 'Lengkapi profil dan mulai pendaftaran program.' }}</p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2 text-sm font-semibold text-green-700" href="{{ route('student.dashboard') }}">
                                    <x-icon name="fa-location-dot" />
                                    Buka Program Saya
                                </a>
                                <a class="inline-flex items-center gap-2 rounded-lg bg-white/15 px-5 py-2 text-sm font-semibold text-white ring-1 ring-white/30" href="{{ route('student.enrollments.create') }}">
                                    <x-icon name="fa-clipboard-list" />
                                    Pendaftaran
                                </a>
                            </div>
                        </div>
                        <x-icon name="fa-location-dot" class="text-6xl text-white/50" />
                    </div>
                </section>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-sky-100 p-4 text-sky-800">
                        <div class="flex items-center gap-3"><x-icon name="fa-calendar-check" class="text-xl text-sky-700" /><div><p class="text-sm">Profil</p><p class="text-xl font-bold">{{ $studentProfileComplete ? 'Lengkap' : 'Belum lengkap' }}</p></div></div>
                    </div>
                    <div class="rounded-lg bg-rose-100 p-4 text-rose-800">
                        <div class="flex items-center gap-3"><x-icon name="fa-exclamation-triangle" class="text-xl text-rose-700" /><div><p class="text-sm">Status Pendaftaran</p><p class="text-xl font-bold">{{ $studentEnrollment?->status ?: 'Belum daftar' }}</p></div></div>
                    </div>
                    <div class="rounded-lg bg-amber-100 p-4 text-amber-800">
                        <div class="flex items-center gap-3"><x-icon name="fa-hourglass-start" class="text-xl text-amber-700" /><div><p class="text-sm">Usulan Mitra</p><p class="text-xl font-bold">{{ number_format($studentProposalCount, 0, ',', '.') }}</p></div></div>
                    </div>
                </div>
            @endif

            @if (Auth::user()->hasRole('dosen'))
                <section class="silat-card">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">{{ __('Dashboard Dosen Pembimbing') }}</h3>
                            <p class="silat-section-description">{{ __('Pantau mahasiswa bimbingan, kebutuhan revisi, dan akses monitoring.') }}</p>
                        </div>
                        <a class="silat-btn-secondary" href="{{ route('reports.monitoring') }}"><x-icon name="fa-chart-column" /> Rekap Bimbingan</a>
                    </div>
                    <div class="p-5">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="silat-stat-card flex items-center gap-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-600 text-white"><x-icon name="fa-chalkboard-user" /></div>
                                <div><p class="silat-stat-label">Mahasiswa Bimbingan</p><p class="silat-stat-value">{{ number_format($lecturerStats['active_enrollments'] ?? 0, 0, ',', '.') }}</p></div>
                            </div>
                            <div class="silat-stat-card flex items-center gap-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-orange-500 text-white"><x-icon name="fa-pen-ruler" /></div>
                                <div><p class="silat-stat-label">Perlu Bimbingan</p><p class="silat-stat-value">{{ number_format($lecturerStats['pending_enrollments'] ?? 0, 0, ',', '.') }}</p></div>
                            </div>
                            <div class="silat-stat-card flex items-center gap-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-500 text-white"><x-icon name="fa-calendar-day" /></div>
                                <div><p class="silat-stat-label">Seminar Terdekat</p><p class="silat-stat-value">0</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto border-t border-gray-100">
                        <table class="silat-table">
                            <thead class="silat-table-head"><tr><th class="silat-table-cell">Mahasiswa</th><th class="silat-table-cell">Program</th><th class="silat-table-cell">Mitra</th><th class="silat-table-cell">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($supervisedEnrollments as $enrollment)
                                    <tr>
                                        <td class="silat-table-cell font-medium text-gray-900">{{ $enrollment->student?->full_name }}<div class="text-xs text-gray-500">{{ $enrollment->student?->npm }}</div></td>
                                        <td class="silat-table-cell">{{ $enrollment->internshipPeriod?->display_name }}<div class="text-xs text-gray-500">{{ $enrollment->studyProgram?->name }}</div></td>
                                        <td class="silat-table-cell">{{ $enrollment->internshipPlace?->name ?: '-' }}</td>
                                        <td class="silat-table-cell space-x-3"><a class="silat-secondary-link" href="{{ route('maps.monitoring') }}">Peta</a><a class="silat-secondary-link" href="{{ route('reports.monitoring') }}">Rekap</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="silat-table-cell"><x-empty-state title="Belum ada mahasiswa bimbingan" icon="fa-chalkboard-user" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if (Auth::user()->hasRole(['admin', 'koordinator']))
                @include('management.partials.action-required', ['summary' => $actionRequiredSummary])
            @endif

            @if (Auth::user()->hasRole('koordinator'))
                <section class="silat-card">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">{{ __('Koordinator Program') }}</h3>
                            <p class="silat-section-description">{{ __('Periode dan prodi yang menjadi tanggung jawab koordinasi Anda.') }}</p>
                        </div>
                        <a class="silat-btn" href="{{ route('coordinator.dashboard') }}"><x-icon name="fa-user-tie" /> Dashboard Koordinator</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="silat-table">
                            <thead class="silat-table-head"><tr><th class="silat-table-cell">Periode</th><th class="silat-table-cell">Prodi</th><th class="silat-table-cell">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($coordinatorAssignments as $assignment)
                                    <tr>
                                        <td class="silat-table-cell font-medium text-gray-900">{{ $assignment->internshipPeriod?->display_name }}</td>
                                        <td class="silat-table-cell text-gray-600">{{ $assignment->studyProgram?->name }}</td>
                                        <td class="silat-table-cell space-x-3">
                                            <a class="silat-secondary-link" href="{{ route('maps.monitoring', ['period_id' => $assignment->internship_period_id, 'study_program_id' => $assignment->study_program_id]) }}">Monitoring</a>
                                            <a class="silat-secondary-link" href="{{ route('reports.monitoring', ['period_id' => $assignment->internship_period_id, 'study_program_id' => $assignment->study_program_id]) }}">Rekap</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="silat-table-cell"><x-empty-state title="Tidak ada penugasan aktif" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if (Auth::user()->hasRole('admin'))
                <section class="silat-card">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">{{ __('Administrasi Sistem') }}</h3>
                            <p class="silat-section-description">{{ __('Indikator utama master data, penempatan, dan proses validasi.') }}</p>
                        </div>
                        <a class="silat-btn" href="{{ route('management.dashboard') }}"><x-icon name="fa-chart-line" /> Buka Manajemen</a>
                    </div>
                    <div class="p-5">
                        <div class="silat-stat-grid">
                            @foreach ([
                                ['label' => 'Total Mahasiswa MBKM/KP', 'value' => $adminStats['students'] ?? 0, 'color' => 'bg-blue-600', 'icon' => 'fa-users'],
                                ['label' => 'Total Dosen Pembimbing', 'value' => $adminStats['lecturers'] ?? 0, 'color' => 'bg-green-600', 'icon' => 'fa-chalkboard-user'],
                                ['label' => 'Total Mitra', 'value' => $adminStats['places'] ?? 0, 'color' => 'bg-yellow-500', 'icon' => 'fa-building'],
                                ['label' => 'Rata-rata Kehadiran', 'value' => ($adminStats['attendance_rate'] ?? 0).'%', 'color' => 'bg-purple-600', 'icon' => 'fa-calendar-check'],
                            ] as $stat)
                                <div class="silat-stat-card flex items-center justify-between gap-4">
                                    <div><p class="silat-stat-label">{{ $stat['label'] }}</p><p class="silat-stat-value">{{ is_numeric($stat['value']) ? number_format($stat['value'], 0, ',', '.') : $stat['value'] }}</p></div>
                                    <div class="{{ $stat['color'] }} flex h-11 w-11 items-center justify-center rounded-full text-white"><x-icon :name="$stat['icon']" /></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">{{ __('Monitoring dan Laporan') }}</h3>
                        <p class="silat-section-description">{{ __('Akses sesuai hak peran untuk membaca lokasi dan rekap program.') }}</p>
                    </div>
                </div>
                <div class="grid gap-3 p-5 md:grid-cols-3">
                    <a class="silat-action-card" href="{{ route('maps.places') }}"><p class="font-semibold text-gray-900">Peta Mitra</p><p class="silat-stat-note">Sebaran mitra dan jumlah peserta.</p></a>
                    <a class="silat-action-card" href="{{ route('maps.monitoring') }}"><p class="font-semibold text-gray-900">Peta Monitoring</p><p class="silat-stat-note">Lokasi check-in mahasiswa dan jarak ke lokasi mitra.</p></a>
                    <a class="silat-action-card" href="{{ route('reports.monitoring') }}"><p class="font-semibold text-gray-900">Rekap Monitoring</p><p class="silat-stat-note">Ringkasan kehadiran, durasi, dan jarak.</p></a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
