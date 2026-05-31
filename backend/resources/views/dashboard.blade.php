<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">{{ __('Sistem Monitoring PKL') }}</p>
                <h2 class="mt-1 text-2xl font-semibold leading-tight text-slate-950">{{ __('Dashboard') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Ringkasan pekerjaan dan akses cepat sesuai peran Anda.') }}</p>
            </div>
            <div class="rounded-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ Auth::user()->name }}</span>
                <span class="ml-2 monpkl-status-pill">{{ ucfirst(Auth::user()->role) }}</span>
            </div>
        </div>
    </x-slot>

    <div class="bg-slate-50 py-8">
        <div class="monpkl-shell space-y-6">
            @if (Auth::user()->hasRole('mahasiswa'))
                <section class="monpkl-section">
                    <div class="monpkl-section-header">
                        <div>
                            <h3 class="monpkl-section-title">{{ __('PKL Saya') }}</h3>
                            <p class="monpkl-section-description">{{ __('Mulai dari profil, pendaftaran, presensi, sampai laporan.') }}</p>
                        </div>
                        <a class="monpkl-primary-link" href="{{ route('student.dashboard') }}">{{ __('Buka PKL Saya') }}</a>
                    </div>
                    <div class="p-5">
                        <div class="monpkl-stat-grid">
                            <div class="monpkl-stat-card">
                                <p class="monpkl-stat-label">{{ __('Profil') }}</p>
                                <p class="monpkl-stat-value">{{ $studentProfileComplete ? __('Lengkap') : __('Belum lengkap') }}</p>
                                <a class="monpkl-secondary-link" href="{{ route('student.profile.edit') }}">{{ __('Kelola profil') }}</a>
                            </div>
                            <div class="monpkl-stat-card">
                                <p class="monpkl-stat-label">{{ __('Pendaftaran') }}</p>
                                <p class="monpkl-stat-value">{{ $studentEnrollment?->status ?: __('Belum daftar') }}</p>
                                <a class="monpkl-secondary-link" href="{{ route('student.enrollments.create') }}">{{ __('Daftar PKL') }}</a>
                            </div>
                            <div class="monpkl-stat-card">
                                <p class="monpkl-stat-label">{{ __('Tempat PKL') }}</p>
                                <p class="mt-2 min-h-8 text-base font-semibold text-slate-950">{{ $studentEnrollment?->internshipPlace?->name ?: __('Belum dipilih') }}</p>
                                <a class="monpkl-secondary-link" href="{{ route('student.proposals.create') }}">{{ __('Ajukan tempat') }}</a>
                            </div>
                            <div class="monpkl-stat-card">
                                <p class="monpkl-stat-label">{{ __('Aksi Hari Ini') }}</p>
                                <p class="monpkl-stat-value">{{ $studentEnrollment?->status === 'active' ? __('Aktif') : __('Menunggu') }}</p>
                                @if ($studentEnrollment?->status === 'active')
                                    <a class="monpkl-secondary-link" href="{{ route('check-ins.create') }}">{{ __('Presensi') }}</a>
                                @else
                                    <p class="monpkl-stat-note">{{ __('Presensi tersedia setelah validasi.') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            @if (Auth::user()->hasRole('dosen'))
                <section class="monpkl-section">
                    <div class="monpkl-section-header">
                        <div>
                            <h3 class="monpkl-section-title">{{ __('Bimbingan Dosen') }}</h3>
                            <p class="monpkl-section-description">{{ __('Pantau mahasiswa bimbingan, lokasi, dan rekap kehadiran.') }}</p>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="monpkl-stat-grid">
                            <div class="monpkl-stat-card">
                                <p class="monpkl-stat-label">{{ __('Bimbingan Aktif') }}</p>
                                <p class="monpkl-stat-value">{{ number_format($lecturerStats['active_enrollments'] ?? 0, 0, ',', '.') }}</p>
                            </div>
                            <div class="monpkl-stat-card">
                                <p class="monpkl-stat-label">{{ __('Menunggu Verifikasi') }}</p>
                                <p class="monpkl-stat-value">{{ number_format($lecturerStats['pending_enrollments'] ?? 0, 0, ',', '.') }}</p>
                            </div>
                            <a class="monpkl-action-card" href="{{ route('maps.monitoring') }}">
                                <p class="font-semibold text-slate-950">{{ __('Peta Monitoring') }}</p>
                                <p class="monpkl-stat-note">{{ __('Lihat lokasi mahasiswa bimbingan.') }}</p>
                            </a>
                            <a class="monpkl-action-card" href="{{ route('reports.monitoring') }}">
                                <p class="font-semibold text-slate-950">{{ __('Rekap Bimbingan') }}</p>
                                <p class="monpkl-stat-note">{{ __('Ringkasan presensi dan durasi.') }}</p>
                            </a>
                        </div>
                    </div>
                </section>
            @endif

            @if (Auth::user()->hasRole('koordinator'))
                <section class="monpkl-section">
                    <div class="monpkl-section-header">
                        <div>
                            <h3 class="monpkl-section-title">{{ __('Koordinator PKL') }}</h3>
                            <p class="monpkl-section-description">{{ __('Periode dan prodi yang menjadi tanggung jawab koordinasi Anda.') }}</p>
                        </div>
                        <a class="monpkl-primary-link" href="{{ route('coordinator.dashboard') }}">{{ __('Buka mode koordinator') }}</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="monpkl-table">
                            <thead class="monpkl-table-head"><tr><th class="monpkl-table-cell">Periode</th><th class="monpkl-table-cell">Prodi</th><th class="monpkl-table-cell">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($coordinatorAssignments as $assignment)
                                    <tr>
                                        <td class="monpkl-table-cell font-medium text-slate-900">{{ $assignment->internshipPeriod?->name }}</td>
                                        <td class="monpkl-table-cell text-slate-600">{{ $assignment->studyProgram?->name }}</td>
                                        <td class="monpkl-table-cell space-x-3">
                                            <a class="monpkl-secondary-link" href="{{ route('maps.monitoring', ['period_id' => $assignment->internship_period_id, 'study_program_id' => $assignment->study_program_id]) }}">Monitoring</a>
                                            <a class="monpkl-secondary-link" href="{{ route('reports.monitoring', ['period_id' => $assignment->internship_period_id, 'study_program_id' => $assignment->study_program_id]) }}">Rekap</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">{{ __('Tidak ada penugasan aktif.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if (Auth::user()->hasRole('admin'))
                <section class="monpkl-section">
                    <div class="monpkl-section-header">
                        <div>
                            <h3 class="monpkl-section-title">{{ __('Administrasi Sistem') }}</h3>
                            <p class="monpkl-section-description">{{ __('Indikator utama master data, penempatan, dan proses validasi.') }}</p>
                        </div>
                        <a class="monpkl-primary-link" href="{{ route('management.dashboard') }}">{{ __('Buka manajemen') }}</a>
                    </div>
                    <div class="p-5">
                        <div class="monpkl-stat-grid">
                            @foreach ([
                                'User' => $adminStats['users'] ?? 0,
                                'Mahasiswa' => $adminStats['students'] ?? 0,
                                'Dosen' => $adminStats['lecturers'] ?? 0,
                                'Peserta' => $adminStats['enrollments'] ?? 0,
                                'Periode' => $adminStats['periods'] ?? 0,
                                'Tempat PKL' => $adminStats['places'] ?? 0,
                                'Usulan Pending' => $adminStats['pending_proposals'] ?? 0,
                                'Pendaftaran Pending' => $adminStats['pending_enrollments'] ?? 0,
                            ] as $label => $count)
                                <div class="monpkl-stat-card">
                                    <p class="monpkl-stat-label">{{ $label }}</p>
                                    <p class="monpkl-stat-value">{{ number_format($count, 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <section class="monpkl-section">
                <div class="monpkl-section-header">
                    <div>
                        <h3 class="monpkl-section-title">{{ __('Monitoring dan Laporan') }}</h3>
                        <p class="monpkl-section-description">{{ __('Akses umum sesuai hak akses untuk membaca lokasi dan rekap PKL.') }}</p>
                    </div>
                </div>
                <div class="grid gap-3 p-5 md:grid-cols-3">
                    <a class="monpkl-action-card" href="{{ route('maps.places') }}">
                        <p class="font-semibold text-slate-950">{{ __('Peta Tempat PKL') }}</p>
                        <p class="monpkl-stat-note">{{ __('Sebaran instansi dan jumlah peserta.') }}</p>
                    </a>
                    <a class="monpkl-action-card" href="{{ route('maps.monitoring') }}">
                        <p class="font-semibold text-slate-950">{{ __('Peta Monitoring') }}</p>
                        <p class="monpkl-stat-note">{{ __('Lokasi check-in mahasiswa dan jarak ke instansi.') }}</p>
                    </a>
                    <a class="monpkl-action-card" href="{{ route('reports.monitoring') }}">
                        <p class="font-semibold text-slate-950">{{ __('Rekap Monitoring') }}</p>
                        <p class="monpkl-stat-note">{{ __('Ringkasan kehadiran, durasi, dan jarak.') }}</p>
                    </a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
