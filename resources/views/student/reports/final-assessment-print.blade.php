<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara Nilai - {{ $enrollment->student?->npm }}</title>
    <style>
        @page { size: A4; margin: 16mm; }
        body { font-family: "Times New Roman", serif; color: #111827; font-size: 12px; margin: 0; }
        button { font-family: Arial, sans-serif; margin: 0 0 12px; padding: 6px 12px; }
        .page { min-height: 260mm; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .doc-header { display: grid; grid-template-columns: 100px 1fr; gap: 16px; align-items: center; border-bottom: 3px solid #111; padding-bottom: 8px; margin-bottom: 10px; text-align: center; }
        .logo { width: 92px; height: 92px; object-fit: contain; }
        .logo-placeholder { width: 92px; height: 92px; border: 1px solid #111; display: flex; align-items: center; justify-content: center; font-size: 10px; }
        .header-title { font-size: 16px; font-weight: 700; line-height: 1.1; text-transform: uppercase; }
        .header-subtitle { font-size: 12px; line-height: 1.25; }
        h1 { border: 1px solid #111; font-size: 13px; line-height: 1.2; margin: 10px auto 12px; padding: 5px; text-align: center; text-transform: uppercase; width: 82%; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #111; padding: 5px 7px; vertical-align: top; }
        th { text-align: center; font-weight: 700; }
        .meta { margin: 12px 48px; line-height: 1.8; }
        .meta-row { display: grid; grid-template-columns: 140px 1fr; gap: 8px; }
        .dots { border-bottom: 1px dotted #111; min-height: 18px; }
        .score-table { margin: 12px auto; width: 86%; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .small { font-size: 11px; }
        .grade-table { width: 190px; font-size: 11px; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 72px; margin: 24px 48px 0; }
        .signature-space { height: 56px; }
        .digital-seal { margin: 8px 0 6px; text-align: left; }
        .digital-seal img { display: block; width: 92px; height: 92px; object-fit: contain; }
        .verification-note { font-size: 10px; line-height: 1.25; color: #374151; }
        .verification-url { max-width: 220px; overflow-wrap: anywhere; }
        .system-approval { color: #374151; font-size: 11px; margin: 12px 0 10px; min-height: 46px; }
        .inline-value { border-bottom: 1px dotted #111; display: inline-block; min-width: 96px; padding: 0 6px; text-align: center; }
        .mutu-row td { text-align: center; }
        .section-row td { font-weight: 700; background: #f3f4f6; }
        .print-grid { display: grid; grid-template-columns: 220px 1fr; gap: 48px; margin: 22px 48px 0; align-items: start; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
@php
    $printHeader = function () use ($header) {
        $contact = collect([$header['phone'] ?? null, $header['fax'] ?? null])->filter()->implode(' ');
        $web = collect([$header['website'] ?? null, $header['email'] ?? null])->filter()->implode(' - Email ');
@endphp
    <div class="doc-header">
        <div>
            @if (! empty($header['logo_url']))
                <img class="logo" src="{{ $header['logo_url'] }}" alt="Logo">
            @else
                <div class="logo-placeholder">Logo</div>
            @endif
        </div>
        <div>
            <div class="header-title">
                {{ $header['ministry'] ?? '' }}<br>
                {{ $header['university'] ?? '' }}<br>
                {{ $header['faculty'] ?? '' }}<br>
                {{ $header['department'] ?? '' }}
            </div>
            <div class="header-subtitle">
                {{ $header['address'] ?? '' }}<br>
                {{ $contact }}<br>
                @if ($web) Laman {{ $web }} @endif
            </div>
        </div>
    </div>
@php
    };
    $fieldScores = $enrollment->fieldSupervisorAssessment?->scores ?? [];
    $seminarScores = $seminarRequest?->assessment_scores ?? [];
    $weighted = fn ($score, $weight) => $score !== null ? round((float) $score * (float) $weight / 100, 2) : null;
@endphp

<button onclick="window.print()">Cetak</button>

<section class="page">
    @php($printHeader())
    <h1>Formulir Berita Acara<br>Penilaian {{ $programName }}</h1>

    <p class="center bold">Nomor: {{ $finalAssessment->document_number ?: '-' }}</p>
    <div class="meta">
        <p>
            Pada hari ini <span class="inline-value">{{ $seminarSchedule['day'] }}</span>
            Tanggal <span class="inline-value">{{ $seminarSchedule['date'] }}</span>
            Pukul <span class="inline-value">{{ $seminarSchedule['time'] }}</span>
            telah dilaksanakan kegiatan seminar KP/PKL sebagai evaluasi akhir pelaksanaan KP/PKL oleh mahasiswa:
        </p>
        <div class="meta-row"><span>Nama / NPM</span><span class="dots">{{ $enrollment->student?->full_name }} / {{ $enrollment->student?->npm }}</span></div>
        <div class="meta-row"><span>Judul Laporan</span><span class="dots">{{ $seminarRequest?->title ?: '-' }}</span></div>
        <div class="meta-row"><span>Mitra</span><span class="dots">{{ $enrollment->internshipPlace?->name ?: '-' }}</span></div>
        <div class="meta-row"><span>Dosen Pembimbing</span><span class="dots">{{ $enrollment->lecturer?->name ?: '-' }}</span></div>
        <div class="meta-row"><span>Pembimbing Lapangan</span><span class="dots">{{ $enrollment->field_supervisor ?: '-' }}</span></div>
    </div>

    <table class="score-table">
        <thead>
            <tr><th>Penilai</th><th>Nama / NIP</th><th>Nilai</th><th>%</th><th>NA</th></tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">Dosen<br>Pembimbing</td>
                <td>{{ $enrollment->lecturer?->name ?: '-' }}<br>NIP. {{ $enrollment->lecturer?->nip ?: '-' }}</td>
                <td class="center">{{ number_format((float) $finalAssessment->lecturer_score, 2, ',', '.') }}</td>
                <td class="center">{{ number_format((float) $finalAssessment->lecturer_weight, 0, ',', '.') }}%</td>
                <td class="center">{{ number_format((float) $finalAssessment->lecturer_score * (float) $finalAssessment->lecturer_weight / 100, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="center">Pembimbing<br>Lapangan</td>
                <td>{{ $enrollment->field_supervisor ?: '-' }}</td>
                <td class="center">{{ number_format((float) $finalAssessment->field_supervisor_score, 2, ',', '.') }}</td>
                <td class="center">{{ number_format((float) $finalAssessment->field_supervisor_weight, 0, ',', '.') }}%</td>
                <td class="center">{{ number_format((float) $finalAssessment->field_supervisor_score * (float) $finalAssessment->field_supervisor_weight / 100, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="center">Koordinator KP</td>
                <td>{{ $documentCoordinatorName ?: '-' }}<br>NIP. {{ $documentCoordinatorIdentifier ?: '-' }}</td>
                <td colspan="2" class="center">Pengurangan</td>
                <td class="center">{{ number_format((float) $finalAssessment->final_deduction, 2, ',', '.') }}</td>
            </tr>
            <tr><td colspan="4" class="right bold">Total Nilai</td><td class="center bold">{{ number_format((float) $finalAssessment->final_score, 2, ',', '.') }}</td></tr>
            <tr class="mutu-row"><td colspan="4" class="right bold">Huruf Mutu</td><td class="center bold">{{ $letterGrade }}</td></tr>
        </tbody>
    </table>

    <div class="print-grid">
        <table class="grade-table">
            <thead><tr><th>Huruf Mutu</th><th>Range Nilai</th></tr></thead>
            <tbody>
                @foreach ($gradeRanges as $range)
                    <tr><td class="center">{{ $range['letter'] }}</td><td>{{ $range['range'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <div class="signatures">
            <div>
                Mengetahui,<br>Ketua Jurusan
                <div class="system-approval">Dokumen ini disahkan secara digital melalui SiLAT.</div>
                <strong>{{ $documentChairName ?: '................................' }}</strong><br>
                NIP. {{ $documentChairIdentifier ?: '................................' }}
            </div>
            <div>
                {{ $finalAssessment->document_city ?: 'Bandar Lampung' }}, {{ $seminarSchedule['date'] }}<br>
                Menyetujui,<br>Koordinator KP/PKL
                <div class="digital-seal">
                    <img src="{{ $verificationQrUrl }}" alt="QR verifikasi dokumen">
                    <div class="verification-note">
                        Scan untuk verifikasi keabsahan dokumen.<br>
                        <span class="verification-url">{{ $verificationUrl }}</span>
                    </div>
                </div>
                <strong>{{ $documentCoordinatorName ?: '................................' }}</strong><br>
                NIP. {{ $documentCoordinatorIdentifier ?: '................................' }}
            </div>
        </div>
    </div>
</section>

<section class="page">
    @php($printHeader())
    <h1>Lembar Penilaian Seminar Kerja Praktik<br>Dosen Pembimbing</h1>

    <div class="meta">
        <div class="meta-row"><span>Nama / NPM</span><span class="dots">{{ $enrollment->student?->full_name }} / {{ $enrollment->student?->npm }}</span></div>
        <div class="meta-row"><span>Judul Laporan</span><span class="dots">{{ $seminarRequest?->title ?: '-' }}</span></div>
        <div class="meta-row"><span>Mitra</span><span class="dots">{{ $enrollment->internshipPlace?->name ?: '-' }}</span></div>
    </div>

    <table class="score-table">
        <thead><tr><th>Aspek yang dinilai</th><th>Nilai</th><th>Persentase</th><th>NA</th></tr></thead>
        <tbody>
            @php($currentGroup = null)
            @foreach ($seminarRubric as $key => $item)
                @if ($currentGroup !== $item['group'])
                    @php($currentGroup = $item['group'])
                    <tr class="section-row"><td>{{ $currentGroup }}</td><td></td><td></td><td></td></tr>
                @endif
                @php($score = data_get($seminarScores, $key.'.score'))
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td class="center">{{ $score !== null ? number_format((float) $score, 2, ',', '.') : '-' }}</td>
                    <td class="center">{{ $item['weight'] }}%</td>
                    <td class="center">{{ $score !== null ? number_format($weighted($score, $item['weight']), 2, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
            <tr><td colspan="3" class="center bold">Nilai Total</td><td class="center bold">{{ number_format((float) $finalAssessment->lecturer_score, 2, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="print-grid">
        <table class="grade-table">
            <thead><tr><th>Huruf Mutu</th><th>Range Nilai</th></tr></thead>
            <tbody>
                @foreach ($gradeRanges as $range)
                    <tr><td class="center">{{ $range['letter'] }}</td><td>{{ $range['range'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <div>
            {{ $finalAssessment->document_city ?: 'Bandar Lampung' }}, {{ $seminarSchedule['date'] }}<br>
            Dosen Pembimbing
            <div class="system-approval">Nilai dosen pembimbing sudah terkunci setelah nilai akhir difinalisasi melalui SiLAT.</div>
            <strong>{{ $enrollment->lecturer?->name ?: '................................' }}</strong><br>
            NIP. {{ $enrollment->lecturer?->nip ?: '................................' }}
        </div>
    </div>
</section>

<section class="page">
    @php($printHeader())
    <h1>Lembar Penilaian Program<br>Pembimbing Lapangan</h1>

    <div class="meta">
        <div class="meta-row"><span>Nama Mahasiswa</span><span class="dots">{{ $enrollment->student?->full_name }}</span></div>
        <div class="meta-row"><span>NPM</span><span class="dots">{{ $enrollment->student?->npm }}</span></div>
        <div class="meta-row"><span>Judul Laporan</span><span class="dots">{{ $seminarRequest?->title ?: '-' }}</span></div>
        <div class="meta-row"><span>Mitra</span><span class="dots">{{ $enrollment->internshipPlace?->name ?: '-' }}</span></div>
    </div>

    <table class="score-table">
        <thead><tr><th>Komponen Penilaian</th><th>Nilai</th></tr></thead>
        <tbody>
            @php($currentGroup = null)
            @foreach ($fieldSupervisorRubric as $key => $item)
                @if ($currentGroup !== $item['group'])
                    @php($currentGroup = $item['group'])
                    <tr class="section-row"><td colspan="2">{{ $currentGroup }}</td></tr>
                @endif
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td class="center">{{ data_get($fieldScores, $key.'.score') !== null ? number_format((float) data_get($fieldScores, $key.'.score'), 2, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
            <tr><td class="center bold">Rata-rata Nilai</td><td class="center bold">{{ number_format((float) $finalAssessment->field_supervisor_score, 2, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="signatures">
        <div></div>
        <div>
            {{ $finalAssessment->document_city ?: 'Bandar Lampung' }}, {{ $seminarSchedule['date'] }}<br>
            Pembimbing Lapangan<br>Kerja Praktik,
            <div class="system-approval">Nilai pembimbing lapangan sudah terkunci setelah nilai akhir difinalisasi melalui SiLAT.</div>
            <strong>{{ $enrollment->field_supervisor ?: '................................' }}</strong>
        </div>
    </div>
</section>
</body>
</html>
