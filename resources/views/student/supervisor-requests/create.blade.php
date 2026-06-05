<x-app-layout>
    @php
        $selectedEnrollment = $enrollments->firstWhere('id', old('internship_enrollment_id', $selectedEnrollmentId));
        $selectedLecturerId = old('requested_lecturer_supervisor_id', $selectedEnrollment?->lecturer_supervisor_id);
        $selectedFieldSupervisor = old('requested_field_supervisor', $selectedEnrollment?->field_supervisor);
        $selectedFieldSupervisorPhone = old('requested_field_supervisor_phone', $selectedEnrollment?->field_supervisor_phone);
        $selectedFieldSupervisorEmail = old('requested_field_supervisor_email', $selectedEnrollment?->field_supervisor_email);
    @endphp

    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Mahasiswa</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Perubahan Pembimbing') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Ajukan pelengkapan atau perubahan data pembimbing untuk laporan.</p>
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell max-w-4xl">
        @if ($errors->any())<x-alert variant="danger" class="mb-6">{{ $errors->first() }}</x-alert>@endif
        @if ($hasPendingRequest)
            <x-alert variant="warning" class="mb-6">Masih ada permohonan perubahan pembimbing berstatus Menunggu. Batalkan permohonan tersebut atau tunggu keputusan admin/koordinator sebelum mengajukan yang baru.</x-alert>
        @endif

        <form method="POST" action="{{ route('student.supervisor-requests.store') }}" class="silat-card space-y-5 p-6">
            @csrf

            <div>
                <x-input-label for="internship_enrollment_id" value="Program Aktif" />
                <x-select-input id="internship_enrollment_id" name="internship_enrollment_id" class="mt-1" required>
                    <option value="">Pilih program aktif</option>
                    @foreach ($enrollments as $enrollment)
                        <option
                            value="{{ $enrollment->id }}"
                            data-lecturer="{{ $enrollment->lecturer?->name ?: 'Belum ditentukan' }}"
                            data-lecturer-id="{{ $enrollment->lecturer_supervisor_id }}"
                            data-field-supervisor="{{ $enrollment->field_supervisor ?: 'Belum diisi' }}"
                            data-field-supervisor-value="{{ $enrollment->field_supervisor }}"
                            data-field-supervisor-phone="{{ $enrollment->field_supervisor_phone }}"
                            data-field-supervisor-email="{{ $enrollment->field_supervisor_email ?: 'Email belum diisi' }}"
                            data-field-supervisor-email-value="{{ $enrollment->field_supervisor_email }}"
                            @selected((string) old('internship_enrollment_id', $selectedEnrollmentId) === (string) $enrollment->id)
                        >
                            {{ $enrollment->internshipPeriod?->display_name }} - {{ $enrollment->studyProgram?->name }} - {{ $enrollment->internshipPlace?->name ?: 'Mitra belum ditentukan' }}
                        </option>
                    @endforeach
                </x-select-input>
                @if ($enrollments->isEmpty())
                    <p class="mt-2 text-sm text-amber-700">Belum ada program aktif yang dapat diajukan perubahan pembimbing.</p>
                @endif
            </div>

            <div class="grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Dosen Pembimbing Saat Ini</p>
                    <p class="mt-1 font-semibold text-gray-900" data-current-lecturer>-</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pembimbing Lapangan Saat Ini</p>
                    <p class="mt-1 font-semibold text-gray-900" data-current-field-supervisor>-</p>
                    <p class="mt-1 text-sm text-gray-500" data-current-field-supervisor-email>-</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="requested_lecturer_supervisor_id" value="Usulan Dosen Pembimbing" />
                    <x-select-input id="requested_lecturer_supervisor_id" name="requested_lecturer_supervisor_id" class="mt-1">
                        <option value="">Belum tahu / ditentukan admin</option>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}" @selected((string) $selectedLecturerId === (string) $lecturer->id)>{{ $lecturer->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="requested_field_supervisor" value="Pembimbing Lapangan" />
                    <x-text-input id="requested_field_supervisor" name="requested_field_supervisor" class="mt-1 block w-full" :value="$selectedFieldSupervisor" />
                </div>
                <div>
                    <x-input-label for="requested_field_supervisor_phone" value="HP Pembimbing Lapangan" />
                    <x-text-input id="requested_field_supervisor_phone" name="requested_field_supervisor_phone" class="mt-1 block w-full" :value="$selectedFieldSupervisorPhone" />
                </div>
                <div>
                    <x-input-label for="requested_field_supervisor_email" value="Email Pembimbing Lapangan" />
                    <x-text-input id="requested_field_supervisor_email" name="requested_field_supervisor_email" type="email" class="mt-1 block w-full" :value="$selectedFieldSupervisorEmail" />
                    <p class="mt-1 text-xs text-gray-500">Wajib tersedia sebelum mahasiswa mengajukan Seminar.</p>
                </div>
            </div>

            <div>
                <x-input-label for="reason" value="Alasan / Catatan Mahasiswa" />
                <x-textarea-input id="reason" name="reason" rows="4" class="mt-1" required>{{ old('reason') }}</x-textarea-input>
            </div>

            <div class="flex justify-end">
                <div class="flex items-center gap-4">
                    <a class="silat-secondary-link" href="{{ route('student.supervisor-requests.index') }}">Lihat histori</a>
                    <x-primary-button :disabled="$hasPendingRequest || $enrollments->isEmpty()"><x-icon name="fa-paper-plane" /> Kirim Permohonan</x-primary-button>
                </div>
            </div>
        </form>
    </div></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const enrollment = document.getElementById('internship_enrollment_id');
            const lecturer = document.querySelector('[data-current-lecturer]');
            const fieldSupervisor = document.querySelector('[data-current-field-supervisor]');
            const fieldSupervisorEmail = document.querySelector('[data-current-field-supervisor-email]');
            const requestedLecturer = document.getElementById('requested_lecturer_supervisor_id');
            const requestedFieldSupervisor = document.getElementById('requested_field_supervisor');
            const requestedFieldSupervisorPhone = document.getElementById('requested_field_supervisor_phone');
            const requestedFieldSupervisorEmail = document.getElementById('requested_field_supervisor_email');

            const sync = (fillRequestedFields = false) => {
                const option = enrollment.selectedOptions[0];
                lecturer.textContent = option?.dataset.lecturer || '-';
                fieldSupervisor.textContent = option?.dataset.fieldSupervisor || '-';
                fieldSupervisorEmail.textContent = option?.dataset.fieldSupervisorEmail || '-';

                if (fillRequestedFields) {
                    requestedLecturer.value = option?.dataset.lecturerId || '';
                    requestedFieldSupervisor.value = option?.dataset.fieldSupervisorValue || '';
                    requestedFieldSupervisorPhone.value = option?.dataset.fieldSupervisorPhone || '';
                    requestedFieldSupervisorEmail.value = option?.dataset.fieldSupervisorEmailValue || '';
                }
            };

            enrollment.addEventListener('change', () => sync(true));
            sync();
        });
    </script>
</x-app-layout>
