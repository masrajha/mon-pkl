<x-guest-layout>
    <div class="space-y-5">
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3">
            <p class="text-sm font-semibold text-green-800">Dokumen Sah / Terverifikasi</p>
            <p class="mt-1 text-xs text-green-700">Data berikut cocok dengan arsip finalisasi nilai di SiLAT.</p>
        </div>

        <div>
            <h1 class="text-lg font-semibold text-gray-900">Verifikasi Berita Acara Nilai</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $finalAssessment->document_number ?: 'Nomor dokumen belum tersedia' }}</p>
        </div>

        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mahasiswa</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $enrollment->student?->full_name ?: '-' }} / {{ $enrollment->student?->npm ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Program</dt>
                <dd class="mt-1 text-gray-900">{{ $enrollment->internshipPeriod?->program?->name ?: '-' }} - {{ $enrollment->internshipPeriod?->name ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mitra</dt>
                <dd class="mt-1 text-gray-900">{{ $enrollment->internshipPlace?->name ?: '-' }}</dd>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-lg bg-gray-50 p-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Nilai</dt>
                    <dd class="mt-1 text-xl font-semibold text-gray-900">{{ number_format((float) $finalAssessment->final_score, 2, ',', '.') }}</dd>
                </div>
                <div class="rounded-lg bg-gray-50 p-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Huruf Mutu</dt>
                    <dd class="mt-1 text-xl font-semibold text-gray-900">{{ $letterGrade }}</dd>
                </div>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Difinalisasi</dt>
                <dd class="mt-1 text-gray-900">
                    {{ $finalAssessment->finalized_at?->format('d/m/Y H:i') ?: '-' }}
                    @if ($finalAssessment->finalizer)
                        oleh {{ $finalAssessment->finalizer->name }}
                    @endif
                </dd>
            </div>
        </dl>
    </div>
</x-guest-layout>
