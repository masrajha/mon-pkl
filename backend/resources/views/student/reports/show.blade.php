<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Laporan Saya') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $enrollment->student?->full_name }}</h3>
                    <p class="text-sm text-gray-500">{{ $enrollment->student?->npm }} · {{ $enrollment->studyProgram?->name }} · {{ $enrollment->internshipPeriod?->name }}</p>
                    <p class="mt-2 text-sm text-gray-700">{{ $enrollment->internshipPlace?->name ?: 'Tempat PKL belum ditentukan' }}</p>
                </div>
                <a class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white" href="{{ route('student.reports.print', $enrollment) }}" target="_blank">Cetak</a>
            </div>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="rounded-md border p-4"><p class="text-xs uppercase text-gray-500">Dosen Pembimbing</p><p class="font-medium">{{ $enrollment->lecturer?->name ?: 'Belum ditentukan' }}</p></div>
                <div class="rounded-md border p-4"><p class="text-xs uppercase text-gray-500">Pembimbing Lapangan</p><p class="font-medium">{{ $enrollment->field_supervisor ?: 'Belum diisi' }}</p></div>
                <div class="rounded-md border p-4"><p class="text-xs uppercase text-gray-500">Total Presensi</p><p class="font-medium">{{ $enrollment->checkIns->count() }}</p></div>
                <div class="rounded-md border p-4"><p class="text-xs uppercase text-gray-500">Status</p><p class="font-medium">{{ $enrollment->status }}</p></div>
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Jarak</th><th class="px-4 py-3">Catatan</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">@forelse ($enrollment->checkIns as $checkIn)<tr><td class="px-4 py-3">{{ $checkIn->checked_at?->format('d/m/Y H:i') }}</td><td class="px-4 py-3">{{ $checkIn->type }}</td><td class="px-4 py-3">{{ $checkIn->distance_meters !== null ? number_format($checkIn->distance_meters, 0, ',', '.').' m' : '-' }}</td><td class="px-4 py-3">{{ $checkIn->note ?: '-' }}</td></tr>@empty<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada data presensi.</td></tr>@endforelse</tbody>
                </table>
            </div>
        </div>
    </div></div>
</x-app-layout>
