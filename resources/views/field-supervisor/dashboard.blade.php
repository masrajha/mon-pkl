@php
    $isTokenAccess = $accessMode === 'token';
    $photoUrl = function ($record): ?string {
        if (! $record) {
            return null;
        }

        if (filled($record->photo_path ?? null)) {
            return \App\Support\PublicStorage::url($record->photo_path);
        }

        return filled($record->source_photo_url ?? null) ? $record->source_photo_url : null;
    };
@endphp

@if ($isTokenAccess)
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Portal Pembimbing Lapangan - SiLAT</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100 text-gray-900 antialiased">
@endif

<div class="{{ $isTokenAccess ? 'py-8' : '' }}">
    <div class="silat-shell space-y-6">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <section class="silat-card p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Pembimbing Lapangan</p>
                    <h1 class="mt-1 text-2xl font-semibold text-gray-900">Portal Pembimbing Lapangan</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Akses {{ $isTokenAccess ? 'melalui token URL' : 'login email' }} untuk {{ $email }}.
                    </p>
                </div>
                <x-badge>{{ $isTokenAccess ? 'Token' : 'Login' }}</x-badge>
            </div>
        </section>

        @forelse ($enrollments as $enrollment)
            @php
                $dailyRows = $dailyRowsByEnrollment[$enrollment->id] ?? collect();
                $assessment = $enrollment->fieldSupervisorAssessment;
                $finalAssessment = $enrollment->finalAssessment;
                $periodConfigurations = app(\App\Services\PeriodConfigurationService::class);
                $assessmentRubric = $periodConfigurations->fieldSupervisorRubric($enrollment->internshipPeriod);
                $assessmentGroups = collect($assessmentRubric)->groupBy('group', preserveKeys: true);
                $institutionSurvey = $periodConfigurations->institutionSurvey($enrollment->internshipPeriod);
                $attendanceScore = $attendanceScoresByEnrollment[$enrollment->id] ?? ['score' => 0, 'present_days' => 0, 'working_days' => 0];
                $attendanceEndsAt = $enrollment->effectiveAttendanceEndsAt();
                $assessmentTimezone = config('monpkl.timezone') ?: 'Asia/Jakarta';
                $attendanceEndsDate = $attendanceEndsAt instanceof \DateTimeInterface
                    ? $attendanceEndsAt->format('Y-m-d')
                    : ($attendanceEndsAt ? \Illuminate\Support\Carbon::parse($attendanceEndsAt, $assessmentTimezone)->toDateString() : null);
                $canAssess = $attendanceEndsDate && $attendanceEndsDate <= \Illuminate\Support\Carbon::today($assessmentTimezone)->toDateString();
                $assessmentRoute = $isTokenAccess
                    ? route('field-supervisor.token.assessment.store', [$requestToken ?? request()->route('token'), $enrollment])
                    : route('field-supervisor.assessment.store', $enrollment);
            @endphp
            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h2 class="silat-section-title">{{ $enrollment->student?->full_name ?: '-' }}</h2>
                        <p class="silat-section-description">
                            {{ $enrollment->student?->npm ?: '-' }} · {{ $enrollment->studyProgram?->name ?: '-' }} · {{ $enrollment->internshipPeriod?->display_name ?: '-' }}
                        </p>
                    </div>
                    <x-badge variant="{{ $enrollment->status === 'active' ? 'success' : 'neutral' }}">{{ $enrollment->status }}</x-badge>
                </div>

                <div class="grid gap-4 p-5 md:grid-cols-3">
                    <div class="silat-stat-card">
                        <p class="silat-stat-label">Mitra</p>
                        <p class="mt-2 font-semibold text-gray-900">{{ $enrollment->internshipPlace?->name ?: '-' }}</p>
                    </div>
                    <div class="silat-stat-card">
                        <p class="silat-stat-label">Dosen Pembimbing</p>
                        <p class="mt-2 font-semibold text-gray-900">{{ $enrollment->lecturer?->name ?: '-' }}</p>
                    </div>
                    <div class="silat-stat-card">
                        <p class="silat-stat-label">Total Catatan</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($dailyRows->count(), 0, ',', '.') }}</p>
                    </div>
                </div>

                <div x-data="{ activeTab: @js($initialTab ?? 'daily') }" class="border-t border-gray-100">
                    <div class="flex flex-col gap-3 border-b border-gray-100 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-md border px-3.5 py-2.5 text-sm font-semibold shadow-sm transition"
                                :class="activeTab === 'daily' ? 'border-green-600 bg-green-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-green-200 hover:bg-green-50 hover:text-green-700'"
                                @click="activeTab = 'daily'"
                            >
                                <x-icon name="fa-clipboard-check" />
                                Validasi Catatan Harian
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-md border px-3.5 py-2.5 text-sm font-semibold shadow-sm transition"
                                :class="activeTab === 'assessment' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700'"
                                @click="activeTab = 'assessment'"
                            >
                                <x-icon name="fa-star-half-stroke" />
                                Penilaian dan Feedback
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge>{{ number_format($dailyRows->count(), 0, ',', '.') }} catatan</x-badge>
                            @if ($assessment)
                                <x-badge variant="success">Nilai {{ number_format((float) $assessment->final_score, 2, ',', '.') }}</x-badge>
                            @else
                                <x-badge variant="{{ $canAssess ? 'warning' : 'neutral' }}">{{ $canAssess ? 'Belum dinilai' : 'Belum dibuka' }}</x-badge>
                            @endif
                        </div>
                    </div>

                    <div x-show="activeTab === 'daily'" x-cloak class="overflow-x-auto">
                    @if (! $isTokenAccess)
                        @php
                            $pendingForgottenRequests = $enrollment->forgottenAttendanceRequests->where('status', 'pending')->values();
                        @endphp
                        @if ($pendingForgottenRequests->isNotEmpty())
                            <div class="border-b border-gray-100 bg-amber-50 px-5 py-4">
                                <h3 class="text-sm font-semibold text-amber-950">Pengajuan Lupa Presensi</h3>
                                <div class="mt-3 space-y-3">
                                    @foreach ($pendingForgottenRequests as $requestItem)
                                        <div class="rounded-lg border border-amber-200 bg-white p-3">
                                            <div class="grid gap-3 lg:grid-cols-[1fr_260px]">
                                                <div class="text-sm">
                                                    <p class="font-semibold text-gray-900">
                                                        {{ $requestItem->action === 'check_out' ? 'Pulang' : 'Masuk' }}
                                                        {{ $requestItem->requested_checked_at?->format('d/m/Y H:i') }}
                                                    </p>
                                                    <p class="mt-1 text-gray-600"><span class="font-medium">Catatan:</span> {{ $requestItem->note }}</p>
                                                    <p class="mt-1 text-gray-600"><span class="font-medium">Alasan:</span> {{ $requestItem->reason }}</p>
                                                    <p class="mt-1 text-xs text-gray-500">Jarak pengajuan: {{ $requestItem->distance_meters !== null ? number_format($requestItem->distance_meters, 0, ',', '.').' m' : '-' }}</p>
                                                    @if ($forgottenPhotoUrl = $photoUrl($requestItem))
                                                        <details class="mt-3 rounded-md border border-amber-100 bg-amber-50/60 p-2">
                                                            <summary class="cursor-pointer text-xs font-semibold text-amber-900">Foto bukti Lupa Presensi</summary>
                                                            <a href="{{ $forgottenPhotoUrl }}" target="_blank" rel="noopener" class="mt-2 block w-fit">
                                                                <img src="{{ $forgottenPhotoUrl }}" alt="Foto bukti Lupa Presensi {{ $requestItem->requested_checked_at?->format('d/m/Y H:i') }}" class="h-28 w-40 rounded-md border border-amber-200 object-cover">
                                                            </a>
                                                        </details>
                                                    @endif
                                                </div>
                                                <div class="space-y-2">
                                                    <form method="POST" action="{{ route('forgotten-attendance-requests.field-supervisor.approve', $requestItem) }}" class="space-y-2">
                                                        @csrf
                                                        <textarea name="review_note" rows="2" class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Catatan persetujuan opsional"></textarea>
                                                        <button class="silat-btn px-3 py-2 text-xs"><x-icon name="fa-check" /> Setujui</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('forgotten-attendance-requests.field-supervisor.reject', $requestItem) }}" class="space-y-2">
                                                        @csrf
                                                        <textarea name="review_note" rows="2" class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Alasan penolakan opsional"></textarea>
                                                        <button class="silat-btn-secondary px-3 py-2 text-xs"><x-icon name="fa-xmark" /> Tolak</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                    @php
                        $bulkValidationFormId = 'bulk-daily-validation-'.$enrollment->id.'-'.($isTokenAccess ? 'token' : 'login');
                        $bulkValidateRoute = $isTokenAccess
                            ? route('field-supervisor.token.daily-logs.bulk-validate', $requestToken ?? request()->route('token'))
                            : route('field-supervisor.daily-logs.bulk-validate', $enrollment);
                        $bulkValidationRows = $dailyRows
                            ->filter(fn (array $row): bool => (bool) ($row['validation_check_in'] ?? null) && ($row['validation_check_in']?->daily_log_status ?: 'pending') === 'pending')
                            ->count();
                    @endphp
                    @if ($bulkValidationRows > 0)
                        <div class="border-b border-gray-100 bg-blue-50 px-5 py-4">
                            <form id="{{ $bulkValidationFormId }}" method="POST" action="{{ $bulkValidateRoute }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                                @csrf
                                <div>
                                    <label for="bulk_daily_validation_note_{{ $enrollment->id }}" class="block text-sm font-medium text-blue-950">Catatan validasi massal</label>
                                    <textarea id="bulk_daily_validation_note_{{ $enrollment->id }}" name="note" rows="2" class="mt-1 block w-full rounded-md border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Catatan opsional untuk semua baris terpilih"></textarea>
                                </div>
                                <button type="submit" class="silat-btn px-4 py-2.5 text-sm">
                                    <x-icon name="fa-check-double" /> Validasi Terpilih
                                </button>
                            </form>
                        </div>
                    @endif
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell w-10">
                                    @if ($bulkValidationRows > 0)
                                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" data-bulk-toggle="{{ $bulkValidationFormId }}" aria-label="Pilih semua catatan harian">
                                    @endif
                                </th>
                                <th class="silat-table-cell">Tanggal</th>
                                <th class="silat-table-cell">Jam</th>
                                <th class="silat-table-cell">Durasi</th>
                                <th class="silat-table-cell">Jarak</th>
                                <th class="silat-table-cell">Rencana / Realisasi</th>
                                <th class="silat-table-cell">Validasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($dailyRows as $row)
                                @php
                                    $validationCheckIn = $row['validation_check_in'] ?? null;
                                    $validateRoute = $validationCheckIn
                                        ? ($isTokenAccess
                                            ? route('field-supervisor.token.daily-logs.validate', [$requestToken ?? request()->route('token'), $validationCheckIn])
                                            : route('field-supervisor.daily-logs.validate', $validationCheckIn))
                                        : null;
                                    $flagRoute = $validationCheckIn
                                        ? ($isTokenAccess
                                            ? route('field-supervisor.token.daily-logs.flag', [$requestToken ?? request()->route('token'), $validationCheckIn])
                                            : route('field-supervisor.daily-logs.flag', $validationCheckIn))
                                        : null;
                                    $dailyLogStatus = $validationCheckIn?->daily_log_status ?: 'pending';
                                @endphp
                                <tr>
                                    <td class="silat-table-cell align-top">
                                        @if ($validationCheckIn && $dailyLogStatus === 'pending')
                                            <input
                                                type="checkbox"
                                                name="check_in_ids[]"
                                                value="{{ $validationCheckIn->id }}"
                                                form="{{ $bulkValidationFormId }}"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                aria-label="Pilih catatan tanggal {{ $row['date']?->format('d/m/Y') ?: '-' }}"
                                            >
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell whitespace-nowrap">{{ $row['date']?->format('d/m/Y') ?: '-' }}</td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        <div>Masuk: {{ $row['check_in']?->checked_at?->format('H:i') ?: '-' }} <x-device-badge :device="\App\Support\DeviceInfo::from($row['check_in']?->device_info)" /></div>
                                        <div>Pulang: {{ $row['check_out']?->checked_at?->format('H:i') ?: '-' }} <x-device-badge :device="\App\Support\DeviceInfo::from($row['check_out']?->device_info)" /></div>
                                    </td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        {{ $row['duration_minutes'] !== null ? floor($row['duration_minutes'] / 60).'j '.($row['duration_minutes'] % 60).'m' : '-' }}
                                    </td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        <div>Masuk: {{ $row['check_in']?->distance_meters !== null ? number_format($row['check_in']->distance_meters, 0, ',', '.').' m' : '-' }}</div>
                                        <div>Pulang: {{ $row['check_out']?->distance_meters !== null ? number_format($row['check_out']->distance_meters, 0, ',', '.').' m' : '-' }}</div>
                                        @if ($row['check_in']?->work_mode === 'wfa' || $row['check_out']?->work_mode === 'wfa')
                                            <div class="mt-1"><x-badge variant="success">WFA</x-badge></div>
                                            <p class="mt-1 max-w-40 whitespace-normal text-xs text-teal-700">
                                                {{ $row['check_in']?->wfaRequest?->planned_location ?: $row['check_out']?->wfaRequest?->planned_location }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell min-w-[360px]">
                                        <p><span class="font-semibold">Rencana:</span> {{ $row['check_in']?->note ?: '-' }}</p>
                                        <p class="mt-2"><span class="font-semibold">Realisasi:</span> {{ $row['check_out']?->note ?: '-' }}</p>
                                        @php
                                            $checkInPhotoUrl = $photoUrl($row['check_in'] ?? null);
                                            $checkOutPhotoUrl = $photoUrl($row['check_out'] ?? null);
                                        @endphp
                                        @if ($checkInPhotoUrl || $checkOutPhotoUrl)
                                            <details class="mt-3 rounded-md border border-gray-200 bg-gray-50 p-2">
                                                <summary class="cursor-pointer text-xs font-semibold text-gray-700">Foto audit presensi</summary>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @if ($checkInPhotoUrl)
                                                        <a href="{{ $checkInPhotoUrl }}" target="_blank" rel="noopener" class="group block">
                                                            <span class="mb-1 block text-xs font-medium text-gray-600">Masuk</span>
                                                            <img src="{{ $checkInPhotoUrl }}" alt="Foto presensi masuk {{ $row['date']?->format('d/m/Y') ?: '' }}" class="h-24 w-32 rounded-md border border-gray-200 object-cover transition group-hover:border-blue-400">
                                                        </a>
                                                    @endif
                                                    @if ($checkOutPhotoUrl)
                                                        <a href="{{ $checkOutPhotoUrl }}" target="_blank" rel="noopener" class="group block">
                                                            <span class="mb-1 block text-xs font-medium text-gray-600">Pulang</span>
                                                            <img src="{{ $checkOutPhotoUrl }}" alt="Foto presensi pulang {{ $row['date']?->format('d/m/Y') ?: '' }}" class="h-24 w-32 rounded-md border border-gray-200 object-cover transition group-hover:border-blue-400">
                                                        </a>
                                                    @endif
                                                </div>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell min-w-[240px]">
                                        @if ($dailyLogStatus === 'validated')
                                            <x-badge variant="success">Tervalidasi</x-badge>
                                            <div class="mt-2 text-xs text-gray-500">
                                                {{ $validationCheckIn->daily_log_validated_at?->format('d/m/Y H:i') }}
                                                <br>{{ $validationCheckIn->daily_log_validated_by_name ?: $validationCheckIn->daily_log_validated_by_email }}
                                            </div>
                                            @if ($validationCheckIn->daily_log_validation_note)
                                                <p class="mt-2 text-xs text-gray-600">{{ $validationCheckIn->daily_log_validation_note }}</p>
                                            @endif
                                            @if ($flagRoute)
                                                <details class="mt-3 rounded-md border border-red-100 bg-red-50 p-2">
                                                    <summary class="cursor-pointer text-xs font-semibold text-red-800">Tandai bermasalah</summary>
                                                    <form method="POST" action="{{ $flagRoute }}" class="mt-2 space-y-2">
                                                        @csrf
                                                        <textarea name="reason" rows="3" class="block w-full rounded-md border-red-200 text-xs shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Alasan wajib: lokasi/foto/catatan tidak valid, butuh klarifikasi..." required></textarea>
                                                        <button type="submit" class="silat-btn-secondary px-3 py-2 text-xs text-red-700"><x-icon name="fa-triangle-exclamation" /> Tandai Bermasalah</button>
                                                    </form>
                                                </details>
                                            @endif
                                        @elseif ($dailyLogStatus === 'flagged')
                                            <x-badge variant="danger">Bermasalah</x-badge>
                                            <div class="mt-2 text-xs text-gray-500">
                                                {{ $validationCheckIn->daily_log_flagged_at?->format('d/m/Y H:i') }}
                                                <br>{{ $validationCheckIn->daily_log_flagged_by_name ?: $validationCheckIn->daily_log_flagged_by_email }}
                                            </div>
                                            <div class="mt-2 rounded-md border border-red-100 bg-red-50 p-2 text-xs text-red-900">
                                                <span class="font-semibold">Alasan:</span> {{ $validationCheckIn->daily_log_flag_reason }}
                                            </div>
                                            @if ($validationCheckIn->daily_log_student_clarification)
                                                <div class="mt-2 rounded-md border border-blue-100 bg-blue-50 p-2 text-xs text-blue-950">
                                                    <span class="font-semibold">Klarifikasi mahasiswa:</span> {{ $validationCheckIn->daily_log_student_clarification }}
                                                    <div class="mt-1 text-blue-700">{{ $validationCheckIn->daily_log_clarified_at?->format('d/m/Y H:i') }}</div>
                                                </div>
                                            @else
                                                <p class="mt-2 text-xs text-amber-700">Menunggu klarifikasi mahasiswa.</p>
                                            @endif
                                            @if ($validateRoute)
                                                <form method="POST" action="{{ $validateRoute }}" class="mt-3 space-y-2">
                                                    @csrf
                                                    <textarea name="note" rows="2" class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Catatan penyelesaian/validasi"></textarea>
                                                    <button type="submit" class="silat-btn px-3 py-2 text-xs"><x-icon name="fa-check" /> Validasi</button>
                                                </form>
                                            @endif
                                        @elseif ($validateRoute)
                                            <div class="space-y-3">
                                                <form method="POST" action="{{ $validateRoute }}" class="space-y-2">
                                                    @csrf
                                                    <textarea name="note" rows="2" class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Catatan validasi opsional"></textarea>
                                                    <button type="submit" class="silat-btn px-3 py-2 text-xs"><x-icon name="fa-check" /> Validasi</button>
                                                </form>
                                                @if ($flagRoute)
                                                    <form method="POST" action="{{ $flagRoute }}" class="space-y-2 rounded-md border border-red-100 bg-red-50 p-2">
                                                        @csrf
                                                        <textarea name="reason" rows="2" class="block w-full rounded-md border-red-200 text-xs shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Alasan jika bermasalah" required></textarea>
                                                        <button type="submit" class="silat-btn-secondary px-3 py-2 text-xs text-red-700"><x-icon name="fa-triangle-exclamation" /> Tandai Bermasalah</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @else
                                            <x-badge variant="neutral">Belum lengkap</x-badge>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="silat-table-cell">
                                        <x-empty-state title="Belum ada catatan harian" icon="fa-clipboard" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                    <div x-show="activeTab === 'assessment'" x-cloak>
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Penilaian Program</h3>
                        <p class="silat-section-description">Form nilai pembimbing lapangan diisi setelah batas akhir presensi/turun lapang.</p>
                    </div>
                    @if ($assessment)
                        <x-badge variant="success">Nilai {{ number_format((float) $assessment->final_score, 2, ',', '.') }}</x-badge>
                    @else
                        <x-badge variant="{{ $canAssess ? 'warning' : 'neutral' }}">{{ $canAssess ? 'Belum dinilai' : 'Belum dibuka' }}</x-badge>
                    @endif
                </div>

                <div class="p-4 sm:p-5">
                    @if ($finalAssessment)
                        <x-alert variant="info">
                            Nilai pembimbing lapangan sudah terkunci karena nilai akhir telah difinalisasi.
                        </x-alert>
                    @elseif (! $canAssess)
                        <x-alert variant="warning">
                            Penilaian baru dapat dilakukan mulai tanggal akhir presensi/turun lapang pukul 00:00.
                            Batas akhir saat ini: {{ $attendanceEndsAt ? \Illuminate\Support\Carbon::parse($attendanceEndsAt)->format('d/m/Y') : 'belum ditentukan' }}.
                        </x-alert>
                    @else
                        <form method="POST" action="{{ $assessmentRoute }}" class="space-y-4">
                            @csrf
                            <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_220px]">
                                <div class="space-y-2.5">
                                    @foreach ($assessmentGroups as $group => $items)
                                        <fieldset class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                            <legend class="sr-only">{{ $group }}</legend>
                                            <div class="border-b border-gray-100 bg-gray-50 px-4 py-2.5">
                                                <h4 class="text-sm font-semibold text-gray-900">{{ $group }}</h4>
                                            </div>
                                            <div class="divide-y divide-gray-100">
                                                @foreach ($items as $key => $item)
                                                    @php
                                                        $isAttendanceScore = $key === 'attendance';
                                                        $scoreValue = $isAttendanceScore
                                                            ? number_format((float) ($attendanceScore['score'] ?? 0), 2, '.', '')
                                                            : old('scores.'.$key, data_get($assessment?->scores, $key.'.score'));
                                                    @endphp
                                                    <div class="grid gap-2 px-4 py-2 sm:grid-cols-[minmax(0,1fr)_8.5rem] sm:items-center">
                                                        <label for="field_supervisor_score_{{ $enrollment->id }}_{{ $key }}" class="text-sm font-medium text-gray-800">
                                                            {{ $item['label'] }}
                                                            @if ($isAttendanceScore)
                                                                <span class="mt-0.5 block text-xs font-normal text-gray-500">
                                                                    {{ number_format((int) ($attendanceScore['present_days'] ?? 0), 0, ',', '.') }}/{{ number_format((int) ($attendanceScore['working_days'] ?? 0), 0, ',', '.') }} hari kerja
                                                                </span>
                                                            @endif
                                                        </label>
                                                        <input
                                                            id="field_supervisor_score_{{ $enrollment->id }}_{{ $key }}"
                                                            name="scores[{{ $key }}]"
                                                            type="number"
                                                            min="0"
                                                            max="100"
                                                            step="0.01"
                                                            inputmode="decimal"
                                                            class="silat-field h-9 w-full text-right text-sm font-semibold tabular-nums {{ $isAttendanceScore ? 'bg-gray-100 text-gray-600' : '' }}"
                                                            value="{{ $scoreValue }}"
                                                            @readonly($isAttendanceScore)
                                                            @required(! $isAttendanceScore)
                                                        />
                                                    </div>
                                                @endforeach
                                            </div>
                                        </fieldset>
                                    @endforeach
                                </div>

                                <aside class="rounded-lg border border-blue-100 bg-blue-50 p-3 xl:sticky xl:top-20 xl:self-start">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Ringkasan</p>
                                    <div class="mt-2 rounded-lg bg-white p-3 shadow-sm">
                                        <p class="text-sm text-gray-500">Rata-rata Nilai</p>
                                        <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-900">
                                            {{ $assessment ? number_format((float) $assessment->final_score, 2, ',', '.') : '-' }}
                                        </p>
                                    </div>
                                    <div class="mt-2 rounded-lg bg-white/70 p-3 text-xs leading-5 text-blue-950">
                                        <div class="flex items-center justify-between gap-3">
                                            <span>Hari hadir efektif</span>
                                            <span class="font-semibold tabular-nums">{{ number_format((int) ($attendanceScore['present_days'] ?? 0), 0, ',', '.') }}</span>
                                        </div>
                                        <div class="mt-1 flex items-center justify-between gap-3">
                                            <span>Hari kerja efektif</span>
                                            <span class="font-semibold tabular-nums">{{ number_format((int) ($attendanceScore['working_days'] ?? 0), 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </aside>
                            </div>

                            <div class="grid gap-4 border-t border-gray-100 pt-4 md:grid-cols-2">
                                <div>
                                    <x-input-label for="field_supervisor_student_general_note_{{ $enrollment->id }}" value="Catatan Umum untuk Mahasiswa" />
                                    <x-textarea-input id="field_supervisor_student_general_note_{{ $enrollment->id }}" name="student_general_note" rows="3" class="mt-1.5 block w-full text-sm leading-6">{{ old('student_general_note', $assessment?->student_general_note ?? $assessment?->note) }}</x-textarea-input>
                                </div>
                                <div>
                                    <x-input-label for="field_supervisor_student_recommendation_{{ $enrollment->id }}" value="Rekomendasi untuk Mahasiswa" />
                                    <x-textarea-input id="field_supervisor_student_recommendation_{{ $enrollment->id }}" name="student_recommendation" rows="3" class="mt-1.5 block w-full text-sm leading-6">{{ old('student_recommendation', $assessment?->student_recommendation) }}</x-textarea-input>
                                </div>
                            </div>

                            <section class="border-t border-gray-100 pt-4">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                    <h4 class="text-sm font-semibold text-gray-900">Feedback untuk Institusi / Program Studi</h4>
                                    <p class="mt-1 text-sm text-gray-500">Masukan ini ditujukan untuk admin/koordinator/prodi dan tidak menjadi bagian nilai mahasiswa.</p>
                                    </div>
                                </div>
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    @foreach ($institutionSurvey as $key => $question)
                                        <div>
                                            <x-input-label for="institution_feedback_{{ $enrollment->id }}_{{ $key }}" :value="$question['label']" />
                                            <x-select-input id="institution_feedback_{{ $enrollment->id }}_{{ $key }}" name="institution_feedback[{{ $key }}]" class="mt-1.5 block h-9 w-full text-sm" required>
                                                <option value="">Pilih jawaban</option>
                                                @foreach (($question['options'] ?? []) as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('institution_feedback.'.$key, data_get($assessment?->institution_feedback, $key.'.value')) === $value)>{{ $label }}</option>
                                                @endforeach
                                            </x-select-input>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <x-input-label for="field_supervisor_institution_note_{{ $enrollment->id }}" value="Catatan Umum untuk Prodi/Jurusan" />
                                    <x-textarea-input id="field_supervisor_institution_note_{{ $enrollment->id }}" name="institution_note" rows="3" class="mt-1.5 block w-full text-sm leading-6">{{ old('institution_note', $assessment?->institution_note) }}</x-textarea-input>
                                </div>
                            </section>

                            <div class="border-t border-gray-100 pt-4">
                                <x-input-label for="field_supervisor_assessment_note_{{ $enrollment->id }}" value="Catatan Internal Penilaian" />
                                <x-textarea-input id="field_supervisor_assessment_note_{{ $enrollment->id }}" name="note" rows="2" class="mt-1.5 block w-full text-sm leading-6">{{ old('note', $assessment?->note) }}</x-textarea-input>
                            </div>
                            @if ($assessment)
                                <p class="text-sm text-gray-500">Terakhir disimpan {{ $assessment->assessed_at?->format('d/m/Y H:i') }} oleh {{ $assessment->assessed_by_name ?: $assessment->assessed_by_email }}.</p>
                            @endif
                            <div class="flex justify-end border-t border-gray-100 pt-4">
                                <x-primary-button class="w-full sm:w-auto"><x-icon name="fa-floppy-disk" /> Simpan Nilai Program</x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
                    </div>
                </div>
            </section>
        @empty
            <x-empty-state title="Tidak ada mahasiswa terkait" description="Email ini belum terhubung dengan data pembimbing lapangan pada enrollment aktif." icon="fa-user-lock" />
        @endforelse
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-bulk-toggle]').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const formId = toggle.dataset.bulkToggle;

                document.querySelectorAll(`input[type="checkbox"][form="${formId}"][name="check_in_ids[]"]`).forEach((checkbox) => {
                    checkbox.checked = toggle.checked;
                });
            });
        });
    });
</script>

@if ($isTokenAccess)
    </body>
    </html>
@endif
