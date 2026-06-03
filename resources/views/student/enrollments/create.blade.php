<x-app-layout>
    @php
        $isRevision = filled($enrollment);
        $formAction = $isRevision ? route('student.enrollments.update', $enrollment) : route('student.enrollments.store');
        $selectedProgram = old('program_id', $enrollment?->internshipPeriod?->program_id);
        $selectedPeriod = old('internship_period_id', $enrollment?->internship_period_id);
        $selectedPlace = old('internship_place_id', $enrollment?->internship_place_id ?? request('internship_place_id'));
    @endphp

    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Mahasiswa</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ $isRevision ? __('Revisi Pendaftaran Program') : __('Pendaftaran Program') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $isRevision ? 'Lengkapi atau perbaiki data sesuai catatan koordinator/admin.' : 'Ikuti tiga langkah ringkas untuk mengirim pendaftaran MBKM/KP.' }}</p>
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell">
        @if ($errors->any())<x-alert variant="danger" class="mb-6">{{ $errors->first() }}</x-alert>@endif
        @if ($isRevision)
            <x-alert variant="warning" class="mb-6">
                Pendaftaran ini diminta revisi.
                @if ($enrollment->admin_note)
                    <span class="mt-1 block font-medium text-amber-900">Catatan: {{ $enrollment->admin_note }}</span>
                @endif
            </x-alert>
        @endif

        <form method="POST" action="{{ $formAction }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @if ($isRevision)
                @method('PATCH')
            @endif

            <div class="grid gap-3 md:grid-cols-3" data-enrollment-steps>
                @foreach ([
                    ['step' => '1', 'title' => 'Program', 'desc' => 'Pilih program dan periode aktif.'],
                    ['step' => '2', 'title' => 'Mitra', 'desc' => 'Isi mitra dan pembimbing lapangan.'],
                    ['step' => '3', 'title' => 'Kelayakan', 'desc' => 'Isi syarat akademik dan dokumen.'],
                ] as $item)
                    <div class="silat-card p-3" data-step-indicator="{{ $item['step'] }}">
                        <div class="flex items-start gap-2">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white" data-step-number>{{ $item['step'] }}</span>
                            <div class="min-w-0"><p class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</p><p class="mt-1 text-xs text-gray-500">{{ $item['desc'] }}</p></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <section class="silat-card" data-enrollment-step="1">
                <div class="silat-section-header">
                    <div><h3 class="silat-section-title">1. Program dan Periode</h3><p class="silat-section-description">Periode selalu melekat pada Program Kegiatan.</p></div>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-3">
                    <div>
                        <x-input-label for="program_id" value="Program Kegiatan" />
                        <x-select-input id="program_id" name="program_id" class="mt-1" required>
                            <option value="">Pilih program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected($selectedProgram == $program->id)>{{ $program->name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="internship_period_id" value="Periode Program" />
                        <x-select-input id="internship_period_id" name="internship_period_id" class="mt-1" required>
                            <option value="">Pilih periode program</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" data-program-id="{{ $period->program_id }}" @selected($selectedPeriod == $period->id)>{{ $period->display_name }}</option>
                            @endforeach
                        </x-select-input>
                        @if ($isRevision)
                            <p class="mt-1 text-xs text-gray-500">Periode tidak dapat diubah saat revisi.</p>
                        @endif
                    </div>
                    <div>
                        <x-input-label value="Program Studi" />
                        <div class="mt-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">
                            <span class="font-medium text-gray-900">{{ $student->studyProgram?->name ?: 'Belum diisi' }}</span>
                            @if ($student->studyProgram?->code)
                                <span class="text-gray-500">({{ $student->studyProgram->code }})</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Mengikuti program studi pada profil mahasiswa.</p>
                    </div>
                </div>
                <div class="flex justify-end border-t border-gray-100 p-5">
                    <button type="button" class="silat-btn" data-enrollment-next>Lanjutkan</button>
                </div>
            </section>

            <section class="silat-card" data-enrollment-step="2" hidden>
                <div class="silat-section-header">
                    <div><h3 class="silat-section-title">2. Mitra dan Pembimbing Lapangan</h3><p class="silat-section-description">Pilih mitra jika sudah tersedia, atau lanjutkan tanpa mitra untuk mengajukan baru.</p></div>
                    <a class="silat-secondary-link" href="{{ route('student.proposals.create') }}">Ajukan mitra baru</a>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <x-input-label for="internship_place_id" value="Mitra / Tempat Kegiatan" />
                        <x-select-input id="internship_place_id" name="internship_place_id" class="mt-1">
                            <option value="">Belum ada / akan mengajukan baru</option>
                            @foreach ($places as $place)
                                <option value="{{ $place->id }}" @selected($selectedPlace == $place->id)>{{ $place->name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="contact_student_phone" value="HP Kontak Mahasiswa" />
                        <x-text-input id="contact_student_phone" name="contact_student_phone" class="mt-1 block w-full" :value="old('contact_student_phone', $enrollment?->contact_student_phone ?? $student->phone)" required />
                    </div>
                    <div>
                        <x-input-label for="field_supervisor" value="Pembimbing Lapangan" />
                        <x-text-input id="field_supervisor" name="field_supervisor" class="mt-1 block w-full" :value="old('field_supervisor', $enrollment?->field_supervisor)" />
                    </div>
                    <div>
                        <x-input-label for="field_supervisor_phone" value="HP Pembimbing Lapangan" />
                        <x-text-input id="field_supervisor_phone" name="field_supervisor_phone" class="mt-1 block w-full" :value="old('field_supervisor_phone', $enrollment?->field_supervisor_phone)" />
                    </div>
                    <div>
                        <x-input-label for="field_supervisor_email" value="Email Pembimbing Lapangan" />
                        <x-text-input id="field_supervisor_email" name="field_supervisor_email" type="email" class="mt-1 block w-full" :value="old('field_supervisor_email', $enrollment?->field_supervisor_email)" />
                        <p class="mt-1 text-xs text-gray-500">Opsional saat pendaftaran. Wajib dilengkapi sebelum pengajuan seminar.</p>
                    </div>
                </div>
                <div class="flex flex-wrap justify-between gap-3 border-t border-gray-100 p-5">
                    <button type="button" class="silat-btn-secondary" data-enrollment-prev>Kembali</button>
                    <button type="button" class="silat-btn" data-enrollment-next>Lanjutkan</button>
                </div>
            </section>

            <section class="silat-card" data-enrollment-step="3" hidden>
                <div class="silat-section-header">
                    <div><h3 class="silat-section-title">3. Rule Program, Kelayakan Akademik, dan Dokumen Bukti</h3><p class="silat-section-description">Lengkapi syarat akademik, unggah bukti, lalu kirim pendaftaran.</p></div>
                    <x-badge>Rule Kerja Praktik</x-badge>
                </div>
                <div class="space-y-4 p-5">
                    <x-alert variant="info">Pastikan data SKS, semester, dan IPK sesuai kondisi akademik saat pendaftaran dikirim.</x-alert>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="has_krs_pkl" value="1" @checked(old('has_krs_pkl', $enrollment?->has_krs_pkl)) class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" required>
                        <span>Saya sudah mengambil Kerja Praktik atau program terkait pada KRS semester ini.</span>
                    </label>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div><x-input-label for="total_sks" value="Total SKS" /><x-text-input id="total_sks" name="total_sks" type="number" class="mt-1 block w-full" :value="old('total_sks', $enrollment?->total_sks)" required /></div>
                        <div><x-input-label for="current_semester" value="Semester" /><x-text-input id="current_semester" name="current_semester" type="number" class="mt-1 block w-full" :value="old('current_semester', $enrollment?->current_semester)" required /></div>
                        <div><x-input-label for="gpa" value="IPK" /><x-text-input id="gpa" name="gpa" type="number" step="0.01" class="mt-1 block w-full" :value="old('gpa', $enrollment?->gpa)" required /></div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm font-semibold text-gray-900">Dokumen Bukti Akademik</p>
                        <p class="mt-1 text-sm text-gray-600">Transkrip Sementara + KRS Semester saat ini.</p>
                    </div>
                    @if ($isRevision && $enrollment?->registration_document_path)
                        <p class="text-sm text-gray-600">Dokumen sebelumnya sudah tersimpan. Unggah file baru hanya jika perlu mengganti dokumen.</p>
                    @endif
                    <div>
                        <x-input-label for="registration_document" value="File Bukti Akademik" />
                        <input id="registration_document" name="registration_document" type="file" accept="application/pdf,.pdf" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100" @required(! $isRevision || ! $enrollment?->registration_document_path)>
                        <p class="mt-1 text-xs text-gray-500">Maksimal 5 MB. Gabungkan transkrip sementara dan KRS semester saat ini dalam satu PDF.</p>
                        <x-input-error :messages="$errors->get('registration_document')" />
                    </div>
                </div>
                <div class="flex flex-col gap-4 border-t border-gray-100 p-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="silat-section-title">Kirim Pendaftaran</h3>
                        <p class="silat-section-description">Pendaftaran akan masuk ke antrean validasi admin/koordinator.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="silat-btn-secondary" data-enrollment-prev>Kembali</button>
                        <x-primary-button><x-icon name="fa-paper-plane" /> {{ $isRevision ? 'Kirim Revisi' : 'Kirim Pendaftaran' }}</x-primary-button>
                    </div>
                </div>
            </section>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const program = document.getElementById('program_id');
                const period = document.getElementById('internship_period_id');
                const syncPeriods = () => {
                    [...period.options].forEach((option) => {
                        if (! option.value) return;
                        option.hidden = program.value && option.dataset.programId && option.dataset.programId !== program.value;
                    });
                    if (period.selectedOptions[0]?.hidden) period.value = '';
                };
                program.addEventListener('change', syncPeriods);
                syncPeriods();

                const panels = [...document.querySelectorAll('[data-enrollment-step]')];
                const indicators = [...document.querySelectorAll('[data-step-indicator]')];
                let activeStep = 1;

                const showStep = (step) => {
                    activeStep = step;

                    panels.forEach((panel) => {
                        panel.hidden = Number(panel.dataset.enrollmentStep) !== activeStep;
                    });

                    indicators.forEach((indicator) => {
                        const isActive = Number(indicator.dataset.stepIndicator) === activeStep;
                        indicator.classList.toggle('ring-2', isActive);
                        indicator.classList.toggle('ring-blue-200', isActive);
                        indicator.classList.toggle('bg-blue-50', isActive);
                        indicator.querySelector('[data-step-number]')?.classList.toggle('bg-blue-700', isActive);
                    });

                    panels.find((panel) => Number(panel.dataset.enrollmentStep) === activeStep)?.scrollIntoView({ block: 'start', behavior: 'smooth' });
                };

                const currentPanelIsValid = () => {
                    const panel = panels.find((item) => Number(item.dataset.enrollmentStep) === activeStep);
                    const fields = [...(panel?.querySelectorAll('input, select, textarea') ?? [])];
                    const invalid = fields.find((field) => ! field.checkValidity());

                    if (invalid) {
                        invalid.reportValidity();
                        return false;
                    }

                    return true;
                };

                document.querySelectorAll('[data-enrollment-next]').forEach((button) => {
                    button.addEventListener('click', () => {
                        if (currentPanelIsValid()) {
                            showStep(Math.min(activeStep + 1, 3));
                        }
                    });
                });

                document.querySelectorAll('[data-enrollment-prev]').forEach((button) => {
                    button.addEventListener('click', () => showStep(Math.max(activeStep - 1, 1)));
                });

                showStep({{ $errors->any() ? 3 : 1 }});
            });
        </script>
    </div></div>
</x-app-layout>
