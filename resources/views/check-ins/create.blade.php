@php
    $programName = $enrollment->internshipPeriod?->program?->name ?? 'Program';
    $attendanceStartsAt = $enrollment->effectiveAttendanceStartsAt();
    $attendanceEndsAt = $enrollment->effectiveAttendanceEndsAt();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Presensi</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Check-in Program') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Validasi lokasi, kamera realtime, dan catatan aktivitas harian.</p>
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell space-y-6">
        @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif

        <div class="grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
            <section class="silat-card overflow-hidden">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Peta Lokasi</h3>
                        <p class="silat-section-description">Marker mahasiswa, marker mitra, dan garis jarak akan tampil setelah lokasi terbaca.</p>
                    </div>
                    <x-badge>{{ $statusPreview }}</x-badge>
                </div>
                <div class="p-5">
                    <div
                        class="monpkl-map monpkl-form-map rounded-lg"
                        data-map-type="check-in"
                        data-lat-input="student_latitude"
                        data-lng-input="student_longitude"
                        data-office-lat="{{ $enrollment->internshipPlace?->latitude }}"
                        data-office-lng="{{ $enrollment->internshipPlace?->longitude }}"
                        data-office-name="{{ $enrollment->internshipPlace?->name }}"
                        data-map-config='@json($mapConfig)'
                    ></div>
                </div>
            </section>

            <section class="silat-card">
                <form method="POST" action="{{ route('check-ins.store') }}" class="space-y-5 p-5" x-data="{ action: '{{ old('action', 'check_in') }}' }">
                    @csrf
                    <input type="hidden" name="enrollment_id" value="{{ $enrollment->id }}">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mahasiswa</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $enrollment->student?->full_name }}</p>
                        <p class="text-sm text-gray-600">{{ $enrollment->student?->npm }} · {{ $enrollment->studyProgram?->name }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mitra / Periode</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $enrollment->internshipPlace?->name ?? '-' }}</p>
                        <p class="text-sm text-gray-600">{{ $enrollment->internshipPeriod?->display_name }}</p>
                    </div>
                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Periode Pelaksanaan {{ $programName }}</p>
                                <p class="mt-1 text-sm text-blue-900">
                                    Presensi valid pada rentang tanggal {{ $attendanceStartsAt?->format('d/m/Y') ?: '-' }} s.d. {{ $attendanceEndsAt?->format('d/m/Y') ?: '-' }}.
                                </p>
                            </div>
                            @if ($enrollment->hasAttendanceOverride())
                                <x-badge variant="info">Khusus</x-badge>
                            @endif
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><x-input-label for="student_latitude" :value="__('Latitude Anda')" /><x-text-input id="student_latitude" name="student_latitude" type="text" class="mt-1 block w-full" readonly required /></div>
                        <div><x-input-label for="student_longitude" :value="__('Longitude Anda')" /><x-text-input id="student_longitude" name="student_longitude" type="text" class="mt-1 block w-full" readonly required /></div>
                    </div>
                    <div>
                        <x-input-label :value="__('Jenis Presensi')" />
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                <input type="radio" name="action" value="check_in" x-model="action" @checked(old('action', 'check_in') === 'check_in') class="border-gray-300 text-blue-600 focus:ring-blue-500" required>
                                <span><span class="font-semibold text-gray-900">Masuk</span><span class="block text-xs text-gray-500">Awal aktivitas harian</span></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                <input type="radio" name="action" value="check_out" x-model="action" @checked(old('action') === 'check_out') class="border-gray-300 text-blue-600 focus:ring-blue-500" required>
                                <span><span class="font-semibold text-gray-900">Pulang</span><span class="block text-xs text-gray-500">Akhir aktivitas harian</span></span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('action')" />
                    </div>
                    <div>
                        <x-input-label for="photo_capture" :value="__('Foto Bukti Realtime')" />
                        <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-3" data-check-in-camera data-capture-input="photo_capture" data-submit-target="#check-in-submit">
                            <div class="overflow-hidden rounded-lg bg-gray-900">
                                <video data-camera-video class="aspect-video w-full object-cover" autoplay playsinline muted></video>
                                <canvas data-camera-canvas class="aspect-video w-full object-cover" hidden></canvas>
                            </div>
                            <input id="photo_capture" name="photo_capture" type="hidden" value="{{ old('photo_capture') }}" required>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" data-camera-start class="silat-btn-secondary"><x-icon name="fa-camera" /> Aktifkan Kamera</button>
                                <button type="button" data-camera-capture class="silat-btn" disabled><x-icon name="fa-camera-retro" /> Ambil Foto</button>
                                <button type="button" data-camera-retake class="silat-btn-secondary" hidden>Ulangi</button>
                            </div>
                            <p data-camera-status class="mt-2 text-xs text-gray-500">Foto wajib diambil langsung dari kamera perangkat.</p>
                        </div>
                        <x-input-error :messages="$errors->get('photo_capture')" />
                    </div>
                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700" x-text="action === 'check_out' ? 'Realisasi' : 'Rencana Aktivitas'"></label>
                        <x-textarea-input id="note" name="note" rows="4" class="mt-1" required minlength="20" placeholder="Minimal 5 kata">{{ old('note') }}</x-textarea-input>
                        <p class="mt-1 text-xs text-gray-500" x-text="action === 'check_out' ? 'Tuliskan realisasi aktivitas yang sudah dilakukan hari ini, minimal 5 kata.' : 'Tuliskan rencana aktivitas hari ini, minimal 5 kata.'"></p>
                        <x-input-error :messages="$errors->get('note')" class="mt-2" />
                    </div>
                    <x-primary-button id="check-in-submit" disabled><x-icon name="fa-fingerprint" /> Simpan Check-in</x-primary-button>
                </form>
            </section>
        </div>

        <section class="silat-card">
            <div class="silat-section-header">
                <div><h3 class="silat-section-title">Check-in Terakhir</h3><p class="silat-section-description">Riwayat singkat presensi terbaru.</p></div>
            </div>
            <div class="overflow-x-auto">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell">Waktu</th><th class="silat-table-cell">Aksi</th><th class="silat-table-cell">Status</th><th class="silat-table-cell">Durasi</th><th class="silat-table-cell">Jarak</th><th class="silat-table-cell">Catatan</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentCheckIns as $checkIn)
                            <tr><td class="silat-table-cell">{{ $checkIn->checked_at?->format('d/m/Y H:i') }}</td><td class="silat-table-cell">{{ $checkIn->action === 'check_out' ? 'Pulang' : 'Masuk' }}</td><td class="silat-table-cell"><x-badge>{{ $checkIn->type }}</x-badge></td><td class="silat-table-cell">{{ $checkIn->duration_minutes !== null ? floor($checkIn->duration_minutes / 60).'j '.($checkIn->duration_minutes % 60).'m' : '-' }}@if($checkIn->sanction_points)<div class="text-xs text-rose-600">{{ $checkIn->sanction_points }} poin</div>@endif</td><td class="silat-table-cell">{{ $checkIn->distance_meters === null ? '-' : number_format($checkIn->distance_meters, 0, ',', '.').' m' }}</td><td class="silat-table-cell">{{ $checkIn->note ?: '-' }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada check-in" icon="fa-fingerprint" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
