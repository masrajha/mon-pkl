<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Akademik</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Validasi Pendaftaran') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Setujui, minta revisi, atau tolak pengajuan berdasarkan scope periode/prodi.</p>
        </div>
    </x-slot>
    <div class="py-8"><div class="silat-shell space-y-6">
        @include('management.partials.nav')
        @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif

        <x-table-controls class="silat-card" title="Antrean Validasi" description="Cari dan filter pendaftaran yang perlu diproses." search-placeholder="Cari mahasiswa, NPM, atau mitra...">
            <x-slot name="filters">
                <div><x-input-label value="Program" /><x-select-input name="program_id" class="mt-1"><option value="">Semua program</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected($selectedProgram === $program->id)>{{ $program->name }}</option>@endforeach</x-select-input></div>
                <div class="mt-3"><x-input-label value="Periode Program" /><x-select-input name="period_id" class="mt-1"><option value="">Semua periode program</option>@foreach ($periods as $period)<option value="{{ $period->id }}" @selected($selectedPeriod === $period->id)>{{ $period->display_name }}</option>@endforeach</x-select-input></div>
                <div class="mt-3"><x-input-label value="Prodi" /><x-select-input name="study_program_id" class="mt-1"><option value="">Semua prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected($selectedStudyProgram === $program->id)>{{ $program->name }}</option>@endforeach</x-select-input></div>
            </x-slot>
        </x-table-controls>

        <div class="space-y-4">
            @forelse ($enrollments as $enrollment)
                @php
                    $quotaKey = $enrollment->internship_period_id.'-'.$enrollment->study_program_id.'-'.$enrollment->internship_place_id;
                    $quotaCount = $quotaWarnings[$quotaKey] ?? null;
                    $settings = app(\App\Services\PeriodConfigurationService::class)->forPeriod($enrollment->internshipPeriod);
                @endphp
                <form method="POST" action="{{ route('management.enrollment-validations.update', $enrollment) }}" class="silat-card">
                    @csrf @method('PATCH')
                    <div class="grid gap-5 p-5 xl:grid-cols-[1fr_1.4fr]">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $enrollment->student?->full_name }}</h3>
                                    <p class="text-sm text-gray-500">{{ $enrollment->student?->npm }} · {{ $enrollment->studyProgram?->name }}</p>
                                </div>
                                <x-badge variant="{{ $enrollment->status === 'revision_required' ? 'warning' : 'info' }}">{{ $enrollment->status }}</x-badge>
                            </div>
                            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                                <p class="font-semibold text-gray-900">{{ $enrollment->internshipPeriod?->display_name }}</p>
                                <p>{{ $enrollment->internshipPlace?->name ?: 'Belum memilih mitra' }}</p>
                            </div>
                            <div class="mt-4 grid gap-2 text-sm text-gray-600 sm:grid-cols-2">
                                <span>KRS: {{ $enrollment->has_krs_pkl ? 'Ya' : 'Tidak' }}</span>
                                <span>SKS: {{ $enrollment->total_sks ?? '-' }}</span>
                                <span>Semester: {{ $enrollment->current_semester ?? '-' }}</span>
                                <span>IPK: {{ $enrollment->gpa ?? '-' }}</span>
                            </div>
                            <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4 text-sm">
                                <p class="font-semibold text-gray-900">Dokumen Bukti Akademik</p>
                                <p class="mt-1 text-gray-500">Transkrip Sementara + KRS Semester saat ini.</p>
                                @if ($enrollment->registration_document_path)
                                    <a class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700" href="{{ route('management.enrollment-validations.document', $enrollment) }}" target="_blank">
                                        <x-icon name="fa-file-pdf" /> Buka dokumen
                                    </a>
                                @else
                                    <p class="mt-2 text-sm text-red-600">Dokumen belum diunggah.</p>
                                @endif
                            </div>
                            @if ($quotaCount !== null)
                                <x-alert variant="warning" class="mt-4">Kuota minimal mitra belum terpenuhi: {{ $quotaCount }}/{{ $settings['enrollment']['min_place_quota'] }} mahasiswa.</x-alert>
                            @endif
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div><x-input-label for="lecturer_supervisor_id_{{ $enrollment->id }}" value="Dosen Pembimbing" /><x-select-input id="lecturer_supervisor_id_{{ $enrollment->id }}" name="lecturer_supervisor_id" class="mt-1"><option value="">Belum ditentukan</option>@foreach ($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($enrollment->lecturer_supervisor_id === $lecturer->id)>{{ $lecturer->name }}</option>@endforeach</x-select-input></div>
                            <div><x-input-label for="status_{{ $enrollment->id }}" value="Keputusan" /><x-select-input id="status_{{ $enrollment->id }}" name="status" class="mt-1" required><option value="active">Setujui</option><option value="revision_required" @selected($enrollment->status === 'revision_required')>Minta Revisi</option><option value="rejected">Tolak</option></x-select-input></div>
                            <div><x-input-label for="field_supervisor_{{ $enrollment->id }}" value="Pembimbing Lapangan" /><x-text-input id="field_supervisor_{{ $enrollment->id }}" name="field_supervisor" class="mt-1 block w-full" :value="$enrollment->field_supervisor" /></div>
                            <div><x-input-label for="field_supervisor_phone_{{ $enrollment->id }}" value="HP Pembimbing Lapangan" /><x-text-input id="field_supervisor_phone_{{ $enrollment->id }}" name="field_supervisor_phone" class="mt-1 block w-full" :value="$enrollment->field_supervisor_phone" /></div>
                            <div class="md:col-span-2"><x-input-label for="field_supervisor_email_{{ $enrollment->id }}" value="Email Pembimbing Lapangan" /><x-text-input id="field_supervisor_email_{{ $enrollment->id }}" name="field_supervisor_email" type="email" class="mt-1 block w-full" :value="$enrollment->field_supervisor_email" /><p class="mt-1 text-xs text-gray-500">Opsional saat validasi, wajib sebelum mahasiswa mengajukan Seminar.</p></div>
                            <div class="md:col-span-2"><x-input-label for="admin_note_{{ $enrollment->id }}" value="Catatan Verifikasi" /><x-textarea-input id="admin_note_{{ $enrollment->id }}" name="admin_note" rows="3" class="mt-1">{{ $enrollment->admin_note }}</x-textarea-input></div>
                            <div class="md:col-span-2 flex flex-wrap justify-end gap-2">
                                <button type="submit" name="status" value="revision_required" class="silat-btn-secondary">Minta Revisi</button>
                                <button type="submit" name="status" value="rejected" class="silat-btn-danger">Tolak</button>
                                <button type="submit" name="status" value="active" class="silat-btn">Setujui</button>
                            </div>
                        </div>
                    </div>
                </form>
            @empty
                <x-empty-state title="Tidak ada pendaftaran yang menunggu validasi" description="Data akan muncul saat mahasiswa mengirim pendaftaran baru." />
            @endforelse
        </div>
        <x-table-pagination :paginator="$enrollments" />
    </div></div>
</x-app-layout>
