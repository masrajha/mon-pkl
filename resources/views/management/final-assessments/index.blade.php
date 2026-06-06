<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Akademik</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">Finalisasi Nilai</h2>
            <p class="mt-1 text-sm text-gray-500">Hitung nilai akhir dari nilai dosen, pembimbing lapangan, dan pengurangan sanksi.</p>
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

            <section class="silat-card p-5">
                <form method="GET" class="grid gap-4 md:grid-cols-[1fr_260px_260px_auto]">
                    <div>
                        <x-input-label for="q" value="Cari" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Mahasiswa, NPM, periode, atau mitra" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full">
                            <option value="">Semua status</option>
                            <option value="ready" @selected($selectedStatus === 'ready')>Siap difinalisasi</option>
                            <option value="finalized" @selected($selectedStatus === 'finalized')>Sudah final</option>
                            <option value="not_ready" @selected($selectedStatus === 'not_ready')>Belum siap</option>
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
                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Mahasiswa</th>
                                <th class="silat-table-cell">Komponen Nilai</th>
                                <th class="silat-table-cell">Pengurangan</th>
                                <th class="silat-table-cell">Nilai Akhir</th>
                                <th class="silat-table-cell">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($summaries as $summary)
                                @php
                                    $enrollment = $summary['enrollment'];
                                    $finalAssessment = $summary['finalAssessment'];
                                    $deductionValue = old('final_deduction', $finalAssessment?->final_deduction ?? $summary['suggestedDeduction']);
                                @endphp
                                <tr>
                                    <td class="silat-table-cell min-w-[240px]">
                                        <p class="font-semibold text-gray-900">{{ $enrollment->student?->full_name ?: '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $enrollment->student?->npm ?: '-' }} · {{ $enrollment->studyProgram?->name ?: '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $enrollment->internshipPeriod?->display_name ?: '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $enrollment->internshipPlace?->name ?: '-' }}</p>
                                    </td>
                                    <td class="silat-table-cell min-w-[280px]">
                                        <div class="space-y-2 text-sm">
                                            <div class="flex items-center justify-between gap-3">
                                                <span>Dosen Pembimbing <span class="text-xs text-gray-500">(50%)</span></span>
                                                <span class="font-semibold tabular-nums text-gray-900">{{ $summary['lecturerScore'] !== null ? number_format($summary['lecturerScore'], 2, ',', '.') : '-' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-3">
                                                <span>Pembimbing Lapangan <span class="text-xs text-gray-500">(50%)</span></span>
                                                <span class="font-semibold tabular-nums text-gray-900">{{ $summary['fieldSupervisorScore'] !== null ? number_format($summary['fieldSupervisorScore'], 2, ',', '.') : '-' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-2">
                                                <span>Nilai dasar</span>
                                                <span class="font-semibold tabular-nums text-gray-900">{{ $summary['baseScore'] !== null ? number_format($summary['baseScore'], 2, ',', '.') : '-' }}</span>
                                            </div>
                                        </div>
                                        @unless ($summary['ready'])
                                            <p class="mt-3 text-xs text-amber-700">Belum siap: nilai dosen dan nilai pembimbing lapangan wajib lengkap.</p>
                                        @endunless
                                    </td>
                                    <td class="silat-table-cell min-w-[220px]">
                                        <div class="space-y-2 text-sm">
                                            <div class="flex items-center justify-between gap-3">
                                                <span>Total sanksi</span>
                                                <span class="font-semibold tabular-nums text-gray-900">{{ number_format($summary['suggestedDeduction'], 2, ',', '.') }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-3">
                                                <span>Suggest pengurangan</span>
                                                <span class="font-semibold tabular-nums text-gray-900">{{ number_format($summary['suggestedDeduction'], 2, ',', '.') }}</span>
                                            </div>
                                            @if ($finalAssessment)
                                                <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-2">
                                                    <span>Pengurangan final</span>
                                                    <span class="font-semibold tabular-nums text-gray-900">{{ number_format((float) $finalAssessment->final_deduction, 2, ',', '.') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="silat-table-cell min-w-[180px]">
                                        @if ($finalAssessment)
                                            <x-badge variant="success">Sudah final</x-badge>
                                            <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-900">{{ number_format((float) $finalAssessment->final_score, 2, ',', '.') }}</p>
                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ $finalAssessment->finalized_at?->format('d/m/Y H:i') }}
                                                @if ($finalAssessment->finalizer)
                                                    · {{ $finalAssessment->finalizer->name }}
                                                @endif
                                            </p>
                                        @elseif ($summary['ready'])
                                            <x-badge variant="warning">Siap difinalisasi</x-badge>
                                            <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-900">{{ number_format($summary['suggestedFinalScore'], 2, ',', '.') }}</p>
                                            <p class="mt-1 text-xs text-gray-500">Perkiraan dari suggest pengurangan.</p>
                                        @else
                                            <x-badge variant="neutral">Belum siap</x-badge>
                                            <p class="mt-2 text-sm text-gray-500">Menunggu komponen nilai lengkap.</p>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell min-w-[280px]">
                                        @if ($summary['ready'])
                                            <form method="POST" action="{{ route('management.final-assessments.store', $enrollment) }}" class="space-y-3 rounded-lg border border-gray-200 p-3">
                                                @csrf
                                                <div>
                                                    <x-input-label value="Nomor Berita Acara" />
                                                    <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                                        {{ $finalAssessment?->document_number ?: 'Akan dibuat saat finalisasi disimpan' }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <x-input-label for="final_deduction_{{ $enrollment->id }}" value="Pengurangan Final" />
                                                    <x-text-input id="final_deduction_{{ $enrollment->id }}" name="final_deduction" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full" :value="$deductionValue" required />
                                                </div>
                                                <div>
                                                    <x-input-label for="final_note_{{ $enrollment->id }}" value="Catatan Koordinator/Admin" />
                                                    <x-textarea-input id="final_note_{{ $enrollment->id }}" name="note" rows="2" class="mt-1 block w-full text-sm">{{ old('note', $finalAssessment?->note) }}</x-textarea-input>
                                                </div>
                                                <x-primary-button>
                                                    <x-icon name="fa-floppy-disk" />
                                                    Simpan Finalisasi
                                                </x-primary-button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-500">Tidak ada aksi.</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="silat-table-cell">
                                        <x-empty-state title="Tidak ada data" description="Data finalisasi nilai tidak ditemukan untuk filter saat ini." icon="fa-calculator" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4">{{ $enrollments->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
