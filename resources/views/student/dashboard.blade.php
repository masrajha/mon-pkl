<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Mahasiswa</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Program Saya') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Ringkasan pendaftaran, presensi, laporan, dan kebutuhan perangkat.</p>
            </div>
            <a class="silat-btn" href="{{ route('check-ins.create') }}"><x-icon name="fa-fingerprint" /> Presensi</a>
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell space-y-6">
        @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif

        <section class="rounded-lg bg-gradient-to-r from-green-500 to-green-700 p-6 text-white shadow-sm">
            <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm text-green-100">{{ now()->translatedFormat('l, d F Y') }}</p>
                    <h3 class="mt-1 text-2xl font-bold">Check-in MBKM/KP</h3>
                    <p class="mt-2 text-green-50">{{ $activeEnrollment?->internshipPeriod?->display_name ?: 'Belum ada periode aktif' }}</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2 text-sm font-semibold text-green-700" href="{{ route('check-ins.create') }}"><x-icon name="fa-location-dot" /> Presensi</a>
                        <a class="inline-flex items-center gap-2 rounded-lg bg-white/15 px-5 py-2 text-sm font-semibold text-white ring-1 ring-white/30" href="{{ $activeEnrollment ? route('student.reports.show', $activeEnrollment) : route('student.enrollments.create') }}"><x-icon name="fa-book-open" /> Laporan</a>
                        <a class="inline-flex items-center gap-2 rounded-lg bg-white/15 px-5 py-2 text-sm font-semibold text-white ring-1 ring-white/30" href="{{ route('student.supervisor-requests.create') }}"><x-icon name="fa-user-pen" /> Pembimbing</a>
                    </div>
                </div>
                <x-icon name="fa-location-dot" class="text-6xl text-white/50" />
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-sky-100 p-4 text-sky-800">
                <div class="flex items-center gap-3"><x-icon name="fa-calendar-check" class="text-xl text-sky-700" /><div><p class="text-sm">Hari Hadir</p><p class="text-xl font-bold">{{ number_format($attendanceDays, 0, ',', '.') }}</p></div></div>
            </div>
            <div class="rounded-lg bg-rose-100 p-4 text-rose-800">
                <div class="flex items-center gap-3"><x-icon name="fa-exclamation-triangle" class="text-xl text-rose-700" /><div><p class="text-sm">Total Sanksi</p><p class="text-xl font-bold">{{ number_format($sanctionsPoints, 0, ',', '.') }}</p></div></div>
            </div>
            <div class="rounded-lg bg-amber-100 p-4 text-amber-800">
                <div class="flex items-center gap-3"><x-icon name="fa-hourglass-start" class="text-xl text-amber-700" /><div><p class="text-sm">Deadline Terdekat</p><p class="text-xl font-bold">{{ $nearestDeadline?->deadline_date?->format('d/m') ?: '-' }}</p></div></div>
            </div>
        </div>

        @if ($orientationEvents->isNotEmpty())
            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Presensi Pembekalan</h3>
                        <p class="silat-section-description">Kegiatan pembekalan wajib untuk program periode ini.</p>
                    </div>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    @foreach ($orientationEvents as $event)
                        @php($attendance = $event->attendances->first())
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $event->name }}</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ $event->location_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $event->starts_at?->format('d/m/Y H:i') ?: '-' }} s.d. {{ $event->ends_at?->format('d/m/Y H:i') ?: '-' }}</p>
                                </div>
                                <x-badge :variant="$attendance ? 'success' : 'warning'">{{ $attendance ? 'Sudah' : 'Wajib' }}</x-badge>
                            </div>
                            <div class="mt-4">
                                @if ($attendance)
                                    <p class="text-sm text-green-700">Presensi tercatat {{ $attendance->checked_at?->format('d/m/Y H:i') }}.</p>
                                @else
                                    <a class="silat-btn" href="{{ route('student.orientation-attendances.create', $event) }}"><x-icon name="fa-fingerprint" /> Presensi Pembekalan</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Status Program</h3>
                        <p class="silat-section-description">Posisi pendaftaran dan kelengkapan pembimbing.</p>
                    </div>
                    <a class="silat-secondary-link" href="{{ route('student.enrollments.create') }}">Daftar program</a>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-3">
                    <div class="silat-stat-card"><p class="silat-stat-label">Profil</p><p class="mt-2 text-lg font-semibold text-gray-900">{{ $student?->full_name ?: 'Belum lengkap' }}</p><p class="text-sm text-gray-500">{{ $student?->npm ?: '-' }}</p><a class="silat-secondary-link mt-3" href="{{ route('student.profile.edit') }}">Kelola profil</a></div>
                    <div class="silat-stat-card"><p class="silat-stat-label">Pendaftaran</p><p class="silat-stat-value">{{ $enrollments->count() }}</p><p class="silat-stat-note">{{ $activeEnrollment?->status ?: 'Belum ada status aktif' }}</p></div>
                    <div class="silat-stat-card"><p class="silat-stat-label">Usulan Tempat</p><p class="silat-stat-value">{{ $proposals->count() }}</p><a class="silat-secondary-link" href="{{ route('student.proposals.create') }}">Ajukan tempat</a></div>
                </div>
            </section>

            <section class="silat-card p-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="silat-section-title">Progres Laporan</h3>
                        <p class="silat-section-description">Indikator awal kelengkapan laporan mahasiswa.</p>
                    </div>
                    <x-icon name="fa-book-open" class="text-2xl text-blue-600" />
                </div>
                <div class="mt-5">
                    <div class="flex items-center justify-between text-sm"><span class="font-medium text-gray-700">Laporan lengkap</span><span class="font-semibold text-gray-900">{{ $reportProgress }}%</span></div>
                    <div class="mt-2 h-3 rounded-full bg-gray-100"><div class="h-3 rounded-full bg-blue-600" style="width: {{ $reportProgress }}%"></div></div>
                    <p class="mt-3 text-sm text-gray-500">{{ $activeEnrollment?->final_report_path ? 'File laporan akhir sudah tersedia.' : 'Upload laporan akhir belum tersedia.' }}</p>
                </div>
            </section>
        </div>

        <section class="silat-card" data-browser-permissions>
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">Izin Akses Perangkat</h3>
                    <p class="silat-section-description">Presensi membutuhkan lokasi GPS dan kamera realtime dari browser.</p>
                </div>
            </div>
            <div class="grid gap-3 p-5 md:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lokasi</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900" data-permission-status="geolocation">Memeriksa...</p>
                    <button type="button" data-permission-request="geolocation" class="silat-btn mt-3">Izinkan Lokasi</button>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kamera</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900" data-permission-status="camera">Memeriksa...</p>
                    <button type="button" data-permission-request="camera" class="silat-btn mt-3">Izinkan Kamera</button>
                </div>
            </div>
        </section>

        <section class="silat-card">
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">Riwayat Pendaftaran</h3>
                    <p class="silat-section-description">Daftar program, mitra, dan pembimbing yang terkait akun Anda.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell">Periode/Prodi</th><th class="silat-table-cell">Mitra</th><th class="silat-table-cell">Pembimbing</th><th class="silat-table-cell">Status</th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($enrollments as $enrollment)
                            <tr>
                                <td class="silat-table-cell">{{ $enrollment->internshipPeriod?->display_name }}<div class="text-xs text-gray-500">{{ $enrollment->studyProgram?->name }}</div></td>
                                <td class="silat-table-cell">{{ $enrollment->internshipPlace?->name ?: 'Belum ditempatkan' }}</td>
                                <td class="silat-table-cell">{{ $enrollment->lecturer?->name ?: 'Dosen belum ditentukan' }}<div class="text-xs text-gray-500">{{ $enrollment->field_supervisor ?: 'Pembimbing lapangan belum diisi' }}</div></td>
                                <td class="silat-table-cell">
                                    @php($statusVariant = match($enrollment->status) {'active' => 'success', 'pending_verification' => 'warning', 'revision_required' => 'warning', 'rejected' => 'danger', default => 'neutral'})
                                    <x-badge variant="{{ $statusVariant }}">{{ $enrollment->status }}</x-badge>
                                    @if ($enrollment->status === 'revision_required' && $enrollment->admin_note)
                                        <div class="mt-1 max-w-xs text-xs text-amber-700">{{ $enrollment->admin_note }}</div>
                                    @endif
                                </td>
                                <td class="silat-table-cell space-x-3 text-right">
                                    @if ($enrollment->status === 'active')
                                        <a class="silat-secondary-link" href="{{ route('check-ins.create') }}">Presensi</a>
                                        <a class="silat-secondary-link" href="{{ route('student.relocations.create') }}">Pindah</a>
                                        <a class="silat-secondary-link" href="{{ route('student.supervisor-requests.create') }}">Pembimbing</a>
                                    @elseif ($enrollment->status === 'revision_required')
                                        <a class="silat-secondary-link" href="{{ route('student.enrollments.edit', $enrollment) }}">Perbaiki</a>
                                    @endif
                                    <a class="silat-secondary-link" href="{{ route('student.reports.show', $enrollment) }}">Laporan</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="silat-table-cell"><x-empty-state title="Belum ada pendaftaran program" description="Mulai pendaftaran untuk mengikuti program MBKM/KP." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
