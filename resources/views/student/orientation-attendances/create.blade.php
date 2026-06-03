<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Presensi Pembekalan</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ $event->name }}</h2>
            <p class="mt-1 text-sm text-gray-500">Validasi lokasi dan foto realtime untuk kegiatan pembekalan program.</p>
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell space-y-6">
        @if ($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif
        @if ($attendance)<x-alert variant="success">Presensi pembekalan sudah tercatat pada {{ $attendance->checked_at?->format('d/m/Y H:i') }}.</x-alert>@endif

        <div class="grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
            <section class="silat-card overflow-hidden">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Peta Lokasi Pembekalan</h3>
                        <p class="silat-section-description">Marker mahasiswa, marker lokasi pembekalan, dan garis jarak akan tampil setelah lokasi terbaca.</p>
                    </div>
                    <x-badge>{{ $maxDistance > 0 ? number_format($maxDistance, 0, ',', '.').' m' : 'Tanpa radius' }}</x-badge>
                </div>
                <div class="p-5">
                    <div
                        class="monpkl-map monpkl-form-map rounded-lg"
                        data-map-type="check-in"
                        data-lat-input="student_latitude"
                        data-lng-input="student_longitude"
                        data-office-lat="{{ $event->latitude }}"
                        data-office-lng="{{ $event->longitude }}"
                        data-office-name="{{ $event->location_name }}"
                        data-map-config='@json($mapConfig)'
                    ></div>
                </div>
            </section>

            <section class="silat-card">
                <form method="POST" action="{{ route('student.orientation-attendances.store', $event) }}" class="space-y-5 p-5">
                    @csrf
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mahasiswa</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $enrollment->student?->full_name }}</p>
                        <p class="text-sm text-gray-600">{{ $enrollment->student?->npm }} · {{ $enrollment->studyProgram?->name }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lokasi / Periode</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $event->location_name }}</p>
                        <p class="text-sm text-gray-600">{{ $event->internshipPeriod?->display_name }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><x-input-label for="student_latitude" :value="__('Latitude Anda')" /><x-text-input id="student_latitude" name="student_latitude" type="text" class="mt-1 block w-full" readonly required /></div>
                        <div><x-input-label for="student_longitude" :value="__('Longitude Anda')" /><x-text-input id="student_longitude" name="student_longitude" type="text" class="mt-1 block w-full" readonly required /></div>
                    </div>
                    <div>
                        <x-input-label for="photo_capture" :value="__('Foto Bukti Realtime')" />
                        <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-3" data-check-in-camera data-capture-input="photo_capture" data-submit-target="#orientation-submit">
                            <div class="overflow-hidden rounded-lg bg-gray-900">
                                <video data-camera-video class="aspect-video w-full object-cover" autoplay playsinline muted></video>
                                <canvas data-camera-canvas class="aspect-video w-full object-cover" hidden></canvas>
                            </div>
                            <input id="photo_capture" name="photo_capture" type="hidden" value="{{ old('photo_capture') }}" required>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" data-camera-start class="silat-btn-secondary" @disabled($attendance)><x-icon name="fa-camera" /> Aktifkan Kamera</button>
                                <button type="button" data-camera-capture class="silat-btn" disabled><x-icon name="fa-camera-retro" /> Ambil Foto</button>
                                <button type="button" data-camera-retake class="silat-btn-secondary" hidden>Ulangi</button>
                            </div>
                            <p data-camera-status class="mt-2 text-xs text-gray-500">Foto wajib diambil langsung dari kamera perangkat.</p>
                        </div>
                        <x-input-error :messages="$errors->get('photo_capture')" />
                    </div>
                    <x-primary-button id="orientation-submit" disabled @disabled($attendance)><x-icon name="fa-fingerprint" /> Simpan Presensi</x-primary-button>
                </form>
            </section>
        </div>
    </div></div>
</x-app-layout>
