<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Catatan Harian - {{ $enrollment->student?->full_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; vertical-align: top; }
        th { background: #f1f5f9; text-align: left; }
        .meta { margin-top: 10px; line-height: 1.6; }
        .signature { min-height: 54px; }
        .nowrap { white-space: nowrap; }
        .note p { margin: 0 0 12px; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Cetak</button>
    <h1>Form Catatan Harian Program</h1>
    <div class="meta">
        <div>Nama: <strong>{{ $enrollment->student?->full_name }}</strong></div>
        <div>NPM/Prodi: {{ $enrollment->student?->npm }} / {{ $enrollment->studyProgram?->name }}</div>
        <div>Periode: {{ $enrollment->internshipPeriod?->display_name }}</div>
        <div>Mitra: {{ $enrollment->internshipPlace?->name }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 140px;">Tanggal</th>
                <th style="width: 100px;">Jam</th>
                <th style="width: 110px;">Jarak</th>
                <th>Catatan</th>
                <th style="width: 120px;">Paraf</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dailyActivityRows as $row)
                <tr>
                    <td class="nowrap">{{ $row['date']?->translatedFormat('l, d M Y') }}</td>
                    <td class="nowrap">
                        Masuk: {{ $row['check_in']?->checked_at?->format('H:i:s') ?: '-' }}<br>
                        Pulang: {{ $row['check_out']?->checked_at?->format('H:i:s') ?: '-' }}<br>
                        Durasi: {{ $row['duration_minutes'] !== null ? number_format($row['duration_minutes'] / 60, 2, ',', '.') : '-' }}
                    </td>
                    <td class="nowrap">
                        Masuk: {{ $row['check_in']?->distance_meters !== null ? number_format($row['check_in']->distance_meters, 2, ',', '.') : '-' }}<br>
                        Pulang: {{ $row['check_out']?->distance_meters !== null ? number_format($row['check_out']->distance_meters, 2, ',', '.') : '-' }}
                    </td>
                    <td class="note">
                        <p><strong>Rencana:</strong> {{ $row['check_in']?->note ?: '-' }}</p>
                        <p><strong>Realisasi:</strong> {{ $row['check_out']?->note ?: '-' }}</p>
                    </td>
                    <td class="signature"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Belum ada catatan harian dari pasangan presensi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
