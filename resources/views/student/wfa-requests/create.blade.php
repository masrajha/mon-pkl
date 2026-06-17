<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Layanan Program</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Ajukan WFA') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Ajukan Work from Anywhere untuk tanggal tertentu dengan bukti pendukung resmi.</p>
        </div>
    </x-slot>

    <div class="py-10"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        @if ($errors->any())<x-alert variant="danger" class="mb-4">{{ $errors->first() }}</x-alert>@endif
        @if ($hasPendingRequest)
            <x-alert variant="warning" class="mb-4">Masih ada pengajuan WFA berstatus Menunggu. Batalkan pengajuan tersebut atau tunggu keputusan admin/koordinator sebelum mengajukan yang baru.</x-alert>
        @endif

        <form method="POST" action="{{ route('student.wfa-requests.store') }}" enctype="multipart/form-data" class="silat-card space-y-5 p-6">
            @csrf
            <div>
                <x-input-label for="internship_enrollment_id" value="Enrollment Aktif" />
                <select id="internship_enrollment_id" name="internship_enrollment_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="">Pilih enrollment</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}" @selected((string) old('internship_enrollment_id', $selectedEnrollmentId) === (string) $enrollment->id)>
                            {{ $enrollment->internshipPeriod?->display_name }} - {{ $enrollment->studyProgram?->name }} - {{ $enrollment->internshipPlace?->name ?: 'Mitra belum ditentukan' }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('internship_enrollment_id')" class="mt-2" />
                @if ($enrollments->isEmpty())
                    <p class="mt-2 text-sm text-amber-700">Belum ada enrollment aktif yang dapat diajukan WFA.</p>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="starts_at" value="Tanggal Mulai WFA" />
                    <x-text-input id="starts_at" name="starts_at" type="date" class="mt-1 block w-full" :value="old('starts_at')" required />
                    <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="ends_at" value="Tanggal Selesai WFA" />
                    <x-text-input id="ends_at" name="ends_at" type="date" class="mt-1 block w-full" :value="old('ends_at')" required />
                    <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="planned_location" value="Lokasi Rencana WFA" />
                <x-text-input id="planned_location" name="planned_location" class="mt-1 block w-full" :value="old('planned_location')" placeholder="Contoh: rumah, kantor cabang, lokasi kegiatan lapangan, atau alamat singkat" required />
                <x-input-error :messages="$errors->get('planned_location')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="planned_latitude" value="Latitude Lokasi Rencana (opsional)" />
                    <x-text-input id="planned_latitude" name="planned_latitude" type="number" step="0.0000001" min="-90" max="90" class="mt-1 block w-full" :value="old('planned_latitude')" />
                    <x-input-error :messages="$errors->get('planned_latitude')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="planned_longitude" value="Longitude Lokasi Rencana (opsional)" />
                    <x-text-input id="planned_longitude" name="planned_longitude" type="number" step="0.0000001" min="-180" max="180" class="mt-1 block w-full" :value="old('planned_longitude')" />
                    <x-input-error :messages="$errors->get('planned_longitude')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="planned_activity" value="Aktivitas yang Akan Dikerjakan" />
                <textarea id="planned_activity" name="planned_activity" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('planned_activity') }}</textarea>
                <x-input-error :messages="$errors->get('planned_activity')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="reason" value="Alasan WFA" />
                <textarea id="reason" name="reason" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('reason') }}</textarea>
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="evidence_file" value="Bukti Pendukung" />
                <input id="evidence_file" name="evidence_file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,image/jpeg,image/png,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100" required>
                <p class="mt-1 text-xs text-gray-500">Wajib. Contoh bukti: foto/screenshot surat resmi, email, instruksi chat WA/Messenger, atau dokumen pendukung. Format JPG, PNG, WebP, PDF, DOC, DOCX maksimal 5 MB.</p>
                <x-input-error :messages="$errors->get('evidence_file')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between gap-3">
                <a class="silat-secondary-link" href="{{ route('student.wfa-requests.index') }}">Lihat histori</a>
                <x-primary-button :disabled="$hasPendingRequest || $enrollments->isEmpty()">Kirim Pengajuan WFA</x-primary-button>
            </div>
        </form>
    </div></div>
</x-app-layout>
