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
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Laporan Mahasiswa</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Laporan Saya') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $enrollment->student?->npm }} · {{ $enrollment->studyProgram?->name }} · {{ $enrollment->internshipPeriod?->display_name }}</p>
            </div>
            @if ($missingPrintData->isEmpty())
                <a class="silat-btn" href="{{ route('student.reports.print', $enrollment) }}" target="_blank"><x-icon name="fa-print" /> Cetak</a>
            @else
                <a class="silat-btn" href="{{ route('student.supervisor-requests.create') }}"><x-icon name="fa-user-pen" /> Ajukan Pembimbing</a>
            @endif
        </div>
    </x-slot>
    <div class="py-8"><div class="silat-shell space-y-6">
        @if ($missingPrintData->isNotEmpty())
            <x-alert variant="warning">
                Data belum lengkap untuk cetak laporan: {{ $missingPrintData->implode(', ') }}.
                @if ($pendingSupervisorRequest)
                    <span class="mt-1 block">Permohonan perubahan pembimbing sedang menunggu persetujuan.</span>
                @else
                    <a class="mt-2 inline-flex font-semibold text-amber-900 underline" href="{{ route('student.supervisor-requests.create') }}">Ajukan pelengkapan data pembimbing</a>
                @endif
            </x-alert>
        @endif

        <section class="silat-card">
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">{{ $enrollment->student?->full_name }}</h3>
                    <p class="silat-section-description">{{ $enrollment->internshipPlace?->name ?: 'Mitra belum ditentukan' }}</p>
                </div>
                <x-badge>{{ $enrollment->internshipPeriod?->program?->rule_key === 'kerja_praktik' ? 'Rule Kerja Praktik' : ($enrollment->internshipPeriod?->program?->rule_key ?: 'kerja_praktik') }}</x-badge>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                @foreach ([
                    ['label' => 'Dosen Pembimbing', 'value' => $enrollment->lecturer?->name ?: 'Belum ditentukan'],
                    ['label' => 'Pembimbing Lapangan', 'value' => $enrollment->field_supervisor ?: 'Belum diisi'],
                    ['label' => 'Email Pembimbing Lapangan', 'value' => $enrollment->field_supervisor_email ?: 'Belum diisi'],
                    ['label' => 'Total Presensi', 'value' => $enrollment->checkIns->count()],
                    ['label' => 'Status', 'value' => $enrollment->status],
                    ['label' => 'Total Sanksi', 'value' => number_format($enrollment->total_sanctions_points ?? 0, 0, ',', '.').' poin'],
                    ['label' => 'Progres Laporan', 'value' => $progressByType->count().' dokumen'],
                ] as $item)
                    <div class="silat-stat-card"><p class="silat-stat-label">{{ $item['label'] }}</p><p class="mt-2 font-semibold text-gray-900">{{ $item['value'] }}</p></div>
                @endforeach
            </div>
            <div class="border-t border-gray-100 p-5">
                <div class="flex items-center justify-between text-sm"><span class="font-medium text-gray-700">Kelengkapan data cetak</span><span class="font-semibold text-gray-900">{{ $enrollment->lecturer && $enrollment->field_supervisor ? '100%' : '60%' }}</span></div>
                <div class="mt-2 h-3 rounded-full bg-gray-100"><div class="h-3 rounded-full bg-blue-600" style="width: {{ $enrollment->lecturer && $enrollment->field_supervisor ? '100' : '60' }}%"></div></div>
                <p class="mt-2 text-sm text-gray-500">{{ $enrollment->lecturer && $enrollment->field_supervisor ? 'Data pembimbing lengkap untuk cetak laporan.' : 'Lengkapi dosen pembimbing dan pembimbing lapangan sebelum cetak final.' }}</p>
            </div>
        </section>

        <section class="silat-card">
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">Ringkasan Pelaksanaan</h3>
                    <p class="silat-section-description">Progres bimbingan, sanksi, dan nilai ditampilkan sebagai status awal pelaksanaan program.</p>
                </div>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                @php
                    $guidanceItems = [
                        ['label' => 'Dosen pembimbing', 'done' => (bool) $enrollment->lecturer],
                        ['label' => 'Pembimbing lapangan', 'done' => filled($enrollment->field_supervisor)],
                        ['label' => 'Mitra dan koordinat', 'done' => (bool) ($enrollment->internshipPlace?->latitude && $enrollment->internshipPlace?->longitude)],
                        ['label' => 'Presensi', 'done' => $enrollment->checkIns->isNotEmpty()],
                        ['label' => 'Laporan final', 'done' => filled($enrollment->final_report_path)],
                    ];
                    $guidanceProgress = (int) round(collect($guidanceItems)->where('done', true)->count() / count($guidanceItems) * 100);
                @endphp

                <div class="silat-stat-card">
                    <p class="silat-stat-label">Progres Bimbingan</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $guidanceProgress }}%</p>
                    <div class="mt-3 h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-blue-600" style="width: {{ $guidanceProgress }}%"></div></div>
                    <div class="mt-3 space-y-1 text-xs text-gray-500">
                        @foreach ($guidanceItems as $item)
                            <div class="flex items-center justify-between gap-3">
                                <span>{{ $item['label'] }}</span>
                                <x-badge variant="{{ $item['done'] ? 'success' : 'neutral' }}">{{ $item['done'] ? 'OK' : 'Belum' }}</x-badge>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="silat-stat-card">
                    <p class="silat-stat-label">Sanksi</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($enrollment->total_sanctions_points ?? 0, 0, ',', '.') }} poin</p>
                    <p class="mt-3 text-sm text-gray-500">{{ ($enrollment->total_sanctions_points ?? 0) > 0 ? 'Ada poin sanksi yang perlu diperhatikan.' : 'Belum ada poin sanksi tercatat.' }}</p>
                </div>

                <div class="silat-stat-card">
                    <p class="silat-stat-label">Nilai</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">Belum tersedia</p>
                    <p class="mt-3 text-sm text-gray-500">Nilai lapangan, nilai dosen, dan nilai akhir akan muncul setelah modul penilaian diaktifkan.</p>
                </div>
            </div>
        </section>

        <section class="silat-card">
            <div class="silat-section-header"><div><h3 class="silat-section-title">Deadline Periode</h3><p class="silat-section-description">Pantau tenggat unggahan dan potensi sanksi.</p></div></div>
            <div class="overflow-x-auto">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell">Jenis</th><th class="silat-table-cell">Tanggal</th><th class="silat-table-cell">Sanksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($enrollment->internshipPeriod?->deadlines ?? [] as $deadline)
                            <tr><td class="silat-table-cell">{{ $deadlineTypeLabels[$deadline->deadline_type] ?? str($deadline->deadline_type)->replace('_', ' ')->title() }}</td><td class="silat-table-cell">{{ $deadline->deadline_date?->format('d/m/Y') }}</td><td class="silat-table-cell">{{ $deadline->penalty_points }} poin {{ $deadline->is_fixed_penalty ? 'tetap' : 'per hari' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="silat-table-cell"><x-empty-state title="Belum ada deadline periode" icon="fa-hourglass-start" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="silat-card">
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">Progres Laporan</h3>
                    <p class="silat-section-description">Unggah dokumen sesuai deadline periode dan pantau hasil review pembimbing.</p>
                </div>
            </div>
            <div class="grid gap-5 p-5 lg:grid-cols-[360px_1fr]">
                <form method="POST" action="{{ route('student.reports.progress.store', $enrollment) }}" enctype="multipart/form-data" class="space-y-4" x-data='{ selectedType: @json(array_key_first($uploadableDeadlineLabels)), notes: @json($submissionNotes) }'>
                    @csrf
                    <div>
                        <x-input-label for="deadline_type" :value="__('Jenis Dokumen')" />
                        <x-select-input id="deadline_type" name="deadline_type" class="mt-1 block w-full" x-model="selectedType" required>
                            @foreach ($uploadableDeadlineLabels as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('deadline_type')" class="mt-2" />
                        @if (empty($uploadableDeadlineLabels))
                            <p class="mt-2 text-sm text-gray-500">Semua dokumen sudah disetujui dan terkunci.</p>
                        @else
                            <p class="mt-2 text-xs text-gray-500">Dokumen yang diminta revisi dapat diunggah ulang berkali-kali. Sanksi deadline hanya dihitung pada submit pertama.</p>
                            <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-950" x-show="notes[selectedType]" x-cloak>
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Catatan Jenis Pelaporan</p>
                                <p class="mt-1" x-text="notes[selectedType]"></p>
                            </div>
                        @endif
                    </div>
                    <div>
                        <x-input-label for="file" :value="__('File PDF/DOC')" />
                        <input id="file" name="file" type="file" accept=".pdf,.doc,.docx" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" required>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>
                    <x-primary-button :disabled="empty($uploadableDeadlineLabels)">Unggah / Revisi</x-primary-button>
                </form>

                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head"><tr><th class="silat-table-cell">Dokumen</th><th class="silat-table-cell">Unggah Terakhir</th><th class="silat-table-cell">Status</th><th class="silat-table-cell">Sanksi Submit Pertama</th><th class="silat-table-cell">Catatan Reviewer</th><th class="silat-table-cell">File</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($progressByType as $progress)
                                <tr>
                                    <td class="silat-table-cell">{{ $deadlineLabels[$progress->deadline_type] ?? str($progress->deadline_type)->replace('_', ' ')->title() }}</td>
                                    <td class="silat-table-cell">{{ $progress->uploaded_at?->format('d/m/Y H:i') }}</td>
                                    <td class="silat-table-cell">
                                        <x-badge variant="{{ $progressStatusVariants[$progress->status] ?? 'neutral' }}">{{ $progressStatusLabels[$progress->status] ?? $progress->status }}</x-badge>
                                        @if ($progress->status === 'approved')
                                            <div class="mt-1 text-xs text-gray-500">Terkunci</div>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell">{{ $progress->sanction_points ?: 0 }} poin</td>
                                    <td class="silat-table-cell">{{ $progress->lecturer_note ?: '-' }}</td>
                                    <td class="silat-table-cell"><a class="silat-secondary-link" href="{{ route('submission-progress.file', $progress) }}" target="_blank">Buka</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada unggahan laporan" icon="fa-file-arrow-up" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="silat-card">
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">Catatan Harian</h3>
                    <p class="silat-section-description">Diambil dari catatan presensi. Rencana berasal dari presensi masuk, realisasi berasal dari presensi pulang.</p>
                </div>
                <a class="silat-btn-secondary" href="{{ route('student.reports.daily-logs.print', $enrollment) }}" target="_blank"><x-icon name="fa-print" /> Cetak Form</a>
            </div>
            <div class="overflow-x-auto p-5">
                <table class="silat-table">
                    <thead class="silat-table-head">
                        <tr>
                            <th class="silat-table-cell">Tanggal</th>
                            <th class="silat-table-cell">Jam</th>
                            <th class="silat-table-cell">Jarak</th>
                            <th class="silat-table-cell">Catatan</th>
                            <th class="silat-table-cell">Paraf</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($dailyActivityRows as $row)
                            <tr>
                                <td class="silat-table-cell whitespace-nowrap">{{ $row['date']?->translatedFormat('l, d M Y') }}</td>
                                <td class="silat-table-cell whitespace-nowrap">
                                    <div>Masuk: {{ $row['check_in']?->checked_at?->format('H:i:s') ?: '-' }}</div>
                                    <div>Pulang: {{ $row['check_out']?->checked_at?->format('H:i:s') ?: '-' }}</div>
                                    <div>Durasi: {{ $row['duration_minutes'] !== null ? number_format($row['duration_minutes'] / 60, 2, ',', '.') : '-' }}</div>
                                </td>
                                <td class="silat-table-cell whitespace-nowrap">
                                    <div>Masuk: {{ $row['check_in']?->distance_meters !== null ? number_format($row['check_in']->distance_meters, 2, ',', '.') : '-' }}</div>
                                    <div>Pulang: {{ $row['check_out']?->distance_meters !== null ? number_format($row['check_out']->distance_meters, 2, ',', '.') : '-' }}</div>
                                </td>
                                <td class="silat-table-cell min-w-[420px]">
                                    <p><strong>Rencana:</strong> {{ $row['check_in']?->note ?: '-' }}</p>
                                    <p class="mt-3"><strong>Realisasi:</strong> {{ $row['check_out']?->note ?: '-' }}</p>
                                </td>
                                <td class="silat-table-cell"></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="silat-table-cell"><x-empty-state title="Belum ada catatan harian" icon="fa-clipboard" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="silat-card">
            <div class="silat-section-header"><div><h3 class="silat-section-title">Rekap Presensi</h3><p class="silat-section-description">Riwayat check-in/check-out, jarak, dan catatan aktivitas.</p></div></div>
            <div class="overflow-x-auto">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell">Waktu</th><th class="silat-table-cell">Aksi</th><th class="silat-table-cell">Status</th><th class="silat-table-cell">Durasi</th><th class="silat-table-cell">Jarak</th><th class="silat-table-cell">Catatan</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($enrollment->checkIns as $checkIn)
                            <tr><td class="silat-table-cell">{{ $checkIn->checked_at?->format('d/m/Y H:i') }}</td><td class="silat-table-cell">{{ $checkIn->action === 'check_out' ? 'Pulang' : 'Masuk' }}</td><td class="silat-table-cell"><x-badge>{{ $checkIn->type }}</x-badge></td><td class="silat-table-cell">{{ $checkIn->duration_minutes !== null ? floor($checkIn->duration_minutes / 60).'j '.($checkIn->duration_minutes % 60).'m' : '-' }}@if($checkIn->sanction_points)<div class="text-xs text-rose-600">{{ $checkIn->sanction_points }} poin</div>@endif</td><td class="silat-table-cell">{{ $checkIn->distance_meters !== null ? number_format($checkIn->distance_meters, 0, ',', '.').' m' : '-' }}</td><td class="silat-table-cell">{{ $checkIn->note ?: '-' }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada data presensi" icon="fa-fingerprint" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
