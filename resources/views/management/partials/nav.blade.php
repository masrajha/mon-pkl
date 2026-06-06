@php
    $items = [
        ['patterns' => ['management.dashboard'], 'group' => 'Manajemen', 'label' => 'Ringkasan'],
        ['patterns' => ['management.enrollment-validations.*'], 'group' => 'Workflow Akademik', 'label' => 'Validasi Pendaftaran'],
        ['patterns' => ['management.place-proposals.*'], 'group' => 'Workflow Akademik', 'label' => 'Usulan Mitra'],
        ['patterns' => ['management.relocations.*'], 'group' => 'Workflow Akademik', 'label' => 'Pindah Mitra'],
        ['patterns' => ['management.supervisor-requests.*'], 'group' => 'Workflow Akademik', 'label' => 'Perubahan Pembimbing'],
        ['patterns' => ['management.field-supervisors.*'], 'group' => 'Master Data', 'label' => 'Pembimbing Lapangan'],
        ['patterns' => ['management.orientation-events.*'], 'group' => 'Workflow Akademik', 'label' => 'Pembekalan'],
        ['patterns' => ['management.seminar-requests.*'], 'group' => 'Workflow Akademik', 'label' => 'Review Seminar'],
        ['patterns' => ['management.final-assessments.*'], 'group' => 'Workflow Akademik', 'label' => 'Finalisasi Nilai'],
        ['patterns' => ['management.enrollments.*'], 'group' => 'Workflow Akademik', 'label' => 'Peserta Periode'],
        ['patterns' => ['management.users.*'], 'group' => 'Master Data', 'label' => 'User'],
        ['patterns' => ['management.students.*'], 'group' => 'Master Data', 'label' => 'Mahasiswa'],
        ['patterns' => ['management.lecturers.*'], 'group' => 'Master Data', 'label' => 'Dosen'],
        ['patterns' => ['management.coordinators.*'], 'group' => 'Master Data', 'label' => 'Koordinator Program'],
        ['patterns' => ['management.study-programs.*'], 'group' => 'Master Data', 'label' => 'Prodi'],
        ['patterns' => ['management.programs.*'], 'group' => 'Master Data', 'label' => 'Program Kegiatan'],
        ['patterns' => ['management.periods.*'], 'group' => 'Master Data', 'label' => 'Periode Program'],
        ['patterns' => ['management.places.*'], 'group' => 'Master Data', 'label' => 'Mitra'],
        ['patterns' => ['system-configurations.*'], 'group' => 'Konfigurasi', 'label' => 'Konfigurasi Program'],
        ['patterns' => ['email-notifications.*'], 'group' => 'Konfigurasi', 'label' => 'Email & Notifikasi'],
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
