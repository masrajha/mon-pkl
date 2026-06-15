<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Snapshot Dashboard Progres Peserta - SiLAT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-page { box-shadow: none !important; margin: 0 !important; width: 100% !important; }
            .avoid-break { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-950">
    <div class="no-print sticky top-0 z-10 border-b border-gray-200 bg-white/95 px-6 py-3 backdrop-blur">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3">
            <a href="{{ route('reports.operational-charts', request()->only(['scope', 'period_id', 'program_id', 'study_program_id', 'start_date', 'end_date'])) }}" class="silat-secondary-link">
                <x-icon name="fa-arrow-left" /> Kembali
            </a>
            <button type="button" onclick="window.print()" class="silat-btn">
                <x-icon name="fa-print" /> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <main class="print-page mx-auto my-6 w-[210mm] max-w-full bg-white p-8 shadow-sm">
        <header class="border-b-2 border-gray-900 pb-4">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-700">SiLAT - Analisis & Laporan</p>
            <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-950">Snapshot Dashboard Progres Peserta</h1>
                    <p class="mt-1 text-sm text-gray-600">Ringkasan kondisi peserta kegiatan berdasarkan filter aktif.</p>
                </div>
                <div class="text-right text-xs text-gray-600">
                    <p>Dicetak: {{ $printedAt->format('d/m/Y H:i') }}</p>
                    <p>Oleh: {{ $printedBy }}</p>
                </div>
            </div>
        </header>

        <section class="avoid-break mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-700">Filter Aktif</h2>
            <div class="mt-3 grid gap-3 text-sm md:grid-cols-2">
                <div><span class="font-semibold">Periode:</span> {{ $selectedPeriod?->display_name ?? 'Semua periode sesuai scope' }}</div>
                <div><span class="font-semibold">Program:</span> {{ $selectedProgram?->name ?? 'Semua program' }}</div>
                <div><span class="font-semibold">Prodi:</span> {{ $selectedStudyProgram?->name ?? 'Semua prodi' }}</div>
                <div><span class="font-semibold">Tanggal:</span> {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</div>
            </div>
        </section>

        <section class="avoid-break mt-5 grid gap-3 md:grid-cols-4">
            <div class="rounded-lg border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Peserta</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalEnrollments, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Pasangan Presensi Valid</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($validPairTotal, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Nilai Final</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($finalProgress['done'] ?? 0, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500">{{ number_format($finalProgress['percent'] ?? 0, 1, ',', '.') }}% selesai</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Peserta Berisiko</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format(($riskSummary['watch'] ?? 0) + ($riskSummary['risky'] ?? 0) + ($riskSummary['critical'] ?? 0), 0, ',', '.') }}</p>
            </div>
        </section>

        <section class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="avoid-break rounded-lg border border-gray-200 p-4">
                <h2 class="text-base font-bold">Tren Presensi Harian</h2>
                @php($maxAttendance = max(1, collect($attendanceTrend)->flatMap(fn ($row) => [$row['check_in'], $row['check_out'], $row['valid_pairs']])->max()))
                <div class="mt-4 space-y-2">
                    @forelse ($attendanceTrend as $row)
                        <div class="grid grid-cols-[3.5rem_minmax(0,1fr)_4.5rem] items-center gap-2 text-xs">
                            <span class="font-semibold text-gray-600">{{ $row['label'] }}</span>
                            <div class="space-y-1">
                                <div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-blue-600" style="width: {{ $row['check_in'] > 0 ? max(2, $row['check_in'] / $maxAttendance * 100) : 0 }}%;"></div></div>
                                <div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ $row['check_out'] > 0 ? max(2, $row['check_out'] / $maxAttendance * 100) : 0 }}%;"></div></div>
                                <div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-indigo-500" style="width: {{ $row['valid_pairs'] > 0 ? max(2, $row['valid_pairs'] / $maxAttendance * 100) : 0 }}%;"></div></div>
                            </div>
                            <span class="text-right text-gray-600">{{ $row['valid_pairs'] }} valid</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada data presensi pada filter ini.</p>
                    @endforelse
                </div>
                <div class="mt-3 flex flex-wrap gap-3 border-t border-gray-100 pt-3 text-xs text-gray-500">
                    <span><span class="inline-block h-2 w-5 rounded-full bg-blue-600"></span> Masuk</span>
                    <span><span class="inline-block h-2 w-5 rounded-full bg-emerald-500"></span> Pulang</span>
                    <span><span class="inline-block h-2 w-5 rounded-full bg-indigo-500"></span> Valid</span>
                </div>
            </div>

            <div class="avoid-break rounded-lg border border-gray-200 p-4">
                <h2 class="text-base font-bold">Status Laporan Lengkap</h2>
                <div class="mt-4 space-y-2">
                    @php($maxReport = max(1, collect($reportStatus)->max()))
                    @foreach ($reportStatusLabels as $key => $label)
                        @php($count = $reportStatus[$key] ?? 0)
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-semibold text-gray-700">{{ $label }}</span>
                                <span>{{ number_format($count, 0, ',', '.') }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-blue-600" style="width: {{ $count > 0 ? max(3, $count / $maxReport * 100) : 0 }}%;"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="avoid-break mt-5 rounded-lg border border-gray-200 p-4">
            <h2 class="text-base font-bold">Progress Status Nilai</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach ($assessmentProgress as $row)
                    <div class="rounded-lg border border-gray-200 p-3">
                        <p class="text-sm font-semibold">{{ $row['label'] }}</p>
                        <p class="mt-1 text-2xl font-bold">{{ number_format($row['done'], 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($row['percent'], 1, ',', '.') }}% selesai, {{ number_format($row['missing'], 0, ',', '.') }} belum</p>
                        <div class="mt-3 h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-blue-600" style="width: {{ max(3, $row['percent']) }}%;"></div></div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="avoid-break rounded-lg border border-gray-200 p-4">
                <h2 class="text-base font-bold">Peserta Berisiko</h2>
                <div class="mt-3 overflow-hidden rounded-md border border-gray-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr><th class="p-2">Mahasiswa</th><th class="p-2">Kategori</th><th class="p-2 text-right">Skor</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($riskRows as $row)
                                @php($enrollment = $row['enrollment'])
                                <tr>
                                    <td class="p-2"><span class="font-semibold">{{ $enrollment->student?->full_name ?? '-' }}</span><br><span class="text-gray-500">{{ $enrollment->student?->npm ?? '-' }}</span></td>
                                    <td class="p-2">{{ $row['category'] }}</td>
                                    <td class="p-2 text-right font-bold">{{ number_format($row['score'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="p-3 text-center text-gray-500">Tidak ada peserta berisiko pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="avoid-break rounded-lg border border-gray-200 p-4">
                <h2 class="text-base font-bold">Sanksi Tertinggi</h2>
                <div class="mt-3 overflow-hidden rounded-md border border-gray-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr><th class="p-2">Mahasiswa</th><th class="p-2">Prodi</th><th class="p-2 text-right">Poin</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($topSanctions as $row)
                                <tr>
                                    <td class="p-2"><span class="font-semibold">{{ $row['student'] }}</span><br><span class="text-gray-500">{{ $row['npm'] }}</span></td>
                                    <td class="p-2">{{ $row['study_program'] }}</td>
                                    <td class="p-2 text-right font-bold">{{ number_format($row['points'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="p-3 text-center text-gray-500">Tidak ada sanksi aktif pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <footer class="mt-6 border-t border-gray-200 pt-3 text-center text-xs text-gray-500">
            Dokumen snapshot ini dihasilkan oleh sistem SiLAT berdasarkan data dan scope akses pencetak.
        </footer>
    </main>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 350);
        });
    </script>
</body>
</html>
