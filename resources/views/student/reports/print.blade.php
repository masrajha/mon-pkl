<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Program {{ $enrollment->student?->npm }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 32px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 16px; margin: 28px 0 10px; }
        table { border-collapse: collapse; width: 100%; margin-top: 18px; font-size: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; color: #4b5563; font-size: 11px; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        td.bg-success, span.bg-success { background: #16a34a; color: #ffffff; font-weight: 700; }
        td.bg-warning, span.bg-warning { background: #f59e0b; color: #111827; font-weight: 700; }
        td.bg-danger, span.bg-danger { background: #dc2626; color: #ffffff; font-weight: 700; }
        .identity { margin-top: 18px; display: grid; grid-template-columns: 92px 1fr; gap: 18px; align-items: start; }
        .profile-photo { width: 88px; height: 112px; border: 1px solid #d1d5db; object-fit: cover; }
        .profile-placeholder { width: 88px; height: 112px; border: 1px solid #d1d5db; display: flex; align-items: center; justify-content: center; background: #f3f4f6; color: #64748b; font-size: 28px; font-weight: 700; }
        .meta { margin-top: 16px; display: grid; grid-template-columns: 180px 1fr; gap: 6px; font-size: 13px; }
        .charts { margin-top: 28px; display: grid; gap: 18px; }
        .chart { width: 100%; height: 260px; border: 1px solid #e5e7eb; border-radius: 8px; }
        .signatures { margin-top: 48px; display: grid; grid-template-columns: 1fr 1fr; gap: 64px; font-size: 13px; }
        .empty { margin-top: 20px; border: 1px dashed #cbd5e1; padding: 18px; color: #64748b; text-align: center; }
        @media print { button { display: none; } body { margin: 20mm; } .chart { break-inside: avoid; } }
    </style>
    <script src="https://www.gstatic.com/charts/loader.js"></script>
</head>
<body>
    @php
        $studentPhotoUrl = \App\Support\PublicStorage::url($enrollment->student?->user?->avatar_url);
        $chartRows = $attendanceRows->map(function (array $row) {
            $checkIn = $row['check_in'];
            $checkOut = $row['check_out'];

            $timeToHour = fn ($date) => $date ? ((int) $date->format('H') + ((int) $date->format('i') / 60) + ((int) $date->format('s') / 3600)) : null;

            return [
                'label' => $row['date']?->format('d/m'),
                'date' => $row['date']?->translatedFormat('l, d M Y'),
                'checkInTime' => $checkIn?->checked_at?->format('H:i:s'),
                'checkOutTime' => $checkOut?->checked_at?->format('H:i:s'),
                'checkInHour' => $timeToHour($checkIn?->checked_at),
                'checkOutHour' => $timeToHour($checkOut?->checked_at),
                'durationHours' => $row['duration_minutes'] !== null ? round($row['duration_minutes'] / 60, 2) : null,
                'checkInDistance' => $checkIn?->distance_meters !== null ? round((float) $checkIn->distance_meters, 2) : null,
                'checkOutDistance' => $checkOut?->distance_meters !== null ? round((float) $checkOut->distance_meters, 2) : null,
            ];
        })->values();
    @endphp

    <button onclick="window.print()">Cetak</button>
    <h1>Laporan Monitoring Program</h1>
    <p>{{ $enrollment->internshipPeriod?->display_name }} &middot; {{ $enrollment->studyProgram?->name }}</p>

    <div class="identity">
        <div>
            @if ($studentPhotoUrl)
                <img src="{{ $studentPhotoUrl }}" alt="Foto {{ $enrollment->student?->full_name }}" class="profile-photo">
            @else
                <div class="profile-placeholder">{{ Str::of($enrollment->student?->full_name ?: 'M')->substr(0, 1)->upper() }}</div>
            @endif
        </div>
        <div class="meta">
            <strong>Mahasiswa</strong><span>{{ $enrollment->student?->full_name }} / {{ $enrollment->student?->npm }}</span>
            <strong>Mitra</strong><span>{{ $enrollment->internshipPlace?->name }}</span>
            <strong>Alamat</strong><span>{{ $enrollment->internshipPlace?->address }}</span>
            <strong>Dosen Pembimbing</strong><span>{{ $enrollment->lecturer?->name }}</span>
            <strong>Pembimbing Lapangan</strong><span>{{ $enrollment->field_supervisor }}</span>
            <strong>Email Pembimbing Lapangan</strong><span>{{ $enrollment->field_supervisor_email ?: '-' }}</span>
        </div>
    </div>
    <h2>Grafik Kehadiran</h2>
    @if ($attendanceRows->isNotEmpty())
        <div class="charts">
            <div id="chart-time" class="chart"></div>
            <div id="chart-distance" class="chart"></div>
            <div id="chart-duration" class="chart"></div>
        </div>
    @else
        <div class="empty">Belum ada pasangan data presensi untuk grafik.</div>
    @endif

    <h2>Rekapitulasi Kehadiran</h2>
    @if ($attendanceRows->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Durasi</th>
                    <th>Jarak Masuk</th>
                    <th>Jarak Pulang</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendanceRows as $row)
                    @php
                        $durationHours = $row['duration_minutes'] !== null ? round($row['duration_minutes'] / 60, 2) : null;
                        $durationClass = $durationHours === null ? '' : ($durationHours > 6 ? 'bg-success' : ($durationHours > 4 ? 'bg-warning' : 'bg-danger'));
                        $checkInDistance = $row['check_in']?->distance_meters !== null ? round((float) $row['check_in']->distance_meters, 2) : null;
                        $checkOutDistance = $row['check_out']?->distance_meters !== null ? round((float) $row['check_out']->distance_meters, 2) : null;
                        $distanceClass = fn ($distance) => $distance === null ? '' : ($distance < 50 ? 'bg-success' : ($distance < 300 ? 'bg-warning' : 'bg-danger'));
                        $timeMinutes = fn ($checkIn) => $checkIn?->checked_at ? (((int) $checkIn->checked_at->format('H') * 60) + (int) $checkIn->checked_at->format('i')) : null;
                        $checkInMinutes = $timeMinutes($row['check_in']);
                        $checkOutMinutes = $timeMinutes($row['check_out']);
                        $checkInDevice = \App\Support\DeviceInfo::from($row['check_in']?->device_info);
                        $checkOutDevice = \App\Support\DeviceInfo::from($row['check_out']?->device_info);
                        $checkInClass = $checkInMinutes === null
                            ? 'bg-danger'
                            : ($checkInMinutes < $attendanceColorRules['check_in_success_before'] ? 'bg-success' : ($checkInMinutes < $attendanceColorRules['check_in_warning_before'] ? 'bg-warning' : 'bg-danger'));
                        $checkOutClass = $checkOutMinutes === null
                            ? 'bg-danger'
                            : ($checkOutMinutes >= $attendanceColorRules['check_out_success_from'] ? 'bg-success' : ($checkOutMinutes >= $attendanceColorRules['check_out_warning_from'] ? 'bg-warning' : 'bg-danger'));
                    @endphp
                    <tr>
                        <td>{{ $row['date']?->translatedFormat('l, d M Y') }}</td>
                        <td class="{{ $checkInClass }}">{{ $row['check_in']?->checked_at?->format('H:i:s') ?: '-' }}<br><small>{{ $checkInDevice['label'] ?? '-' }}</small></td>
                        <td class="{{ $checkOutClass }}">{{ $row['check_out']?->checked_at?->format('H:i:s') ?: '-' }}<br><small>{{ $checkOutDevice['label'] ?? '-' }}</small></td>
                        <td class="{{ $durationClass }}">{{ $durationHours !== null ? number_format($durationHours, 2, ',', '.').' jam' : '-' }}</td>
                        <td class="{{ $distanceClass($checkInDistance) }}">{{ $checkInDistance !== null ? number_format($checkInDistance, 2, ',', '.').' m' : '-' }}</td>
                        <td class="{{ $distanceClass($checkOutDistance) }}">{{ $checkOutDistance !== null ? number_format($checkOutDistance, 2, ',', '.').' m' : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">Belum ada pasangan data presensi untuk dicetak.</div>
    @endif

    <div class="signatures">
        <div>Mengetahui,<br>Dosen Pembimbing<br><br><br><strong>{{ $enrollment->lecturer?->name }}</strong></div>
        <div>Pembimbing Lapangan<br><br><br><br><strong>{{ $enrollment->field_supervisor }}</strong></div>
    </div>
    <script>
        const attendanceRows = @json($chartRows);

        if (attendanceRows.length > 0 && window.google) {
            google.charts.load('current', { packages: ['line'] });
            google.charts.setOnLoadCallback(drawCharts);
        }

        function drawCharts() {
            drawTimeChart();
            drawDistanceChart();
            drawDurationChart();
        }

        function drawTimeChart() {
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Tanggal');
            data.addColumn('number', 'Masuk');
            data.addColumn('number', 'Pulang');
            data.addRows(attendanceRows.map((row) => [row.label, row.checkInHour, row.checkOutHour]));
            drawLine('chart-time', data, 'Jam Masuk dan Jam Pulang', 'Dalam jam');
        }

        function drawDistanceChart() {
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Tanggal');
            data.addColumn('number', 'Masuk');
            data.addColumn('number', 'Pulang');
            data.addRows(attendanceRows.map((row) => [row.label, row.checkInDistance, row.checkOutDistance]));
            drawLine('chart-distance', data, 'Jarak dari Kantor', 'Dalam meter');
        }

        function drawDurationChart() {
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Tanggal');
            data.addColumn('number', 'Durasi');
            data.addColumn('number', 'Batas Minimal');
            data.addRows(attendanceRows.map((row) => [row.label, row.durationHours, 6]));
            drawLine('chart-duration', data, 'Durasi di Mitra', 'Dalam jam');
        }

        function drawLine(elementId, data, title, subtitle) {
            const chart = new google.charts.Line(document.getElementById(elementId));
            chart.draw(data, google.charts.Line.convertOptions({
                chart: { title, subtitle },
                height: 250,
                axes: { x: { 0: { side: 'bottom' } } },
            }));
        }
    </script>
</body>
</html>
