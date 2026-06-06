<x-app-layout>
    @php
        $missingPrintData = collect([
            'Dosen pembimbing' => ! $enrollment->lecturer,
            'Pembimbing lapangan' => blank($enrollment->field_supervisor),
            'Mitra' => ! $enrollment->internshipPlace,
            'Koordinat mitra' => ! $enrollment->internshipPlace?->latitude || ! $enrollment->internshipPlace?->longitude,
        ])->filter()->keys();
        $pendingSupervisorRequest = $enrollment->supervisorChangeRequests->firstWhere('status', 'pending');
        $progressStatusLabels = [
            'pending' => 'Menunggu Review',
            'approved' => 'Disetujui',
            'revision_required' => 'Perlu Revisi',
            'revision' => 'Perlu Revisi',
            'rejected' => 'Ditolak',
        ];
        $progressStatusVariants = [
            'approved' => 'success',
            'revision_required' => 'warning',
            'revision' => 'warning',
            'rejected' => 'danger',
        ];
        $seminarStatusVariants = [
            'lecturer_approved' => 'success',
            'manual_acc_approved' => 'success',
            'scheduled' => 'info',
            'completed' => 'success',
            'waiting_lecturer_approval' => 'warning',
            'waiting_manual_acc_validation' => 'warning',
            'waiting_assessment_validation' => 'warning',
            'assessment_revision_required' => 'warning',
            'revision_required' => 'warning',
            'rejected' => 'danger',
            'cancelled' => 'neutral',
        ];
        $latestSeminarRequest = $enrollment->seminarRequests->first();
        $guidanceItems = [
            ['label' => 'Dosen pembimbing', 'done' => (bool) $enrollment->lecturer],
            ['label' => 'Pembimbing lapangan', 'done' => filled($enrollment->field_supervisor)],
            ['label' => 'Mitra dan koordinat', 'done' => (bool) ($enrollment->internshipPlace?->latitude && $enrollment->internshipPlace?->longitude)],
            ['label' => 'Presensi', 'done' => $enrollment->checkIns->isNotEmpty()],
            ['label' => 'Validasi catatan Pembimbing Lapangan', 'done' => ($dailyLogValidationSummary['total'] ?? 0) > 0 && ($dailyLogValidationSummary['pending'] ?? 0) === 0],
            ['label' => 'Nilai Pembimbing Lapangan', 'done' => (bool) $enrollment->fieldSupervisorAssessment],
            ['label' => 'Laporan lengkap', 'done' => $progressByType->has('full_report')],
            ['label' => 'Seminar', 'done' => $latestSeminarRequest?->status === 'completed'],
            ['label' => 'Hardcopy', 'done' => $hardcopyProgress?->status === 'approved'],
        ];
        $guidanceProgress = (int) round(collect($guidanceItems)->where('done', true)->count() / count($guidanceItems) * 100);
        $tabs = [
            'detail' => ['label' => 'Detail Program', 'icon' => 'fa-circle-info'],
            'pembekalan' => ['label' => 'Pembekalan', 'icon' => 'fa-users-line'],
            'presensi' => ['label' => 'Presensi & Catatan', 'icon' => 'fa-fingerprint'],
            'pelaporan' => ['label' => 'Pelaporan', 'icon' => 'fa-file-lines'],
            'seminar' => ['label' => 'Seminar & Penilaian', 'icon' => 'fa-person-chalkboard'],
            'penyelesaian' => ['label' => 'Penyelesaian', 'icon' => 'fa-flag-checkered'],
        ];
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Detail Program</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ $enrollment->internshipPeriod?->display_name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $enrollment->student?->npm }} - {{ $enrollment->studyProgram?->name }} - {{ $enrollment->internshipPlace?->name ?: 'Mitra belum ditentukan' }}</p>
            </div>
            <a class="silat-btn-secondary" href="{{ route('student.dashboard') }}"><x-icon name="fa-arrow-left" /> Ringkasan Program</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6" x-data="{ tab: @js($activeTab) }">
            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif

            @if ($missingPrintData->isNotEmpty())
                <x-alert variant="warning">
                    Data belum lengkap untuk cetak laporan: {{ $missingPrintData->implode(', ') }}.
                    @if ($pendingSupervisorRequest)
                        <span class="mt-1 block">Permohonan perubahan pembimbing sedang menunggu persetujuan.</span>
                    @elseif (! $canRequestSupervisorChange)
                        <span class="mt-1 block">Pengajuan perubahan pembimbing untuk periode ini sedang ditutup.</span>
                    @else
                        <a class="mt-2 inline-flex font-semibold text-amber-900 underline" href="{{ route('student.supervisor-requests.create', ['enrollment_id' => $enrollment->id]) }}">Ajukan pelengkapan data pembimbing</a>
                    @endif
                </x-alert>
            @endif

            <section class="silat-card p-4">
                <div class="flex flex-wrap gap-2">
                    @foreach ($tabs as $key => $item)
                        <button type="button" @click="tab = '{{ $key }}'" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-semibold" :class="tab === '{{ $key }}' ? 'border-blue-700 bg-blue-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                            <x-icon :name="$item['icon']" class="w-4" />
                            {{ $item['label'] }}
                        </button>
                    @endforeach
                </div>
            </section>

            <section x-show="tab === 'detail'" x-cloak class="space-y-6">
                <div class="grid gap-4 lg:grid-cols-[1fr_0.9fr]">
                    <section class="silat-card">
                        <div class="silat-section-header">
                            <div>
                                <h3 class="silat-section-title">Ringkasan Pelaksanaan</h3>
                                <p class="silat-section-description">Status umum enrollment dan kelengkapan proses.</p>
                            </div>
                            <x-badge>{{ $enrollment->status }}</x-badge>
                        </div>
                        <div class="grid gap-4 p-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700">Kelengkapan</span>
                                    <span class="font-semibold text-gray-900">{{ $guidanceProgress }}%</span>
                                </div>
                                <div class="mt-2 h-3 rounded-full bg-gray-100"><div class="h-3 rounded-full bg-blue-600" style="width: {{ $guidanceProgress }}%"></div></div>
                            </div>
                            @foreach ($guidanceItems as $item)
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-gray-900">{{ $item['label'] }}</p>
                                        <x-badge :variant="$item['done'] ? 'success' : 'neutral'">{{ $item['done'] ? 'OK' : 'Belum' }}</x-badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="silat-card">
                        <div class="silat-section-header"><div><h3 class="silat-section-title">Data Program</h3><p class="silat-section-description">Data utama peserta program.</p></div></div>
                        <div class="grid gap-3 p-5">
                            @foreach ([
                                'Program/periode' => $enrollment->internshipPeriod?->display_name,
                                'Mitra' => $enrollment->internshipPlace?->name ?: 'Belum ditentukan',
                                'Dosen pembimbing' => $enrollment->lecturer?->name ?: 'Belum ditentukan',
                                'Pembimbing lapangan' => $enrollment->field_supervisor ?: 'Belum diisi',
                                'Email pembimbing lapangan' => $enrollment->field_supervisor_email ?: 'Belum diisi',
                                'Total sanksi' => number_format($enrollment->total_sanctions_points ?? 0, 0, ',', '.').' poin',
                            ] as $label => $value)
                                <div class="rounded-lg border border-gray-200 bg-white p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $value }}</p>
                                </div>
                            @endforeach
                            <div class="flex flex-wrap gap-2 pt-2">
                                @if ($canRequestRelocation)
                                    <a class="silat-btn-secondary" href="{{ route('student.relocations.create', ['enrollment_id' => $enrollment->id]) }}"><x-icon name="fa-route" /> Ajukan Pindah Mitra</a>
                                @endif
                                @if ($canRequestSupervisorChange)
                                    <a class="silat-btn-secondary" href="{{ route('student.supervisor-requests.create', ['enrollment_id' => $enrollment->id]) }}"><x-icon name="fa-user-pen" /> Perubahan Pembimbing</a>
                                @endif
                            </div>
                        </div>
                    </section>
                </div>

                <section class="silat-card">
                    <div class="silat-section-header"><div><h3 class="silat-section-title">Deadline Periode</h3><p class="silat-section-description">Aturan tanggal dan model sanksi periode.</p></div></div>
                    <div class="overflow-x-auto">
                        <table class="silat-table">
                            <thead class="silat-table-head"><tr><th class="silat-table-cell">Jenis</th><th class="silat-table-cell">Tanggal</th><th class="silat-table-cell">Sanksi</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($enrollment->internshipPeriod?->deadlines ?? [] as $deadline)
                                    <tr><td class="silat-table-cell">{{ $deadlineTypeLabels[$deadline->deadline_type] ?? Str::headline($deadline->deadline_type) }}</td><td class="silat-table-cell">{{ $deadline->deadline_date?->format('d/m/Y') }}</td><td class="silat-table-cell">{{ $deadline->penalty_points }} poin {{ $deadline->is_fixed_penalty ? 'tetap' : 'per hari' }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="silat-table-cell"><x-empty-state title="Belum ada deadline periode" icon="fa-hourglass-start" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>

            <section x-show="tab === 'pembekalan'" x-cloak class="silat-card">
                <div class="silat-section-header"><div><h3 class="silat-section-title">Pembekalan</h3><p class="silat-section-description">Presensi pembekalan untuk program periode ini.</p></div></div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    @forelse ($orientationEvents as $event)
                        @php $attendance = $event->attendances->first(); @endphp
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $event->name }}</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ $event->location_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $event->starts_at?->format('d/m/Y H:i') ?: '-' }} s.d. {{ $event->ends_at?->format('d/m/Y H:i') ?: '-' }}</p>
                                </div>
                                <x-badge :variant="$attendance ? 'success' : 'warning'">{{ $attendance ? 'Sudah hadir' : 'Belum hadir' }}</x-badge>
                            </div>
                            <div class="mt-4">
                                @if ($attendance)
                                    <p class="text-sm text-green-700">Presensi tercatat {{ $attendance->checked_at?->format('d/m/Y H:i') }}.</p>
                                @else
                                    <a class="silat-btn" href="{{ route('student.orientation-attendances.create', $event) }}"><x-icon name="fa-fingerprint" /> Presensi Pembekalan</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="md:col-span-2"><x-empty-state title="Belum ada pembekalan aktif" icon="fa-users-line" /></div>
                    @endforelse
                </div>
            </section>

            <section x-show="tab === 'presensi'" x-cloak class="space-y-6">
                <section class="silat-card">
                    <div class="silat-section-header">
                        <div><h3 class="silat-section-title">Catatan Harian</h3><p class="silat-section-description">Rencana dari presensi masuk, realisasi dari presensi pulang.</p></div>
                        <div class="flex flex-wrap gap-2">
                            <a class="silat-btn-secondary" href="{{ route('student.reports.daily-logs.print', $enrollment) }}" target="_blank"><x-icon name="fa-print" /> Cetak Catatan Harian</a>
                            @if ($missingPrintData->isEmpty())
                                <a class="silat-btn" href="{{ route('student.reports.print', $enrollment) }}" target="_blank"><x-icon name="fa-chart-line" /> Cetak Laporan Presensi</a>
                            @else
                                <button type="button" class="silat-btn opacity-60" disabled title="Data belum lengkap: {{ $missingPrintData->implode(', ') }}"><x-icon name="fa-chart-line" /> Cetak Laporan Presensi</button>
                            @endif
                        </div>
                    </div>
                    <div class="grid gap-3 px-5 pt-5 sm:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Catatan</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($dailyLogValidationSummary['total'] ?? 0, 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-lg border border-green-100 bg-green-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-green-700">Tervalidasi Pembimbing Lapangan</p>
                            <p class="mt-1 text-xl font-bold text-green-900">{{ number_format($dailyLogValidationSummary['validated'] ?? 0, 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-lg border border-amber-100 bg-amber-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Menunggu Pembimbing Lapangan</p>
                            <p class="mt-1 text-xl font-bold text-amber-900">{{ number_format($dailyLogValidationSummary['pending'] ?? 0, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto p-5">
                        <table class="silat-table">
                            <thead class="silat-table-head"><tr><th class="silat-table-cell">Tanggal</th><th class="silat-table-cell">Jam</th><th class="silat-table-cell">Jarak</th><th class="silat-table-cell">Catatan</th><th class="silat-table-cell">Validasi Pembimbing Lapangan</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($dailyActivityRows as $row)
                                    @php $validationCheckIn = $row['validation_check_in'] ?? null; @endphp
                                    <tr>
                                        <td class="silat-table-cell whitespace-nowrap">{{ $row['date']?->translatedFormat('l, d M Y') }}</td>
                                        <td class="silat-table-cell whitespace-nowrap"><div>Masuk: {{ $row['check_in']?->checked_at?->format('H:i:s') ?: '-' }}</div><div>Pulang: {{ $row['check_out']?->checked_at?->format('H:i:s') ?: '-' }}</div><div>Durasi: {{ $row['duration_minutes'] !== null ? number_format($row['duration_minutes'] / 60, 2, ',', '.') : '-' }}</div></td>
                                        <td class="silat-table-cell whitespace-nowrap"><div>Masuk: {{ $row['check_in']?->distance_meters !== null ? number_format($row['check_in']->distance_meters, 2, ',', '.') : '-' }}</div><div>Pulang: {{ $row['check_out']?->distance_meters !== null ? number_format($row['check_out']->distance_meters, 2, ',', '.') : '-' }}</div></td>
                                        <td class="silat-table-cell min-w-[420px]"><p><strong>Rencana:</strong> {{ $row['check_in']?->note ?: '-' }}</p><p class="mt-3"><strong>Realisasi:</strong> {{ $row['check_out']?->note ?: '-' }}</p></td>
                                        <td class="silat-table-cell min-w-[180px]">
                                            @if ($validationCheckIn?->daily_log_validated_at)
                                                <x-badge variant="success">Tervalidasi</x-badge>
                                                <div class="mt-2 text-xs text-gray-500">
                                                    {{ $validationCheckIn->daily_log_validated_at?->format('d/m/Y H:i') }}
                                                    <br>{{ $validationCheckIn->daily_log_validated_by_name ?: $validationCheckIn->daily_log_validated_by_email }}
                                                </div>
                                                @if ($validationCheckIn->daily_log_validation_note)
                                                    <p class="mt-2 text-xs text-gray-600">{{ $validationCheckIn->daily_log_validation_note }}</p>
                                                @endif
                                            @else
                                                <x-badge variant="warning">Menunggu Pembimbing Lapangan</x-badge>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="silat-table-cell"><x-empty-state title="Belum ada catatan harian" icon="fa-clipboard" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="silat-card">
                    <div class="silat-section-header"><div><h3 class="silat-section-title">Log Presensi</h3><p class="silat-section-description">Riwayat check-in/check-out, jarak, dan sanksi.</p></div></div>
                    <div class="overflow-x-auto">
                        <table class="silat-table">
                            <thead class="silat-table-head"><tr><th class="silat-table-cell">Waktu</th><th class="silat-table-cell">Aksi</th><th class="silat-table-cell">Status</th><th class="silat-table-cell">Durasi</th><th class="silat-table-cell">Jarak</th><th class="silat-table-cell">Catatan</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($enrollment->checkIns as $checkIn)
                                    <tr><td class="silat-table-cell">{{ $checkIn->checked_at?->format('d/m/Y H:i') }}</td><td class="silat-table-cell">{{ $checkIn->action === 'check_out' ? 'Pulang' : 'Masuk' }}</td><td class="silat-table-cell"><x-badge>{{ $checkIn->type }}</x-badge></td><td class="silat-table-cell">{{ $checkIn->duration_minutes !== null ? floor($checkIn->duration_minutes / 60).'j '.($checkIn->duration_minutes % 60).'m' : '-' }}@if ($checkIn->sanction_points)<div class="text-xs text-rose-600">{{ $checkIn->sanction_points }} poin</div>@endif</td><td class="silat-table-cell">{{ $checkIn->distance_meters !== null ? number_format($checkIn->distance_meters, 0, ',', '.').' m' : '-' }}</td><td class="silat-table-cell">{{ $checkIn->note ?: '-' }}</td></tr>
                                @empty
                                    <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada data presensi" icon="fa-fingerprint" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>

            <section x-show="tab === 'pelaporan'" x-cloak class="silat-card">
                <div class="silat-section-header"><div><h3 class="silat-section-title">Pelaporan</h3><p class="silat-section-description">Unggah laporan sampai Pelaporan Tahap 4/Laporan Lengkap Bab 1 s.d. 5.</p></div></div>
                <div class="grid gap-5 p-5 lg:grid-cols-[360px_1fr]">
                    <form method="POST" action="{{ route('student.reports.progress.store', $enrollment) }}" enctype="multipart/form-data" class="space-y-4" x-data='{ selectedType: @json(array_key_first($uploadableDeadlineLabels)), notes: @json($submissionNotes) }'>
                        @csrf
                        <div>
                            <x-input-label for="deadline_type" value="Jenis Dokumen" />
                            <x-select-input id="deadline_type" name="deadline_type" class="mt-1 block w-full" x-model="selectedType" required>
                                @foreach ($uploadableDeadlineLabels as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </x-select-input>
                            @if (empty($uploadableDeadlineLabels))
                                <p class="mt-2 text-sm text-gray-500">Semua dokumen laporan sudah disetujui atau terkunci.</p>
                            @else
                                <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-950" x-show="notes[selectedType]" x-cloak><p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Catatan Jenis Pelaporan</p><p class="mt-1" x-text="notes[selectedType]"></p></div>
                            @endif
                        </div>
                        <div><x-input-label for="file" value="File PDF/DOC" /><input id="file" name="file" type="file" accept=".pdf,.doc,.docx" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" required></div>
                        <x-primary-button :disabled="empty($uploadableDeadlineLabels)">Unggah / Revisi</x-primary-button>
                    </form>
                    <div class="overflow-x-auto">
                        <table class="silat-table">
                            <thead class="silat-table-head"><tr><th class="silat-table-cell">Dokumen</th><th class="silat-table-cell">Unggah Terakhir</th><th class="silat-table-cell">Status</th><th class="silat-table-cell">Sanksi</th><th class="silat-table-cell">Catatan</th><th class="silat-table-cell">File</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($progressByType->reject(fn ($progress, $type) => $type === 'hardcopy') as $progress)
                                    <tr><td class="silat-table-cell">{{ $deadlineLabels[$progress->deadline_type] ?? Str::headline($progress->deadline_type) }}</td><td class="silat-table-cell">{{ $progress->uploaded_at?->format('d/m/Y H:i') }}</td><td class="silat-table-cell"><x-badge :variant="$progressStatusVariants[$progress->status] ?? 'neutral'">{{ $progressStatusLabels[$progress->status] ?? $progress->status }}</x-badge></td><td class="silat-table-cell">{{ $progress->sanction_points ?: 0 }} poin</td><td class="silat-table-cell">{{ $progress->lecturer_note ?: '-' }}</td><td class="silat-table-cell"><a class="silat-secondary-link" href="{{ route('submission-progress.file', $progress) }}" target="_blank">Buka</a></td></tr>
                                @empty
                                    <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada unggahan laporan" icon="fa-file-arrow-up" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section x-show="tab === 'seminar'" x-cloak class="silat-card">
                <div class="silat-section-header">
                    <div><h3 class="silat-section-title">Seminar & Penilaian</h3><p class="silat-section-description">Ajukan seminar, pantau ACC, jadwal, dan nilai seminar.</p></div>
                    @if ($latestSeminarRequest)
                        <x-badge :variant="$seminarStatusVariants[$latestSeminarRequest->status] ?? 'neutral'">{{ $seminarStatusLabels[$latestSeminarRequest->status] ?? Str::headline($latestSeminarRequest->status) }}</x-badge>
                    @endif
                </div>
                <div class="border-b border-gray-100 p-5">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Nilai Program Pembimbing Lapangan</p>
                                <p class="mt-1 text-sm text-gray-500">Komponen: kedisiplinan, kerja sama, dan prestasi kerja.</p>
                            </div>
                            @if ($enrollment->fieldSupervisorAssessment)
                                <x-badge variant="success">{{ number_format((float) $enrollment->fieldSupervisorAssessment->final_score, 2, ',', '.') }}</x-badge>
                            @else
                                <x-badge variant="warning">Menunggu Pembimbing Lapangan</x-badge>
                            @endif
                        </div>
                        @if ($enrollment->fieldSupervisorAssessment)
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-md border border-white bg-white p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">A. Kedisiplinan</p>
                                    <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format((float) $enrollment->fieldSupervisorAssessment->discipline_score, 2, ',', '.') }}</p>
                                </div>
                                <div class="rounded-md border border-white bg-white p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">B. Kerja sama</p>
                                    <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format((float) $enrollment->fieldSupervisorAssessment->teamwork_score, 2, ',', '.') }}</p>
                                </div>
                                <div class="rounded-md border border-white bg-white p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">C. Prestasi kerja</p>
                                    <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format((float) $enrollment->fieldSupervisorAssessment->performance_score, 2, ',', '.') }}</p>
                                </div>
                            </div>
                            <p class="mt-3 text-sm text-gray-500">
                                Diisi {{ $enrollment->fieldSupervisorAssessment->assessed_at?->format('d/m/Y H:i') }}
                                oleh {{ $enrollment->fieldSupervisorAssessment->assessed_by_name ?: $enrollment->fieldSupervisorAssessment->assessed_by_email }}.
                            </p>
                            @if ($enrollment->fieldSupervisorAssessment->student_general_note)
                                <div class="mt-4 rounded-md border border-white bg-white p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Catatan Umum</p>
                                    <p class="mt-1 text-sm text-gray-700">{{ $enrollment->fieldSupervisorAssessment->student_general_note }}</p>
                                </div>
                            @endif
                            @if ($enrollment->fieldSupervisorAssessment->student_recommendation)
                                <div class="mt-3 rounded-md border border-white bg-white p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rekomendasi untuk Mahasiswa</p>
                                    <p class="mt-1 text-sm text-gray-700">{{ $enrollment->fieldSupervisorAssessment->student_recommendation }}</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="grid gap-5 p-5 lg:grid-cols-[360px_1fr]">
                    <form method="POST" action="{{ route('student.seminar-requests.store', $enrollment) }}" enctype="multipart/form-data" class="space-y-4" x-data="{ approvalMethod: 'system' }">
                        @csrf
                        @if (! $canRequestSeminar)<x-alert variant="warning">{{ $seminarBlockedReason }}</x-alert>@endif
                        <div><x-input-label for="seminar_title" value="Judul Seminar" /><x-text-input id="seminar_title" name="title" class="mt-1 block w-full" :value="old('title')" :disabled="! $canRequestSeminar" required /></div>
                        <div class="grid gap-3 sm:grid-cols-2"><div><x-input-label for="proposed_date" value="Tanggal Usulan" /><x-text-input id="proposed_date" name="proposed_date" type="date" class="mt-1 block w-full" :value="old('proposed_date')" :disabled="! $canRequestSeminar" /></div><div><x-input-label for="proposed_time" value="Jam Usulan" /><x-text-input id="proposed_time" name="proposed_time" type="time" class="mt-1 block w-full" :value="old('proposed_time')" :disabled="! $canRequestSeminar" /></div></div>
                        <div><x-input-label for="mode" value="Mode Seminar" /><x-select-input id="mode" name="mode" class="mt-1 block w-full" :disabled="! $canRequestSeminar" required><option value="offline">Offline</option><option value="online">Online</option><option value="hybrid">Hybrid</option></x-select-input></div>
                        <div><x-input-label for="seminar_location" value="Lokasi / Ruang" /><x-text-input id="seminar_location" name="location" class="mt-1 block w-full" :value="old('location')" :disabled="! $canRequestSeminar" /></div>
                        <div><x-input-label for="meeting_url" value="Link Meeting" /><x-text-input id="meeting_url" name="meeting_url" type="url" class="mt-1 block w-full" :value="old('meeting_url')" :disabled="! $canRequestSeminar" /></div>
                        <div><x-input-label for="approval_method" value="Jalur ACC Dosen" /><x-select-input id="approval_method" name="approval_method" class="mt-1 block w-full" x-model="approvalMethod" :disabled="! $canRequestSeminar" required><option value="system">ACC Dosen via Sistem</option><option value="manual_upload">Upload Berkas ACC Seminar</option></x-select-input></div>
                        <div><x-input-label for="seminar_document" value="Dokumen Seminar / Draft" /><input id="seminar_document" name="seminar_document" type="file" accept=".pdf,.doc,.docx" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" {{ ! $canRequestSeminar ? 'disabled' : '' }}></div>
                        <div x-show="approvalMethod === 'manual_upload'" x-cloak><x-input-label for="manual_acc" value="Berkas ACC Seminar dari Dosen" /><input id="manual_acc" name="manual_acc" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" x-bind:required="approvalMethod === 'manual_upload'" {{ ! $canRequestSeminar ? 'disabled' : '' }}></div>
                        <div><x-input-label for="student_note" value="Catatan Mahasiswa" /><x-textarea-input id="student_note" name="student_note" rows="3" class="mt-1 block w-full" :disabled="! $canRequestSeminar">{{ old('student_note') }}</x-textarea-input></div>
                        <x-primary-button :disabled="! $canRequestSeminar">Ajukan Seminar</x-primary-button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="silat-table">
                            <thead class="silat-table-head"><tr><th class="silat-table-cell">Pengajuan</th><th class="silat-table-cell">Status</th><th class="silat-table-cell">Jadwal</th><th class="silat-table-cell">Nilai</th><th class="silat-table-cell">File</th><th class="silat-table-cell">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($enrollment->seminarRequests as $seminarRequest)
                                    <tr>
                                        <td class="silat-table-cell"><div class="font-medium text-gray-900">{{ $seminarRequest->title }}</div><div class="text-xs text-gray-500">Usulan: {{ $seminarRequest->proposed_date?->format('d/m/Y') ?: '-' }} {{ $seminarRequest->proposed_time ? substr((string) $seminarRequest->proposed_time, 0, 5) : '' }}</div></td>
                                        <td class="silat-table-cell"><x-badge :variant="$seminarStatusVariants[$seminarRequest->status] ?? 'neutral'">{{ $seminarStatusLabels[$seminarRequest->status] ?? Str::headline($seminarRequest->status) }}</x-badge>@if($seminarRequest->lecturer_note)<div class="mt-1 text-xs text-amber-700">{{ $seminarRequest->lecturer_note }}</div>@endif @if($seminarRequest->admin_note)<div class="mt-1 text-xs text-gray-500">{{ $seminarRequest->admin_note }}</div>@endif</td>
                                        <td class="silat-table-cell">{{ $seminarRequest->scheduled_at?->format('d/m/Y H:i') ?: '-' }}@if($seminarRequest->location)<div class="text-xs text-gray-500">{{ $seminarRequest->location }}</div>@endif</td>
                                        <td class="silat-table-cell">{{ $seminarRequest->seminar_score !== null ? number_format((float) $seminarRequest->seminar_score, 2, ',', '.') : '-' }}</td>
                                        <td class="silat-table-cell space-y-1">@if($seminarRequest->seminar_document_path)<a class="silat-secondary-link text-xs" href="{{ route('seminar-requests.file', [$seminarRequest, 'document']) }}" target="_blank">Dokumen</a>@endif @if($seminarRequest->manual_acc_path)<a class="block silat-secondary-link text-xs" href="{{ route('seminar-requests.file', [$seminarRequest, 'manual-acc']) }}" target="_blank">ACC</a>@endif @if($seminarRequest->assessment_file_path)<a class="block silat-secondary-link text-xs" href="{{ route('seminar-requests.file', [$seminarRequest, 'assessment']) }}" target="_blank">Form Nilai</a>@endif</td>
                                        <td class="silat-table-cell">
                                            @if (in_array($seminarRequest->status, ['waiting_lecturer_approval', 'waiting_manual_acc_validation', 'revision_required'], true))
                                                <form method="POST" action="{{ route('student.seminar-requests.cancel', $seminarRequest) }}">@csrf @method('PATCH')<button class="silat-secondary-link text-red-700" type="submit">Batalkan</button></form>
                                            @else
                                                <span class="text-xs text-gray-500">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada pengajuan seminar" icon="fa-person-chalkboard" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section x-show="tab === 'penyelesaian'" x-cloak class="space-y-6">
                <section class="silat-card">
                    <div class="silat-section-header"><div><h3 class="silat-section-title">Penyelesaian Program</h3><p class="silat-section-description">Upload bukti penyerahan laporan hardcopy dan cetak laporan akhir.</p></div></div>
                    <div class="grid gap-3 px-5 pt-5 sm:grid-cols-2">
                        @foreach ($completionPrerequisites as $item)
                            <div class="rounded-lg border {{ $item['done'] ? 'border-green-100 bg-green-50' : 'border-amber-100 bg-amber-50' }} p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $item['label'] }}</p>
                                        <p class="mt-1 text-xs text-gray-600">{{ $item['description'] }}</p>
                                    </div>
                                    <x-badge :variant="$item['done'] ? 'success' : 'warning'">{{ $item['done'] ? 'OK' : 'Belum' }}</x-badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-5 pt-5">
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Nilai Akhir</p>
                                    <p class="mt-1 text-sm text-gray-500">Rekap nilai dosen, pembimbing lapangan, dan pengurangan sanksi.</p>
                                </div>
                                @if ($enrollment->finalAssessment)
                                    <x-badge variant="success">Sudah final</x-badge>
                                @else
                                    <x-badge variant="neutral">Belum difinalisasi</x-badge>
                                @endif
                            </div>

                            @if ($enrollment->finalAssessment)
                                @php($finalAssessment = $enrollment->finalAssessment)
                                <div class="mt-4 grid gap-3 md:grid-cols-5">
                                    <div class="rounded-md bg-gray-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Dosen Pembimbing</p>
                                        <p class="mt-1 text-xl font-semibold tabular-nums text-gray-900">{{ number_format((float) $finalAssessment->lecturer_score, 2, ',', '.') }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ number_format((float) $finalAssessment->lecturer_weight, 0, ',', '.') }}%</p>
                                    </div>
                                    <div class="rounded-md bg-gray-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pembimbing Lapangan</p>
                                        <p class="mt-1 text-xl font-semibold tabular-nums text-gray-900">{{ number_format((float) $finalAssessment->field_supervisor_score, 2, ',', '.') }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ number_format((float) $finalAssessment->field_supervisor_weight, 0, ',', '.') }}%</p>
                                    </div>
                                    <div class="rounded-md bg-gray-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nilai Dasar</p>
                                        <p class="mt-1 text-xl font-semibold tabular-nums text-gray-900">{{ number_format((float) $finalAssessment->base_score, 2, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-md bg-amber-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pengurangan</p>
                                        <p class="mt-1 text-xl font-semibold tabular-nums text-amber-900">{{ number_format((float) $finalAssessment->final_deduction, 2, ',', '.') }}</p>
                                        <p class="mt-1 text-xs text-amber-700">Suggest {{ number_format((float) $finalAssessment->suggested_deduction, 2, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-md bg-blue-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Total Nilai</p>
                                        <p class="mt-1 text-2xl font-bold tabular-nums text-blue-950">{{ number_format((float) $finalAssessment->final_score, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                                @if ($finalAssessment->note)
                                    <div class="mt-3 rounded-md bg-gray-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Catatan Finalisasi</p>
                                        <p class="mt-1 text-sm text-gray-700">{{ $finalAssessment->note }}</p>
                                    </div>
                                @endif
                                <p class="mt-3 text-xs text-gray-500">
                                    Difinalisasi {{ $finalAssessment->finalized_at?->format('d/m/Y H:i') }}
                                    @if ($finalAssessment->finalizer)
                                        oleh {{ $finalAssessment->finalizer->name }}
                                    @endif
                                </p>
                            @else
                                <p class="mt-4 text-sm text-gray-500">Nilai akhir akan tampil setelah admin/koordinator melakukan finalisasi.</p>
                            @endif
                        </div>
                    </div>
                    <div class="grid gap-5 p-5 lg:grid-cols-[360px_1fr]">
                        <form method="POST" action="{{ route('student.reports.progress.store', $enrollment) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <input type="hidden" name="deadline_type" value="hardcopy">
                            <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-950">{{ $submissionNotes['hardcopy'] ?? 'Upload bukti penyerahan laporan hardcopy.' }}</div>
                            <div><x-input-label for="hardcopy_file" value="Bukti Penyerahan Hardcopy" /><input id="hardcopy_file" name="file" type="file" accept=".pdf,.doc,.docx" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" required></div>
                            <x-primary-button :disabled="! $canUploadHardcopy">Upload Hardcopy</x-primary-button>
                            @if (! $canUploadHardcopy)<p class="text-sm text-gray-500">Bukti hardcopy terkunci sampai validasi catatan harian dan nilai Pembimbing Lapangan lengkap, atau bukti sudah disetujui.</p>@endif
                        </form>
                        <div class="space-y-4">
                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-sm font-semibold text-gray-900">Status Hardcopy</p>
                                @if ($hardcopyProgress)
                                    <div class="mt-2 flex flex-wrap items-center gap-2"><x-badge :variant="$progressStatusVariants[$hardcopyProgress->status] ?? 'neutral'">{{ $progressStatusLabels[$hardcopyProgress->status] ?? $hardcopyProgress->status }}</x-badge><span class="text-sm text-gray-500">{{ $hardcopyProgress->uploaded_at?->format('d/m/Y H:i') }}</span></div>
                                    @if ($hardcopyProgress->lecturer_note)<p class="mt-2 text-sm text-gray-500">{{ $hardcopyProgress->lecturer_note }}</p>@endif
                                    <a class="mt-3 inline-flex silat-secondary-link" href="{{ route('submission-progress.file', $hardcopyProgress) }}" target="_blank">Buka bukti</a>
                                @else
                                    <p class="mt-2 text-sm text-gray-500">Belum ada bukti hardcopy.</p>
                                @endif
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-sm font-semibold text-gray-900">Cetak Laporan</p>
                                <p class="mt-1 text-sm text-gray-500">Cetak laporan tersedia jika data dosen pembimbing, pembimbing lapangan, mitra, dan koordinat mitra sudah lengkap.</p>
                                @if ($missingPrintData->isEmpty())
                                    <a class="silat-btn mt-3" href="{{ route('student.reports.print', $enrollment) }}" target="_blank"><x-icon name="fa-print" /> Cetak Laporan</a>
                                @else
                                    <p class="mt-3 text-sm text-amber-700">Data belum lengkap: {{ $missingPrintData->implode(', ') }}.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>
            </section>
        </div>
    </div>
</x-app-layout>
