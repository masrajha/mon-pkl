<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Akademik</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Validasi Pendaftaran') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Setujui, minta revisi, atau tolak pengajuan berdasarkan scope periode/prodi.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            @include('management.partials.nav')

            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert variant="danger">{{ $errors->first() }}</x-alert>
            @endif

            <x-table-controls class="silat-card" title="Antrean Validasi" description="Cari dan filter pendaftaran yang perlu diproses." search-placeholder="Cari mahasiswa, NPM, atau mitra...">
                <x-slot name="filters">
                    <div>
                        <x-input-label value="Program" />
                        <x-select-input name="program_id" class="mt-1">
                            <option value="">Semua program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected($selectedProgram === $program->id)>{{ $program->name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="mt-3">
                        <x-input-label value="Periode Program" />
                        <x-select-input name="period_id" class="mt-1">
                            <option value="">Semua periode program</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected($selectedPeriod === $period->id)>{{ $period->display_name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="mt-3">
                        <x-input-label value="Prodi" />
                        <x-select-input name="study_program_id" class="mt-1">
                            <option value="">Semua prodi</option>
                            @foreach ($studyPrograms as $program)
                                <option value="{{ $program->id }}" @selected($selectedStudyProgram === $program->id)>{{ $program->name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                </x-slot>
            </x-table-controls>

            <div class="silat-card overflow-hidden">
                <form method="POST" action="{{ route('management.enrollment-validations.bulk') }}">
                    @csrf
                    <div class="silat-table-toolbar">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Bulk action</p>
                            <p class="text-xs text-gray-500">Pilih pendaftaran, lalu jalankan keputusan yang sama.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" name="action" value="active" class="silat-btn">
                                <x-icon name="fa-check" class="mr-1" /> Setujui
                            </button>
                            <button type="submit" name="action" value="revision_required" class="silat-btn-secondary">
                                <x-icon name="fa-rotate-left" class="mr-1" /> Revisi
                            </button>
                            <button type="submit" name="action" value="rejected" class="silat-btn-danger">
                                <x-icon name="fa-xmark" class="mr-1" /> Tolak
                            </button>
                        </div>
                    </div>

                    <div class="silat-table-wrap">
                        <table class="silat-table">
                            <thead class="silat-table-head">
                                <tr>
                                    <th class="silat-table-cell">
                                        <input type="checkbox" class="rounded border-gray-300" onclick="document.querySelectorAll('[data-enrollment-checkbox]').forEach((el) => el.checked = this.checked)">
                                    </th>
                                    <th class="silat-table-cell">Program/Periode</th>
                                    <th class="silat-table-cell">Nama/NPM</th>
                                    <th class="silat-table-cell">Prodi</th>
                                    <th class="silat-table-cell">SKS/IPK</th>
                                    <th class="silat-table-cell">Lampiran</th>
                                    <th class="silat-table-cell">Catatan Verifikasi</th>
                                    <th class="silat-table-cell text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($enrollments as $enrollment)
                                    @php
                                        $quotaKey = $enrollment->internship_period_id.'-'.$enrollment->study_program_id.'-'.$enrollment->internship_place_id;
                                        $quotaCount = $quotaWarnings[$quotaKey] ?? null;
                                        $settings = app(\App\Services\PeriodConfigurationService::class)->forPeriod($enrollment->internshipPeriod);
                                        $statusVariant = $enrollment->status === 'revision_required' ? 'warning' : 'info';
                                    @endphp
                                    <tr>
                                        <td class="silat-table-cell align-top">
                                            <input data-enrollment-checkbox type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->id }}" class="rounded border-gray-300">
                                        </td>
                                        <td class="silat-table-cell align-top">
                                            <div class="font-medium text-gray-900">{{ $enrollment->internshipPeriod?->program?->name ?: '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $enrollment->internshipPeriod?->display_name ?: '-' }}</div>
                                            <div class="mt-2 text-xs text-gray-600">{{ $enrollment->internshipPlace?->name ?: 'Belum memilih mitra' }}</div>
                                            <x-badge class="mt-2" :variant="$statusVariant">{{ Str::headline($enrollment->status) }}</x-badge>
                                            @if ($quotaCount !== null)
                                                <div class="mt-2 rounded-md bg-amber-50 px-2 py-1 text-xs text-amber-700">
                                                    Kuota mitra {{ $quotaCount }}/{{ $settings['enrollment']['min_place_quota'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="silat-table-cell align-top">
                                            <div class="font-medium text-gray-900">{{ $enrollment->student?->full_name ?: '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $enrollment->student?->npm ?: '-' }}</div>
                                        </td>
                                        <td class="silat-table-cell align-top text-gray-700">
                                            {{ $enrollment->studyProgram?->name ?: '-' }}
                                        </td>
                                        <td class="silat-table-cell align-top text-gray-700">
                                            <div>SKS: {{ $enrollment->total_sks ?? '-' }}</div>
                                            <div>IPK: {{ $enrollment->gpa ?? '-' }}</div>
                                            <div class="text-xs text-gray-500">Semester {{ $enrollment->current_semester ?? '-' }} · KRS {{ $enrollment->has_krs_pkl ? 'Ya' : 'Tidak' }}</div>
                                        </td>
                                        <td class="silat-table-cell align-top">
                                            @if ($enrollment->registration_document_path)
                                                <a class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700" href="{{ route('management.enrollment-validations.document', $enrollment) }}" target="_blank">
                                                    <x-icon name="fa-file-pdf" /> Buka
                                                </a>
                                            @else
                                                <span class="text-sm text-red-600">Belum ada</span>
                                            @endif
                                        </td>
                                        <td class="silat-table-cell align-top">
                                            <x-textarea-input name="admin_notes[{{ $enrollment->id }}]" rows="3" class="min-w-64 text-sm" placeholder="Catatan untuk mahasiswa">{{ old('admin_notes.'.$enrollment->id, $enrollment->admin_note) }}</x-textarea-input>
                                        </td>
                                        <td class="silat-table-cell align-top">
                                            <div class="flex justify-end gap-1.5">
                                                <button type="submit" name="action" value="active" formaction="{{ route('management.enrollment-validations.bulk') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 text-sm text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2" title="Setujui" aria-label="Setujui pendaftaran" onclick="this.form.single_enrollment_id.value = '{{ $enrollment->id }}'">
                                                    <i class="fa-regular fa-circle-check" role="img" aria-label="Ikon setujui"></i>
                                                </button>
                                                <button type="submit" name="action" value="revision_required" formaction="{{ route('management.enrollment-validations.bulk') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-200 bg-amber-50 text-sm text-amber-700 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" title="Revisi" aria-label="Minta revisi pendaftaran" onclick="this.form.single_enrollment_id.value = '{{ $enrollment->id }}'">
                                                    <i class="fas fa-rotate-left" role="img" aria-label="Ikon revisi"></i>
                                                </button>
                                                <button type="submit" name="action" value="rejected" formaction="{{ route('management.enrollment-validations.bulk') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-sm text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2" title="Tolak" aria-label="Tolak pendaftaran" onclick="this.form.single_enrollment_id.value = '{{ $enrollment->id }}'">
                                                    <i class="fas fa-ban" role="img" aria-label="Ikon tolak"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="silat-table-cell">
                                            <x-empty-state title="Tidak ada pendaftaran yang menunggu validasi" description="Data akan muncul saat mahasiswa mengirim pendaftaran baru." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <input type="hidden" name="single_enrollment_id" value="">
                </form>

                <x-table-pagination :paginator="$enrollments" />
            </div>
        </div>
    </div>
</x-app-layout>
