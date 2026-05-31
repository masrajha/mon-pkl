<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan PKL {{ $enrollment->student?->npm }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 32px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        table { border-collapse: collapse; width: 100%; margin-top: 18px; font-size: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .meta { margin-top: 16px; display: grid; grid-template-columns: 180px 1fr; gap: 6px; font-size: 13px; }
        .signatures { margin-top: 48px; display: grid; grid-template-columns: 1fr 1fr; gap: 64px; font-size: 13px; }
        @media print { button { display: none; } body { margin: 20mm; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Cetak</button>
    <h1>Laporan Monitoring PKL</h1>
    <p>{{ $enrollment->internshipPeriod?->name }} · {{ $enrollment->studyProgram?->name }}</p>
    <div class="meta">
        <strong>Mahasiswa</strong><span>{{ $enrollment->student?->full_name }} / {{ $enrollment->student?->npm }}</span>
        <strong>Tempat PKL</strong><span>{{ $enrollment->internshipPlace?->name }}</span>
        <strong>Alamat</strong><span>{{ $enrollment->internshipPlace?->address }}</span>
        <strong>Dosen Pembimbing</strong><span>{{ $enrollment->lecturer?->name }}</span>
        <strong>Pembimbing Lapangan</strong><span>{{ $enrollment->field_supervisor }}</span>
    </div>
    <table>
        <thead><tr><th>No</th><th>Waktu</th><th>Status</th><th>Jarak</th><th>Catatan</th></tr></thead>
        <tbody>
            @foreach ($enrollment->checkIns as $checkIn)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $checkIn->checked_at?->format('d/m/Y H:i') }}</td><td>{{ $checkIn->type }}</td><td>{{ $checkIn->distance_meters !== null ? number_format($checkIn->distance_meters, 0, ',', '.').' m' : '-' }}</td><td>{{ $checkIn->note }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <div class="signatures">
        <div>Mengetahui,<br>Dosen Pembimbing<br><br><br><strong>{{ $enrollment->lecturer?->name }}</strong></div>
        <div>Pembimbing Lapangan<br><br><br><br><strong>{{ $enrollment->field_supervisor }}</strong></div>
    </div>
</body>
</html>
