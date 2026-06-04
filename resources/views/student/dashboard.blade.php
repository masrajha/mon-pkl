<x-app-layout>
    @php
        $activeStatusVariant = match($activeEnrollment?->status) {
            'active' => 'success',
            'pending_verification', 'draft' => 'warning',
            'revision_required' => 'warning',
            'completed' => 'info',
            'rejected', 'cancelled' => 'danger',
            default => 'neutral',
        };
        $latestSeminar = $activeEnrollment?->seminarRequests?->first();
        $seminarLabel = $latestSeminar
            ? ([
                'waiting_lecturer_approval' => 'Menunggu ACC Dosen',
                'waiting_manual_acc_validation' => 'Validasi ACC',
                'lecturer_approved' => 'ACC Dosen',
                'manual_acc_approved' => 'ACC Manual Valid',
                'scheduled' => 'Terjadwal',
                'waiting_assessment_validation' => 'Validasi Nilai',
                'assessment_revision_required' => 'Revisi Nilai',
                'completed' => 'Selesai',
                'rejected' => 'Ditolak',
                'cancelled' => 'Dibatalkan',
            ][$latestSeminar->status] ?? Str::headline($latestSeminar->status))
            : 'Belum diajukan';
        $canOpenActiveReport = (bool) $activeEnrollment;
        $reportUrl = $activeEnrollment ? route('student.reports.show', $activeEnrollment) : route('student.enrollments.create');
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Mahasiswa</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Program Saya') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Status program aktif, presensi, laporan, seminar, dan riwayat pendaftaran.</p>
            </div>
            <a class="silat-btn" href="{{ route('check-ins.create') }}"><x-icon name="fa-fingerprint" /> Presensi</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif

            <section class="silat-card overflow-hidden">
                <div class="border-b border-gray-100 bg-white p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :variant="$activeStatusVariant">{{ $activeEnrollment?->status ?: 'Belum ada program' }}</x-badge>
                                @if ($nearestDeadline)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                                        <x-icon name="fa-hourglass-start" /> {{ $nearestDeadline->deadline_date?->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>
                            <h3 class="mt-3 text-xl font-semibold text-gray-900">
                                {{ $activeEnrollment?->internshipPeriod?->display_name ?: 'Belum ada program aktif' }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $activeEnrollment?->studyProgram?->name ?: 'Lengkapi pendaftaran untuk memulai program.' }}
                            </p>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-3 lg:min-w-[520px]">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs font-semibold uppercase text-gray-500">Mitra</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $activeEnrollment?->internshipPlace?->name ?: 'Belum ditentukan' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs font-semibold uppercase text-gray-500">Pembimbing</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $activeEnrollment?->lecturer?->name ?: 'Belum ditentukan' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs font-semibold uppercase text-gray-500">Seminar</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $seminarLabel }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 p-5 md:grid-cols-3 xl:grid-cols-6">
                    <a class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700" href="{{ route('check-ins.create') }}">
                        <x-icon name="fa-fingerprint" /> Presensi
                    </a>
                    <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800 hover:bg-blue-100" href="{{ $reportUrl }}">
                        <x-icon name="fa-book-open" /> Laporan
                    </a>
                    <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-800 hover:bg-indigo-100" href="{{ $reportUrl }}">
                        <x-icon name="fa-person-chalkboard" /> Seminar
                    </a>
                    <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50" href="{{ route('student.relocations.create') }}">
                        <x-icon name="fa-route" /> Pindah Mitra
                    </a>
                    <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50" href="{{ route('student.supervisor-requests.create') }}">
                        <x-icon name="fa-user-pen" /> Pembimbing
                    </a>
                    <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50" href="{{ route('student.enrollments.create') }}">
                        <x-icon name="fa-file-circle-plus" /> Daftar
                    </a>
                </div>
            </section>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="min-h-24 rounded-lg border border-sky-100 bg-sky-50 p-4 text-sky-900">
                    <p class="text-xs font-semibold uppercase text-sky-700">Hari Hadir</p>
                    <p class="mt-2 text-2xl font-bold">{{ number_format($attendanceDays, 0, ',', '.') }}</p>
                </div>
                <div class="min-h-24 rounded-lg border border-rose-100 bg-rose-50 p-4 text-rose-900">
                    <p class="text-xs font-semibold uppercase text-rose-700">Total Sanksi</p>
                    <p class="mt-2 text-2xl font-bold">{{ number_format($sanctionsPoints, 0, ',', '.') }}</p>
                </div>
                <div class="min-h-24 rounded-lg border border-amber-100 bg-amber-50 p-4 text-amber-900">
                    <p class="text-xs font-semibold uppercase text-amber-700">Deadline</p>
                    <p class="mt-2 text-2xl font-bold">{{ $nearestDeadline?->deadline_date?->format('d/m') ?: '-' }}</p>
                </div>
                <div class="min-h-24 rounded-lg border border-blue-100 bg-blue-50 p-4 text-blue-900">
                    <p class="text-xs font-semibold uppercase text-blue-700">Laporan</p>
                    <p class="mt-2 text-2xl font-bold">{{ $reportProgress }}%</p>
                </div>
            </div>

            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Riwayat Pendaftaran</h3>
                        <p class="silat-section-description">Program, mitra, pembimbing, dan aksi utama yang terkait akun Anda.</p>
                    </div>
                    <a class="silat-btn-secondary" href="{{ route('student.enrollments.create') }}"><x-icon name="fa-file-circle-plus" /> Daftar Program</a>
                </div>
                <div class="grid gap-4 p-5">
                    @forelse ($enrollments as $enrollment)
                        @php
                            $statusVariant = match($enrollment->status) {
                                'active' => 'success',
                                'pending_verification', 'draft' => 'warning',
                                'revision_required' => 'warning',
                                'completed' => 'info',
                                'rejected', 'cancelled' => 'danger',
                                default => 'neutral',
                            };
                            $isActiveRow = $activeEnrollment && $activeEnrollment->id === $enrollment->id;
                        @endphp
                        <article class="rounded-lg border {{ $isActiveRow ? 'border-blue-200 bg-blue-50/40' : 'border-gray-200 bg-white' }} p-4">
                            <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr_1fr_auto] lg:items-center">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-gray-900">{{ $enrollment->internshipPeriod?->display_name ?: '-' }}</p>
                                        <x-badge :variant="$statusVariant">{{ $enrollment->status }}</x-badge>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">{{ $enrollment->studyProgram?->name ?: '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase text-gray-500">Mitra</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $enrollment->internshipPlace?->name ?: 'Belum ditentukan' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase text-gray-500">Pembimbing</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $enrollment->lecturer?->name ?: 'Dosen belum ditentukan' }}</p>
                                    <p class="text-xs text-gray-500">{{ $enrollment->field_supervisor ?: 'Pembimbing lapangan belum diisi' }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    @if ($enrollment->status === 'active')
                                        <a class="inline-flex items-center gap-1 rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700" href="{{ route('check-ins.create') }}"><x-icon name="fa-fingerprint" /> Presensi</a>
                                        <a class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50" href="{{ route('student.reports.show', $enrollment) }}"><x-icon name="fa-book-open" /> Laporan</a>
                                    @elseif ($enrollment->status === 'revision_required')
                                        <a class="inline-flex items-center gap-1 rounded-md bg-amber-500 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-600" href="{{ route('student.enrollments.edit', $enrollment) }}"><x-icon name="fa-pen" /> Perbaiki</a>
                                    @else
                                        <a class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50" href="{{ route('student.reports.show', $enrollment) }}"><x-icon name="fa-book-open" /> Laporan</a>
                                    @endif
                                    <a class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50" href="{{ route('student.relocations.create') }}"><x-icon name="fa-route" /> Mitra</a>
                                    <a class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50" href="{{ route('student.supervisor-requests.create') }}"><x-icon name="fa-user-pen" /> Pembimbing</a>
                                </div>
                            </div>
                            @if ($enrollment->status === 'revision_required' && $enrollment->admin_note)
                                <div class="mt-3 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ $enrollment->admin_note }}</div>
                            @endif
                        </article>
                    @empty
                        <x-empty-state title="Belum ada pendaftaran program" description="Mulai pendaftaran untuk mengikuti program MBKM/KP." />
                    @endforelse
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
                <section class="silat-card p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="silat-section-title">Progres Laporan</h3>
                            <p class="silat-section-description">Ringkasan awal kelengkapan laporan mahasiswa.</p>
                        </div>
                        <x-icon name="fa-book-open" class="text-2xl text-blue-600" />
                    </div>
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">Laporan lengkap</span>
                            <span class="font-semibold text-gray-900">{{ $reportProgress }}%</span>
                        </div>
                        <div class="mt-2 h-3 rounded-full bg-gray-100">
                            <div class="h-3 rounded-full bg-blue-600" style="width: {{ $reportProgress }}%"></div>
                        </div>
                        <p class="mt-3 text-sm text-gray-500">{{ $activeEnrollment?->final_report_path ? 'File laporan akhir sudah tersedia.' : 'Upload laporan akhir belum tersedia.' }}</p>
                        <a class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-900" href="{{ $reportUrl }}"><x-icon name="fa-arrow-right" /> Buka laporan</a>
                    </div>
                </section>

                <section class="silat-card p-5" data-browser-permissions>
                    <div>
                        <h3 class="silat-section-title">Izin Akses Perangkat</h3>
                        <p class="silat-section-description">Diperlukan saat melakukan presensi.</p>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lokasi</p>
                            <p class="mt-1 text-base font-semibold text-gray-900" data-permission-status="geolocation">Memeriksa...</p>
                            <button type="button" data-permission-request="geolocation" class="silat-btn mt-3">Izinkan Lokasi</button>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kamera</p>
                            <p class="mt-1 text-base font-semibold text-gray-900" data-permission-status="camera">Memeriksa...</p>
                            <button type="button" data-permission-request="camera" class="silat-btn mt-3">Izinkan Kamera</button>
                        </div>
                    </div>
                </section>
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
                            @php
                                $attendance = $event->attendances->first();
                            @endphp
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
        </div>
    </div>
</x-app-layout>
