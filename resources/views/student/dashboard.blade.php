<x-app-layout>
    @php
        $statusVariant = fn (?string $status): string => match($status) {
            'active' => 'success',
            'pending_verification', 'draft', 'revision_required' => 'warning',
            'completed' => 'info',
            'period_inactive', 'period_unavailable' => 'neutral',
            'rejected', 'cancelled' => 'danger',
            default => 'neutral',
        };

        $statusLabel = fn (?string $status): string => match($status) {
            'pending_verification' => 'Menunggu Verifikasi',
            'revision_required' => 'Perlu Revisi',
            'active' => 'Aktif',
            'completed' => 'Selesai',
            'period_inactive' => 'Periode Nonaktif',
            'period_unavailable' => 'Periode Tidak Tersedia',
            'cancelled' => 'Dibatalkan',
            'rejected' => 'Ditolak',
            'draft' => 'Draft',
            default => $status ?: 'Belum ada',
        };

        $effectiveStatus = function ($enrollment): string {
            $periodIsActive = (bool) $enrollment->internshipPeriod?->is_active;

            if ($enrollment->status === 'active') {
                return $periodIsActive ? 'active' : 'period_inactive';
            }

            if (! $periodIsActive && in_array($enrollment->status, ['draft', 'pending_verification', 'revision_required'], true)) {
                return 'period_unavailable';
            }

            return $enrollment->status;
        };

        $latestLogs = collect()
            ->merge($enrollments->map(fn ($enrollment) => [
                'date' => $enrollment->updated_at,
                'title' => 'Status program: '.$statusLabel($effectiveStatus($enrollment)),
                'description' => $enrollment->internshipPeriod?->display_name.' - '.($enrollment->internshipPlace?->name ?: 'Mitra belum ditentukan'),
                'icon' => 'fa-clipboard-list',
            ]))
            ->merge($proposals->map(fn ($proposal) => [
                'date' => $proposal->updated_at,
                'title' => 'Usulan mitra: '.Str::headline($proposal->status),
                'description' => $proposal->name,
                'icon' => 'fa-building-circle-arrow-right',
            ]))
            ->merge($relocationRequests->map(fn ($item) => [
                'date' => $item->updated_at,
                'title' => 'Pindah mitra: '.Str::headline($item->status),
                'description' => ($item->currentPlace?->name ?: '-').' ke '.($item->newPlace?->name ?: '-'),
                'icon' => 'fa-route',
            ]))
            ->merge($supervisorChangeRequests->map(fn ($item) => [
                'date' => $item->updated_at,
                'title' => 'Perubahan pembimbing: '.Str::headline($item->status),
                'description' => $item->requestedLecturer?->name ?: ($item->requested_field_supervisor ?: 'Data pembimbing'),
                'icon' => 'fa-user-pen',
            ]))
            ->sortByDesc('date')
            ->take(6)
            ->values();
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Mahasiswa</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Ringkasan Program') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Pilih program untuk membuka detail pelaksanaan, presensi, laporan, seminar, dan penyelesaian.</p>
            </div>
            <section class="w-full rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:w-[360px]" data-browser-permissions>
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Izin Akses Perangkat</p>
                        <p class="text-xs text-gray-500">Diperlukan saat presensi.</p>
                    </div>
                    <x-icon name="fa-mobile-screen-button" class="text-blue-600" />
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button type="button" data-permission-request="geolocation" class="rounded-md border border-gray-200 px-3 py-2 text-left text-xs hover:bg-gray-50">
                        <span class="block font-semibold text-gray-900">Lokasi</span>
                        <span class="text-gray-500" data-permission-status="geolocation">Memeriksa...</span>
                    </button>
                    <button type="button" data-permission-request="camera" class="rounded-md border border-gray-200 px-3 py-2 text-left text-xs hover:bg-gray-50">
                        <span class="block font-semibold text-gray-900">Kamera</span>
                        <span class="text-gray-500" data-permission-status="camera">Memeriksa...</span>
                    </button>
                </div>
            </section>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Program yang Diikuti</h3>
                    <p class="text-sm text-gray-500">Card menampilkan status terakhir tiap enrollment.</p>
                </div>
                <a class="silat-btn" href="{{ route('student.enrollments.create') }}"><x-icon name="fa-file-circle-plus" /> Daftar Program</a>
            </div>

            @include('partials.important-deadlines', [
                'deadlines' => $importantDeadlines,
                'title' => 'Deadline Program Saya',
                'description' => 'Deadline dalam 7 hari ke depan dari program yang Anda ikuti, atau deadline terdekat berikutnya.',
            ])

            <div class="grid gap-5 xl:grid-cols-2">
                @forelse ($enrollments as $enrollment)
                    @php
                        $enrollmentEffectiveStatus = $effectiveStatus($enrollment);
                        $isOperationalActive = $enrollmentEffectiveStatus === 'active';
                        $attendanceDaysForCard = $enrollment->checkIns
                            ->groupBy(fn ($checkIn) => $checkIn->checked_at?->toDateString())
                            ->filter(fn ($items) => $items->contains('action', 'check_in') && $items->contains('action', 'check_out'))
                            ->count();
                        $latestSeminar = $enrollment->seminarRequests->sortByDesc('id')->first();
                        $nearest = $isOperationalActive
                            ? $enrollment->internshipPeriod?->deadlines
                                ?->filter(fn ($deadline) => $deadline->deadline_date?->isFuture() || $deadline->deadline_date?->isToday())
                                ->sortBy('deadline_date')
                                ->first()
                            : null;
                    @endphp
                    <article class="silat-card overflow-hidden">
                        <div class="border-b border-gray-100 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-badge :variant="$statusVariant($enrollmentEffectiveStatus)">{{ $statusLabel($enrollmentEffectiveStatus) }}</x-badge>
                                        @if ($nearest)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                                                <x-icon name="fa-hourglass-start" /> {{ $nearest->deadline_date?->format('d/m') }}
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="mt-3 text-lg font-semibold text-gray-950">{{ $enrollment->internshipPeriod?->display_name ?: '-' }}</h4>
                                    <p class="mt-1 text-sm text-gray-500">{{ $enrollment->studyProgram?->name ?: '-' }}</p>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <p>{{ $enrollment->internshipPeriod?->program?->name ?: 'Program' }}</p>
                                    <p>{{ $enrollment->updated_at?->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 p-5 sm:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs font-semibold uppercase text-gray-500">Mitra</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $enrollment->internshipPlace?->name ?: 'Belum ditentukan' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs font-semibold uppercase text-gray-500">Dosen Pembimbing</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $enrollment->lecturer?->name ?: 'Belum ditentukan' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs font-semibold uppercase text-gray-500">Presensi</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ number_format($attendanceDaysForCard, 0, ',', '.') }} hari hadir</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs font-semibold uppercase text-gray-500">Seminar</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $latestSeminar ? Str::headline($latestSeminar->status) : 'Belum diajukan' }}</p>
                            </div>
                        </div>

                        @if ($enrollment->status === 'revision_required' && $enrollment->admin_note)
                            <div class="mx-5 mb-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ $enrollment->admin_note }}</div>
                        @endif

                        <div class="flex flex-wrap gap-2 border-t border-gray-100 p-5">
                            <a class="silat-btn" href="{{ route('student.reports.show', $enrollment) }}"><x-icon name="fa-arrow-right-to-bracket" /> Detail</a>
                            @if ($isOperationalActive)
                                <a class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-5 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2" href="{{ route('check-ins.create', ['enrollment' => $enrollment->id]) }}"><x-icon name="fa-fingerprint" /> Presensi</a>
                            @endif
                            @if ($enrollment->status === 'revision_required')
                                <a class="silat-btn-secondary" href="{{ route('student.enrollments.edit', $enrollment) }}"><x-icon name="fa-pen" /> Perbaiki</a>
                            @endif
                        </div>
                    </article>
                @empty
                    <section class="silat-card p-8 xl:col-span-2">
                        <x-empty-state title="Belum ada program" description="Mulai pendaftaran untuk mengikuti program MBKM/KP." icon="fa-clipboard-list" />
                        <div class="mt-5 text-center">
                            <a class="silat-btn" href="{{ route('student.enrollments.create') }}"><x-icon name="fa-file-circle-plus" /> Daftar Program</a>
                        </div>
                    </section>
                @endforelse
            </div>

            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Log Aktivitas Terbaru</h3>
                        <p class="silat-section-description">Riwayat pendaftaran diringkas sebagai log, bukan tabel besar.</p>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($latestLogs as $log)
                        <div class="flex gap-4 p-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                <x-icon :name="$log['icon']" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900">{{ $log['title'] }}</p>
                                <p class="text-sm text-gray-500">{{ $log['description'] }}</p>
                            </div>
                            <p class="shrink-0 text-xs text-gray-400">{{ $log['date']?->format('d/m/Y H:i') }}</p>
                        </div>
                    @empty
                        <div class="p-5">
                            <x-empty-state title="Belum ada log aktivitas" icon="fa-clock-rotate-left" />
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
