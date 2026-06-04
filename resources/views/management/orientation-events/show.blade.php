<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Rekap Pembekalan') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        <section class="silat-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $event->name }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $event->location_name }} · {{ $event->studyProgram?->name ?: 'Semua prodi' }}</p>
                </div>
                <a class="silat-secondary-link" href="{{ route('management.orientation-events.index') }}">Kembali</a>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                <div class="rounded-lg bg-blue-50 p-4 text-blue-800"><p class="text-sm">Peserta</p><p class="text-2xl font-bold">{{ number_format($participants->count(), 0, ',', '.') }}</p></div>
                <div class="rounded-lg bg-green-50 p-4 text-green-800"><p class="text-sm">Presensi</p><p class="text-2xl font-bold">{{ number_format($presentCount, 0, ',', '.') }}</p></div>
                <div class="rounded-lg bg-rose-50 p-4 text-rose-800"><p class="text-sm">Belum Presensi</p><p class="text-2xl font-bold">{{ number_format($absentCount, 0, ',', '.') }}</p></div>
            </div>
        </section>

        <section class="silat-card mt-6 overflow-hidden">
            <div class="silat-section-header">
                <div><h3 class="silat-section-title">Daftar Peserta</h3><p class="silat-section-description">Jarak dihitung dari lokasi mahasiswa ke lokasi pembekalan.</p></div>
            </div>
            <div class="overflow-x-auto">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell">Mahasiswa</th><th class="silat-table-cell">Prodi</th><th class="silat-table-cell">Status</th><th class="silat-table-cell">Waktu</th><th class="silat-table-cell">Jarak</th></tr></thead>
                    <tbody>
                        @forelse ($participants as $enrollment)
                            @php
                                $attendance = $attendances->get($enrollment->student_id);
                            @endphp
                            <tr>
                                <td class="silat-table-cell"><div class="font-medium text-gray-900">{{ $enrollment->student?->full_name }}</div><div class="text-xs text-gray-500">{{ $enrollment->student?->npm }}</div></td>
                                <td class="silat-table-cell">{{ $enrollment->studyProgram?->name }}</td>
                                <td class="silat-table-cell"><x-badge :variant="$attendance ? 'success' : 'danger'">{{ $attendance ? 'Presensi' : 'Belum presensi' }}</x-badge></td>
                                <td class="silat-table-cell">{{ $attendance?->checked_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                <td class="silat-table-cell">{{ $attendance?->distance_meters === null ? '-' : number_format($attendance->distance_meters, 0, ',', '.').' m' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="silat-table-cell"><x-empty-state title="Belum ada peserta pada scope event ini" icon="fa-user-graduate" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
