@php
    $items = [
        ['patterns' => ['management.dashboard'], 'group' => 'Manajemen', 'label' => 'Dashboard Manajemen'],
        ['patterns' => ['management.enrollment-validations.*'], 'group' => 'Workflow Akademik', 'label' => 'Validasi Pendaftaran'],
        ['patterns' => ['management.place-proposals.*'], 'group' => 'Workflow Akademik', 'label' => 'Usulan Mitra'],
        ['patterns' => ['management.relocations.*'], 'group' => 'Workflow Akademik', 'label' => 'Pindah Mitra'],
        ['patterns' => ['management.supervisor-requests.*'], 'group' => 'Workflow Akademik', 'label' => 'Perubahan Pembimbing'],
        ['patterns' => ['management.field-supervisors.*'], 'group' => 'Master Data', 'label' => 'Pembimbing Lapangan'],
        ['patterns' => ['management.orientation-events.*'], 'group' => 'Workflow Akademik', 'label' => 'Pembekalan'],
        ['patterns' => ['management.seminar-requests.*'], 'group' => 'Workflow Akademik', 'label' => 'Review Seminar'],
        ['patterns' => ['management.forgotten-attendance-requests.*'], 'group' => 'Workflow Akademik', 'label' => 'Lupa Presensi'],
        ['patterns' => ['management.wfa-requests.*'], 'group' => 'Workflow Akademik', 'label' => 'Pengajuan WFA'],
        ['patterns' => ['management.final-assessments.*'], 'group' => 'Workflow Akademik', 'label' => 'Finalisasi Nilai'],
        ['patterns' => ['management.enrollments.*'], 'group' => 'Workflow Akademik', 'label' => 'Peserta Periode'],
        ['patterns' => ['management.users.*'], 'group' => 'Master Data', 'label' => 'User'],
        ['patterns' => ['management.students.*'], 'group' => 'Master Data', 'label' => 'Mahasiswa'],
        ['patterns' => ['management.lecturers.*'], 'group' => 'Master Data', 'label' => 'Dosen'],
        ['patterns' => ['management.coordinators.*'], 'group' => 'Master Data', 'label' => 'Koordinator Program'],
        ['patterns' => ['management.report-viewers.*'], 'group' => 'Master Data', 'label' => 'Viewer Laporan'],
        ['patterns' => ['management.organizations.*'], 'group' => 'Master Data', 'label' => 'Organisasi'],
        ['patterns' => ['management.study-programs.*'], 'group' => 'Master Data', 'label' => 'Prodi'],
        ['patterns' => ['management.programs.*'], 'group' => 'Master Data', 'label' => 'Program Kegiatan'],
        ['patterns' => ['management.periods.*'], 'group' => 'Master Data', 'label' => 'Periode Program'],
        ['patterns' => ['management.places.*'], 'group' => 'Master Data', 'label' => 'Mitra'],
        ['patterns' => ['reports.progress-funnel'], 'group' => 'Analisis & Laporan', 'label' => 'Progress Funnel'],
        ['patterns' => ['reports.risk-scoring'], 'group' => 'Analisis & Laporan', 'label' => 'Risk Scoring'],
        ['patterns' => ['reports.attendance-heatmap'], 'group' => 'Analisis & Laporan', 'label' => 'Heatmap Kehadiran'],
        ['patterns' => ['reports.operational-charts'], 'group' => 'Analisis & Laporan', 'label' => 'Grafik Operasional'],
        ['patterns' => ['reports.sanctions'], 'group' => 'Analisis & Laporan', 'label' => 'Rekap Sanksi'],
        ['patterns' => ['reports.final-scores'], 'group' => 'Analisis & Laporan', 'label' => 'Rekap Nilai Akhir'],
        ['patterns' => ['reports.monitoring'], 'group' => 'Analisis & Laporan', 'label' => 'Rekap Monitoring'],
        ['patterns' => ['system-configurations.*'], 'group' => 'Konfigurasi', 'label' => 'Konfigurasi Program'],
        ['patterns' => ['email-notifications.*'], 'group' => 'Konfigurasi', 'label' => 'Email & Notifikasi'],
        ['patterns' => ['management.audit-logs.*'], 'group' => 'Konfigurasi', 'label' => 'Audit Log'],
    ];

    $current = collect($items)->first(
        fn (array $item) => collect($item['patterns'])->contains(fn (string $pattern) => request()->routeIs($pattern))
    );
@endphp

@if ($current)
    <nav class="mb-5 flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
        <span class="font-medium text-gray-600">{{ $current['group'] }}</span>
        <x-icon name="fa-chevron-right" class="text-[10px] text-gray-400" />
        <span class="font-semibold text-gray-900">{{ $current['label'] }}</span>
    </nav>
@endif
