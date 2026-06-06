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
                @php
                    $studentEnrollmentSummary ??= ['total' => 0, 'statuses' => collect()];
                    $studentEnrollmentStatusLabels = [
                        'draft' => 'Draft',
                        'pending_verification' => 'Menunggu Verifikasi',
                        'revision_required' => 'Perlu Revisi',
                        'active' => 'Aktif',
                        'completed' => 'Selesai',
                        'period_inactive' => 'Periode Nonaktif',
                        'period_unavailable' => 'Periode Tidak Tersedia',
                        'rejected' => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                    ];
                    $studentEnrollmentStatusCounts = collect($studentEnrollmentStatusLabels)
                        ->map(fn ($label, $status) => [
                            'status' => $status,
                            'label' => $label,
                            'count' => (int) ($studentEnrollmentSummary['statuses'][$status] ?? 0),
                        ])
                        ->filter(fn ($item) => $item['count'] > 0);
                    $studentPrimaryEnrollmentStatus = $studentEnrollmentStatusCounts->firstWhere('status', 'active') ?? $studentEnrollmentStatusCounts->first();
                    $studentSecondaryEnrollmentStatuses = $studentEnrollmentStatusCounts
                        ->reject(fn ($item) => $studentPrimaryEnrollmentStatus && $item['status'] === $studentPrimaryEnrollmentStatus['status'])
                        ->values();
                    $hasEnrollmentSummary = ($studentEnrollmentSummary['total'] ?? 0) > 0;
                @endphp
                <section class="rounded-lg border border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-sky-50 p-6 text-gray-950 shadow-sm">
                    <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-sm font-medium text-emerald-700">{{ now()->translatedFormat('l, d F Y') }}</p>
                            <h3 class="mt-1 text-2xl font-bold">Program Saya</h3>
                            <p class="mt-2 text-gray-600">{{ $studentActiveEnrollments->isNotEmpty() ? 'Program aktif yang sedang Anda ikuti.' : 'Lengkapi profil dan mulai pendaftaran program.' }}</p>
                        </div>
                        <div class="hidden h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl text-emerald-700 md:flex">
                            <x-icon name="fa-location-dot" />
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        @forelse ($studentActiveEnrollments as $activeEnrollment)
                            <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <x-badge variant="success">Aktif</x-badge>
                                        <h4 class="mt-3 font-semibold text-gray-950">{{ $activeEnrollment->internshipPeriod?->display_name ?: 'Program aktif' }}</h4>
                                        <p class="mt-1 text-sm text-gray-500">{{ $activeEnrollment->studyProgram?->name ?: '-' }}</p>
                                    </div>
                                    <p class="text-right text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ $activeEnrollment->internshipPeriod?->program?->name ?: 'Program' }}</p>
                                </div>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-md border border-gray-100 bg-gray-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mitra</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $activeEnrollment->internshipPlace?->name ?: 'Belum ditentukan' }}</p>
                                    </div>
                                    <div class="rounded-md border border-gray-100 bg-gray-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Dosen</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $activeEnrollment->lecturer?->name ?: 'Belum ditentukan' }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" href="{{ route('student.reports.show', $activeEnrollment) }}">
                                        <x-icon name="fa-arrow-right-to-bracket" />
                                        Detail
                                    </a>
                                    <a class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2" href="{{ route('check-ins.create', ['enrollment' => $activeEnrollment->id]) }}">
                                        <x-icon name="fa-fingerprint" />
                                        Presensi
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 bg-white/70 p-5">
                                <p class="font-semibold text-gray-900">Belum ada program aktif</p>
                                <p class="mt-1 text-sm text-gray-500">Daftar program atau tunggu validasi pendaftaran agar program aktif muncul di sini.</p>
                                <a class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" href="{{ route('student.enrollments.create') }}">
                                    <x-icon name="fa-clipboard-list" />
                                    Pendaftaran
                                </a>
                            </div>
                        @endforelse
                    </div>
                </section>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-sky-100 p-4 text-sky-800">
                        <div class="flex items-center gap-3"><x-icon name="fa-calendar-check" class="text-xl text-sky-700" /><div><p class="text-sm">Profil</p><p class="text-xl font-bold">{{ $studentProfileComplete ? 'Lengkap' : 'Belum lengkap' }}</p></div></div>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-4 text-emerald-800">
                        <div class="flex items-center gap-3">
                            <x-icon name="{{ $hasEnrollmentSummary ? 'fa-circle-check' : 'fa-circle-info' }}" class="text-xl {{ $hasEnrollmentSummary ? 'text-emerald-700' : 'text-gray-600' }}" />
                            <div>
                                <p class="text-sm">Status Pendaftaran</p>
                                @if ($hasEnrollmentSummary)
                                    <p class="mt-1 text-xl font-bold text-emerald-900">
                                        {{ $studentPrimaryEnrollmentStatus['label'] }} {{ number_format($studentPrimaryEnrollmentStatus['count'], 0, ',', '.') }}
                                    </p>
                                    @if ($studentSecondaryEnrollmentStatuses->isNotEmpty())
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($studentSecondaryEnrollmentStatuses as $item)
                                                <span class="inline-flex items-center rounded-full bg-white px-5 py-2 text-sm font-semibold leading-normal text-emerald-700 shadow-sm ring-1 ring-emerald-200">
                                                    {{ $item['label'] }} {{ number_format($item['count'], 0, ',', '.') }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <p class="text-xl font-bold text-gray-700">Belum Mendaftar</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg bg-indigo-50 p-4 text-indigo-800">
                        <div class="flex items-center gap-3"><x-icon name="fa-building-circle-arrow-right" class="text-xl text-indigo-700" /><div><p class="text-sm">Usulan Mitra</p><p class="text-xl font-bold">{{ number_format($studentProposalCount, 0, ',', '.') }}</p></div></div>
                    </div>
                </div>

                @include('partials.important-deadlines', [
                    'deadlines' => $studentImportantDeadlines,
                    'title' => 'Deadline Program Saya',
                    'description' => 'Deadline dalam 7 hari ke depan dari program yang Anda ikuti, atau deadline terdekat berikutnya.',
                ])
            @endif

            @if (Auth::user()->hasRole('dosen'))
                @php
                    $supervisedGroups = $supervisedEnrollments->groupBy(fn ($enrollment) => $enrollment->internship_period_id ?: 'tanpa-periode');
                @endphp
                <section class="silat-card">
                    <div class="silat-section-header">
                        <div>
                            <h3 class="silat-section-title">{{ __('Dashboard Dosen Pembimbing') }}</h3>
                            <p class="silat-section-description">{{ __('Mahasiswa bimbingan dikelompokkan berdasarkan program periode.') }}</p>
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

                    <div class="space-y-5 border-t border-gray-100 p-5">
                        @forelse ($supervisedGroups as $group)
                            @php
                                $firstEnrollment = $group->first();
                                $period = $firstEnrollment?->internshipPeriod;
                            @endphp
                            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $period?->program?->name ?: 'Program' }}</p>
                                        <h4 class="mt-1 font-semibold text-gray-900">{{ $period?->display_name ?: 'Tanpa periode' }}</h4>
                                        <p class="mt-1 text-sm text-gray-500">{{ number_format($group->count(), 0, ',', '.') }} mahasiswa bimbingan</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a class="silat-btn-secondary" href="{{ route('management.submission-progress.index', ['period_id' => $period?->id]) }}"><x-icon name="fa-file-circle-check" /> Review Laporan</a>
                                        <a class="silat-btn-secondary" href="{{ route('management.seminar-requests.index', ['period_id' => $period?->id]) }}"><x-icon name="fa-person-chalkboard" /> Review Seminar</a>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="silat-table">
                                        <thead class="silat-table-head">
                                            <tr>
                                                <th class="silat-table-cell">Mahasiswa</th>
                                                <th class="silat-table-cell">Prodi</th>
                                                <th class="silat-table-cell">Mitra</th>
                                                <th class="silat-table-cell">Status</th>
                                                <th class="silat-table-cell">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($group as $enrollment)
                                                <tr>
                                                    <td class="silat-table-cell font-medium text-gray-900">
                                                        {{ $enrollment->student?->full_name }}
                                                        <div class="text-xs text-gray-500">{{ $enrollment->student?->npm }}</div>
                                                    </td>
                                                    <td class="silat-table-cell">{{ $enrollment->studyProgram?->name ?: '-' }}</td>
                                                    <td class="silat-table-cell">{{ $enrollment->internshipPlace?->name ?: '-' }}</td>
                                                    <td class="silat-table-cell"><x-badge>{{ $enrollment->status }}</x-badge></td>
                                                    <td class="silat-table-cell">
                                                        <div class="flex flex-wrap gap-3">
                                                            <a class="silat-secondary-link" href="{{ route('management.submission-progress.index', ['q' => $enrollment->student?->npm]) }}">Laporan</a>
                                                            <a class="silat-secondary-link" href="{{ route('management.seminar-requests.index', ['q' => $enrollment->student?->npm]) }}">Seminar</a>
                                                            <a class="silat-secondary-link" href="{{ route('maps.monitoring', ['period_id' => $enrollment->internship_period_id]) }}">Peta</a>
                                                            <a class="silat-secondary-link" href="{{ route('reports.monitoring', ['period_id' => $enrollment->internship_period_id]) }}">Rekap</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @empty
                            <x-empty-state title="Belum ada mahasiswa bimbingan" icon="fa-chalkboard-user" />
                        @endforelse
                    </div>
                </section>

                @include('management.partials.action-required', ['summary' => $actionRequiredSummary])

                @include('partials.important-deadlines', [
                    'deadlines' => $lecturerImportantDeadlines,
                    'title' => 'Deadline Mahasiswa Bimbingan',
                    'description' => 'Deadline dalam 7 hari ke depan dari periode mahasiswa bimbingan, atau deadline terdekat berikutnya.',
                ])
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
