@php
    $isTokenAccess = $accessMode === 'token';
@endphp

@if ($isTokenAccess)
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Portal Pembimbing Lapangan - SiLAT</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100 text-gray-900 antialiased">
@endif

<div class="{{ $isTokenAccess ? 'py-8' : '' }}">
    <div class="silat-shell space-y-6">
        <section class="silat-card p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Pembimbing Lapangan</p>
                    <h1 class="mt-1 text-2xl font-semibold text-gray-900">Portal Pembimbing Lapangan</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Akses {{ $isTokenAccess ? 'melalui token URL' : 'login email' }} untuk {{ $email }}.
                    </p>
                </div>
                <x-badge>{{ $isTokenAccess ? 'Token' : 'Login' }}</x-badge>
            </div>
        </section>

        @forelse ($enrollments as $enrollment)
            @php
                $dailyRows = $dailyRowsByEnrollment[$enrollment->id] ?? collect();
            @endphp
            <section class="silat-card">
                <div class="silat-section-header">
                    <div>
                        <h2 class="silat-section-title">{{ $enrollment->student?->full_name ?: '-' }}</h2>
                        <p class="silat-section-description">
                            {{ $enrollment->student?->npm ?: '-' }} · {{ $enrollment->studyProgram?->name ?: '-' }} · {{ $enrollment->internshipPeriod?->display_name ?: '-' }}
                        </p>
                    </div>
                    <x-badge variant="{{ $enrollment->status === 'active' ? 'success' : 'neutral' }}">{{ $enrollment->status }}</x-badge>
                </div>

                <div class="grid gap-4 p-5 md:grid-cols-3">
                    <div class="silat-stat-card">
                        <p class="silat-stat-label">Mitra</p>
                        <p class="mt-2 font-semibold text-gray-900">{{ $enrollment->internshipPlace?->name ?: '-' }}</p>
                    </div>
                    <div class="silat-stat-card">
                        <p class="silat-stat-label">Dosen Pembimbing</p>
                        <p class="mt-2 font-semibold text-gray-900">{{ $enrollment->lecturer?->name ?: '-' }}</p>
                    </div>
                    <div class="silat-stat-card">
                        <p class="silat-stat-label">Total Catatan</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($dailyRows->count(), 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto border-t border-gray-100">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Tanggal</th>
                                <th class="silat-table-cell">Jam</th>
                                <th class="silat-table-cell">Durasi</th>
                                <th class="silat-table-cell">Jarak</th>
                                <th class="silat-table-cell">Rencana / Realisasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($dailyRows as $row)
                                <tr>
                                    <td class="silat-table-cell whitespace-nowrap">{{ $row['date']?->format('d/m/Y') ?: '-' }}</td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        <div>Masuk: {{ $row['check_in']?->checked_at?->format('H:i') ?: '-' }}</div>
                                        <div>Pulang: {{ $row['check_out']?->checked_at?->format('H:i') ?: '-' }}</div>
                                    </td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        {{ $row['duration_minutes'] !== null ? floor($row['duration_minutes'] / 60).'j '.($row['duration_minutes'] % 60).'m' : '-' }}
                                    </td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        <div>Masuk: {{ $row['check_in']?->distance_meters !== null ? number_format($row['check_in']->distance_meters, 0, ',', '.').' m' : '-' }}</div>
                                        <div>Pulang: {{ $row['check_out']?->distance_meters !== null ? number_format($row['check_out']->distance_meters, 0, ',', '.').' m' : '-' }}</div>
                                    </td>
                                    <td class="silat-table-cell min-w-[360px]">
                                        <p><span class="font-semibold">Rencana:</span> {{ $row['check_in']?->note ?: '-' }}</p>
                                        <p class="mt-2"><span class="font-semibold">Realisasi:</span> {{ $row['check_out']?->note ?: '-' }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="silat-table-cell">
                                        <x-empty-state title="Belum ada catatan harian" icon="fa-clipboard" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <x-empty-state title="Tidak ada mahasiswa terkait" description="Email ini belum terhubung dengan data pembimbing lapangan pada enrollment aktif." icon="fa-user-lock" />
        @endforelse
    </div>
</div>

@if ($isTokenAccess)
    </body>
    </html>
@endif
