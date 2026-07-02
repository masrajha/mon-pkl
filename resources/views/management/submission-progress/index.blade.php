<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Akademik</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Review Progres Laporan') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Review unggahan laporan mahasiswa, beri catatan, dan setujui laporan final.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            <section class="silat-card p-5">
                <form method="GET" class="grid gap-4 md:grid-cols-[1fr_220px_260px_auto]">
                    <div>
                        <x-input-label for="q" :value="__('Cari')" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Nama, NPM, atau mitra" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full">
                            <option value="">Semua</option>
                            @foreach (['pending' => 'Menunggu Review', 'approved' => 'Disetujui', 'revision_required' => 'Perlu Revisi', 'rejected' => 'Ditolak'] as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="period_id" value="Periode Program" />
                        <x-select-input id="period_id" name="period_id" class="mt-1 block w-full">
                            <option value="">Semua periode</option>
                            @foreach ($periodOptions as $period)
                                <option value="{{ $period->id }}" @selected((int) $selectedPeriodId === (int) $period->id)>{{ $period->display_name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="flex items-end">
                        <x-primary-button>Filter</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="silat-card">
                <div class="silat-mobile-card-table-wrap overflow-x-auto">
                    <table class="silat-table silat-mobile-card-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Mahasiswa</th>
                                <th class="silat-table-cell">Program/Periode</th>
                                <th class="silat-table-cell">Dokumen</th>
                                <th class="silat-table-cell">Unggah</th>
                                <th class="silat-table-cell">Status</th>
                                <th class="silat-table-cell">Review</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($progressItems as $progress)
                                <tr>
                                    <td class="silat-table-cell" data-label="Mahasiswa">
                                        <div class="font-semibold text-gray-900">{{ $progress->enrollment?->student?->full_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $progress->enrollment?->student?->npm }} · {{ $progress->enrollment?->studyProgram?->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $progress->enrollment?->internshipPlace?->name }}</div>
                                    </td>
                                    <td class="silat-table-cell" data-label="Program/Periode">
                                        <div>{{ $progress->enrollment?->internshipPeriod?->program?->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $progress->enrollment?->internshipPeriod?->display_name }}</div>
                                    </td>
                                    <td class="silat-table-cell" data-label="Dokumen">
                                        <div>{{ $deadlineLabels[$progress->deadline_type] ?? str($progress->deadline_type)->replace('_', ' ')->title() }}</div>
                                        <a class="silat-secondary-link text-xs" href="{{ route('submission-progress.file', $progress) }}" target="_blank">Buka file</a>
                                        @if ($progress->sanction_points)
                                            <div class="text-xs text-rose-600">{{ $progress->sanction_points }} poin sanksi</div>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell" data-label="Unggah">{{ $progress->uploaded_at?->format('d/m/Y H:i') }}</td>
                                    <td class="silat-table-cell" data-label="Status">
                                        <x-badge variant="{{ $progress->status === 'approved' ? 'success' : (in_array($progress->status, ['revision_required', 'revision'], true) ? 'warning' : ($progress->status === 'rejected' ? 'danger' : 'neutral')) }}">{{ $statusLabels[$progress->status] ?? $progress->status }}</x-badge>
                                        @if ($progress->status === 'approved')
                                            <div class="mt-1 text-xs text-gray-500">Terkunci</div>
                                        @elseif ($progress->reviewer)
                                            <div class="mt-1 text-xs text-gray-500">Review oleh {{ $progress->reviewer->name }}</div>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell min-w-[320px]" data-label="Review">
                                        @if ($progress->status === 'approved')
                                            <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-900">
                                                Dokumen sudah disetujui dan tidak dapat diubah lagi.
                                                @if ($progress->lecturer_note)
                                                    <p class="mt-2 text-emerald-800">{{ $progress->lecturer_note }}</p>
                                                @endif
                                            </div>
                                            @php
                                                $isFinalized = (bool) $progress->enrollment?->finalAssessment;
                                                $hasCompletedSeminar = (bool) $progress->enrollment?->seminarRequests?->contains(fn ($seminarRequest) => $seminarRequest->status === 'completed' || filled($seminarRequest->completed_at) || filled($seminarRequest->seminar_score));
                                            @endphp
                                            @if ($isFinalized || $hasCompletedSeminar)
                                                <div class="mt-2 text-xs text-gray-500">
                                                    Buka ulang review tidak tersedia karena {{ $isFinalized ? 'nilai akhir sudah difinalisasi' : 'seminar sudah selesai atau nilai dosen sudah masuk' }}.
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    class="silat-btn-warning mt-3 px-3 py-2 text-xs"
                                                    x-data
                                                    x-on:click.prevent="$dispatch('open-modal', 'reopen-submission-progress-{{ $progress->id }}')"
                                                >
                                                    Buka Ulang
                                                </button>

                                                <x-modal name="reopen-submission-progress-{{ $progress->id }}" maxWidth="md" focusable>
                                                    <form method="POST" action="{{ route('management.submission-progress.reopen', $progress) }}" class="p-6">
                                                        @csrf
                                                        @method('PATCH')

                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Buka Ulang Review</p>
                                                            <h3 class="mt-1 text-lg font-semibold text-gray-900">{{ $progress->enrollment?->student?->full_name }}</h3>
                                                            <p class="mt-1 text-sm text-gray-500">
                                                                {{ $deadlineLabels[$progress->deadline_type] ?? str($progress->deadline_type)->replace('_', ' ')->title() }}
                                                            </p>
                                                        </div>

                                                        <div class="mt-5 space-y-4">
                                                            <div>
                                                                <x-input-label value="Buka ulang sebagai" />
                                                                <x-select-input name="reopen_status" class="mt-1 block w-full" required>
                                                                    <option value="pending" @selected(old('reopen_status') === 'pending')>Menunggu Review</option>
                                                                    <option value="revision_required" @selected(old('reopen_status') === 'revision_required')>Perlu Revisi</option>
                                                                </x-select-input>
                                                            </div>
                                                            <div>
                                                                <x-input-label value="Alasan buka ulang" />
                                                                <x-textarea-input name="reopen_reason" class="mt-1 block w-full" rows="3" placeholder="Contoh: ada bagian laporan yang perlu dikoreksi ulang" required>{{ old('reopen_reason') }}</x-textarea-input>
                                                                <x-input-error :messages="$errors->get('reopen_reason')" class="mt-2" />
                                                            </div>
                                                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                                                Jika ini laporan akhir, status laporan lengkap akan dilepas sampai dokumen disetujui kembali.
                                                            </div>
                                                        </div>

                                                        <div class="mt-6 flex justify-end gap-3">
                                                            <x-secondary-button x-on:click="$dispatch('close-modal', 'reopen-submission-progress-{{ $progress->id }}')">Batal</x-secondary-button>
                                                            <button type="submit" class="silat-btn-warning">Buka Ulang Review</button>
                                                        </div>
                                                    </form>
                                                </x-modal>
                                            @endif
                                        @else
                                            <form method="POST" action="{{ route('management.submission-progress.update', $progress) }}" class="space-y-3">
                                                @csrf
                                                @method('PATCH')
                                                <x-select-input name="status" class="block w-full" required>
                                                    @foreach (['approved' => 'Setujui', 'revision_required' => 'Minta Revisi', 'rejected' => 'Tolak'] as $value => $label)
                                                        <option value="{{ $value }}" @selected($progress->status === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </x-select-input>
                                                <x-textarea-input name="lecturer_note" class="block w-full" rows="2" placeholder="Catatan review untuk mahasiswa">{{ old('lecturer_note', $progress->lecturer_note) }}</x-textarea-input>
                                                <p class="text-xs text-gray-500">Catatan wajib diisi jika meminta revisi atau menolak dokumen.</p>
                                                <x-primary-button>Simpan Review</x-primary-button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada unggahan progres" icon="fa-file-circle-check" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4">{{ $progressItems->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
