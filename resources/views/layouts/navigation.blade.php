@php
    $user = Auth::user();
    $userAvatarUrl = \App\Support\PublicStorage::url($user->avatar_url);
    $actionRequiredSummary ??= [];
    $groups = [];
    $roleOptions = collect([
        ['key' => 'admin', 'label' => 'Admin', 'home' => 'management.dashboard', 'available' => $user->hasRole('admin')],
        ['key' => 'dosen', 'label' => 'Dosen Pembimbing', 'home' => 'dashboard', 'available' => $user->hasRole('dosen')],
        ['key' => 'koordinator', 'label' => 'Koordinator', 'home' => 'coordinator.dashboard', 'available' => $user->hasRole('koordinator')],
        ['key' => 'report_viewer', 'label' => 'Viewer Laporan', 'home' => 'reports.progress-funnel', 'available' => $user->hasRole('report_viewer')],
        ['key' => 'mahasiswa', 'label' => 'Mahasiswa', 'home' => 'student.dashboard', 'available' => $user->hasRole('mahasiswa')],
        ['key' => 'pembimbing_lapangan', 'label' => 'Pembimbing Lapangan', 'home' => 'field-supervisor.index', 'available' => $user->hasRole('pembimbing_lapangan')],
    ])->filter(fn (array $role): bool => $role['available'])->values();
    $activeRole = session('active_role');

    if (! $roleOptions->contains('key', $activeRole)) {
        $activeRole = $roleOptions->firstWhere('key', $user->role)['key'] ?? $roleOptions->first()['key'] ?? $user->role;
    }

    $activeRoleOption = $roleOptions->firstWhere('key', $activeRole);
    $activeRoleLabel = $activeRoleOption['label'] ?? ucfirst((string) $activeRole);
    $activeHomeRoute = $activeRoleOption['home'] ?? 'dashboard';
    $documentationRoleRoutes = [
        'admin' => 'admin',
        'dosen' => 'dosen-pembimbing',
        'koordinator' => 'koordinator',
        'report_viewer' => 'viewer-laporan',
        'mahasiswa' => 'mahasiswa',
        'pembimbing_lapangan' => 'pembimbing-lapangan',
    ];
    $documentationRole = $documentationRoleRoutes[$activeRole] ?? null;
    $documentationUrl = $documentationRole ? route('docs.show', $documentationRole) : route('docs.index');

    $groups[] = [
        'label' => 'Utama',
        'items' => [
            [
                'label' => 'Dashboard',
                'route' => $activeHomeRoute,
                'icon' => 'fa-gauge-high',
                'active' => [$activeHomeRoute],
            ],
        ],
    ];

    if ($activeRole === 'mahasiswa') {
        $groups[] = [
            'label' => 'Program Saya',
            'items' => [
                ['label' => 'Ringkasan Program', 'route' => 'student.dashboard', 'icon' => 'fa-house-user', 'active' => ['student.dashboard', 'student.reports.*']],
                ['label' => 'Profil Saya', 'route' => 'student.profile.edit', 'icon' => 'fa-id-card', 'active' => ['student.profile.*']],
                ['label' => 'Pendaftaran Program', 'route' => 'student.enrollments.create', 'icon' => 'fa-clipboard-list', 'active' => ['student.enrollments.*']],
                ['label' => 'Presensi', 'route' => 'check-ins.create', 'icon' => 'fa-fingerprint', 'active' => ['check-ins.*']],
            ],
        ];

        $groups[] = [
            'label' => 'Layanan Program',
            'items' => [
                ['label' => 'Usulan Mitra', 'route' => 'student.proposals.index', 'icon' => 'fa-building-circle-arrow-right', 'active' => ['student.proposals.*']],
                ['label' => 'Pindah Mitra', 'route' => 'student.relocations.index', 'icon' => 'fa-route', 'active' => ['student.relocations.*']],
                ['label' => 'Perubahan Pembimbing', 'route' => 'student.supervisor-requests.index', 'icon' => 'fa-user-pen', 'active' => ['student.supervisor-requests.*']],
                ['label' => 'Pengajuan WFA', 'route' => 'student.wfa-requests.index', 'icon' => 'fa-laptop-house', 'active' => ['student.wfa-requests.*']],
                ['label' => 'Data Mitra', 'route' => 'student.places.index', 'icon' => 'fa-building', 'active' => ['student.places.*']],
            ],
        ];
    }

    if ($activeRole === 'koordinator') {
        $groups[] = [
            'label' => 'Koordinator',
            'items' => [
                ['label' => 'Dashboard Koordinator', 'route' => 'coordinator.dashboard', 'icon' => 'fa-user-tie', 'active' => ['coordinator.*']],
                ['label' => 'Validasi Pendaftaran', 'route' => 'management.enrollment-validations.index', 'icon' => 'fa-user-check', 'active' => ['management.enrollment-validations.*'], 'badge' => 'enrollment_validations'],
                ['label' => 'Usulan Mitra', 'route' => 'management.place-proposals.index', 'icon' => 'fa-building-circle-check', 'active' => ['management.place-proposals.*'], 'badge' => 'place_proposals'],
                ['label' => 'Pindah Mitra', 'route' => 'management.relocations.index', 'icon' => 'fa-route', 'active' => ['management.relocations.*'], 'badge' => 'relocations'],
                ['label' => 'Perubahan Pembimbing', 'route' => 'management.supervisor-requests.index', 'icon' => 'fa-user-pen', 'active' => ['management.supervisor-requests.*'], 'badge' => 'supervisor_changes'],
                ['label' => 'Pembimbing Lapangan', 'route' => 'management.field-supervisors.index', 'icon' => 'fa-user-check', 'active' => ['management.field-supervisors.*']],
                ['label' => 'Review Laporan', 'route' => 'management.submission-progress.index', 'icon' => 'fa-file-circle-check', 'active' => ['management.submission-progress.*']],
                ['label' => 'Review Seminar', 'route' => 'management.seminar-requests.index', 'icon' => 'fa-person-chalkboard', 'active' => ['management.seminar-requests.*']],
                ['label' => 'Lupa Presensi', 'route' => 'management.forgotten-attendance-requests.index', 'icon' => 'fa-calendar-xmark', 'active' => ['management.forgotten-attendance-requests.*']],
                ['label' => 'Pengajuan WFA', 'route' => 'management.wfa-requests.index', 'icon' => 'fa-laptop-house', 'active' => ['management.wfa-requests.*'], 'badge' => 'wfa_requests'],
                ['label' => 'Finalisasi Nilai', 'route' => 'management.final-assessments.index', 'icon' => 'fa-calculator', 'active' => ['management.final-assessments.*']],
                ['label' => 'Monitoring Prodi', 'route' => 'maps.monitoring', 'icon' => 'fa-map-location-dot', 'active' => ['maps.monitoring']],
            ],
        ];
    }

    if ($activeRole === 'dosen') {
        $groups[] = [
            'label' => 'Dosen Pembimbing',
            'items' => [
                ['label' => 'Review Laporan', 'route' => 'management.submission-progress.index', 'icon' => 'fa-file-circle-check', 'active' => ['management.submission-progress.*'], 'badge' => 'lecturer_report_reviews'],
                ['label' => 'Seminar & Penilaian', 'route' => 'management.seminar-requests.index', 'icon' => 'fa-person-chalkboard', 'active' => ['management.seminar-requests.*'], 'badge' => 'lecturer_seminar_reviews'],
                ['label' => 'Peta Monitoring Bimbingan', 'route' => 'maps.monitoring', 'params' => ['scope' => 'bimbingan'], 'icon' => 'fa-map-location-dot', 'active' => ['maps.monitoring']],
                ['label' => 'Rekap Monitoring Bimbingan', 'route' => 'reports.monitoring', 'params' => ['scope' => 'bimbingan'], 'icon' => 'fa-chart-column', 'active' => ['reports.*']],
            ],
        ];
    }

    if ($activeRole === 'pembimbing_lapangan') {
        $groups[] = [
            'label' => 'Pembimbing Lapangan',
            'items' => [
                ['label' => 'Mahasiswa Bimbingan', 'route' => 'field-supervisor.enrollments.index', 'icon' => 'fa-user-check', 'active' => ['field-supervisor.enrollments.*']],
            ],
        ];
    }

    if ($activeRole === 'admin') {
        $groups[] = [
            'label' => 'Manajemen',
            'items' => [
                ['label' => 'Dashboard Manajemen', 'route' => 'management.dashboard', 'icon' => 'fa-chart-line', 'active' => ['management.dashboard']],
            ],
        ];

        $groups[] = [
            'label' => 'Workflow Akademik',
            'items' => [
                ['label' => 'Program Kegiatan', 'route' => 'management.programs.index', 'icon' => 'fa-layer-group', 'active' => ['management.programs.*']],
                ['label' => 'Periode Program', 'route' => 'management.periods.index', 'icon' => 'fa-calendar-days', 'active' => ['management.periods.*']],
                ['label' => 'Koordinator Program', 'route' => 'management.coordinators.index', 'icon' => 'fa-user-gear', 'active' => ['management.coordinators.*']],
                ['label' => 'Validasi Pendaftaran', 'route' => 'management.enrollment-validations.index', 'icon' => 'fa-user-check', 'active' => ['management.enrollment-validations.*'], 'badge' => 'enrollment_validations'],
                ['label' => 'Peserta Periode', 'route' => 'management.enrollments.index', 'icon' => 'fa-users-viewfinder', 'active' => ['management.enrollments.*']],
                ['label' => 'Usulan Mitra', 'route' => 'management.place-proposals.index', 'icon' => 'fa-building-circle-check', 'active' => ['management.place-proposals.*'], 'badge' => 'place_proposals'],
                ['label' => 'Pindah Mitra', 'route' => 'management.relocations.index', 'icon' => 'fa-route', 'active' => ['management.relocations.*'], 'badge' => 'relocations'],
                ['label' => 'Perubahan Pembimbing', 'route' => 'management.supervisor-requests.index', 'icon' => 'fa-user-pen', 'active' => ['management.supervisor-requests.*'], 'badge' => 'supervisor_changes'],
                ['label' => 'Review Laporan', 'route' => 'management.submission-progress.index', 'icon' => 'fa-file-circle-check', 'active' => ['management.submission-progress.*']],
                ['label' => 'Review Seminar', 'route' => 'management.seminar-requests.index', 'icon' => 'fa-person-chalkboard', 'active' => ['management.seminar-requests.*']],
                ['label' => 'Lupa Presensi', 'route' => 'management.forgotten-attendance-requests.index', 'icon' => 'fa-calendar-xmark', 'active' => ['management.forgotten-attendance-requests.*']],
                ['label' => 'Pengajuan WFA', 'route' => 'management.wfa-requests.index', 'icon' => 'fa-laptop-house', 'active' => ['management.wfa-requests.*'], 'badge' => 'wfa_requests'],
                ['label' => 'Finalisasi Nilai', 'route' => 'management.final-assessments.index', 'icon' => 'fa-calculator', 'active' => ['management.final-assessments.*']],
            ],
        ];

        $groups[] = [
            'label' => 'Master Data',
            'items' => [
                ['label' => 'User', 'route' => 'management.users.index', 'icon' => 'fa-users-gear', 'active' => ['management.users.*']],
                ['label' => 'Mahasiswa', 'route' => 'management.students.index', 'icon' => 'fa-user-graduate', 'active' => ['management.students.*']],
                ['label' => 'Dosen', 'route' => 'management.lecturers.index', 'icon' => 'fa-chalkboard-user', 'active' => ['management.lecturers.*']],
                ['label' => 'Pembimbing Lapangan', 'route' => 'management.field-supervisors.index', 'icon' => 'fa-user-check', 'active' => ['management.field-supervisors.*']],
                ['label' => 'Organisasi', 'route' => 'management.organizations.index', 'icon' => 'fa-sitemap', 'active' => ['management.organizations.*']],
                ['label' => 'Prodi', 'route' => 'management.study-programs.index', 'icon' => 'fa-school', 'active' => ['management.study-programs.*']],
                ['label' => 'Viewer Laporan', 'route' => 'management.report-viewers.index', 'icon' => 'fa-chart-simple', 'active' => ['management.report-viewers.*']],
                ['label' => 'Mitra', 'route' => 'management.places.index', 'icon' => 'fa-building', 'active' => ['management.places.*']],
            ],
        ];
    }

    if (in_array($activeRole, ['admin', 'koordinator', 'report_viewer'], true)) {
        $groups[] = [
            'label' => 'Analisis & Laporan',
            'items' => [
                ['label' => 'Progress Funnel', 'route' => 'reports.progress-funnel', 'icon' => 'fa-chart-simple', 'active' => ['reports.progress-funnel']],
                ['label' => 'Risk Scoring', 'route' => 'reports.risk-scoring', 'icon' => 'fa-triangle-exclamation', 'active' => ['reports.risk-scoring']],
                ['label' => 'Heatmap Kehadiran', 'route' => 'reports.attendance-heatmap', 'icon' => 'fa-table-cells', 'active' => ['reports.attendance-heatmap']],
                ['label' => 'Grafik Operasional', 'route' => 'reports.operational-charts', 'icon' => 'fa-chart-pie', 'active' => ['reports.operational-charts']],
                ['label' => 'Rekap Sanksi', 'route' => 'reports.sanctions', 'icon' => 'fa-scale-balanced', 'active' => ['reports.sanctions']],
                ['label' => 'Rekap Nilai Akhir', 'route' => 'reports.final-scores', 'icon' => 'fa-calculator', 'active' => ['reports.final-scores']],
                ['label' => 'Rekap Monitoring', 'route' => 'reports.monitoring', 'icon' => 'fa-chart-column', 'active' => ['reports.monitoring']],
            ],
        ];
    }

    $monitoringItems = [
        ['label' => 'Peta & Rute Mitra', 'route' => 'maps.places', 'icon' => 'fa-map', 'active' => ['maps.places']],
    ];

    if ($activeRole !== 'dosen') {
        $monitoringItems[] = ['label' => 'Peta Monitoring', 'route' => 'maps.monitoring', 'icon' => 'fa-map-location-dot', 'active' => ['maps.monitoring']];
    }

    if (! in_array($activeRole, ['admin', 'dosen', 'koordinator'], true)) {
        $monitoringItems[] = ['label' => 'Rekap Monitoring', 'route' => 'reports.monitoring', 'icon' => 'fa-chart-column', 'active' => ['reports.monitoring']];
    }

    if ($activeRole === 'admin') {
        $monitoringItems[] = ['label' => 'Input Lokasi Mitra', 'route' => 'internship-places.create', 'icon' => 'fa-location-crosshairs', 'active' => ['internship-places.*']];
    }

    $groups[] = [
        'label' => 'Monitoring',
        'items' => $monitoringItems,
    ];

    if ($activeRole === 'admin') {
        $groups[] = [
            'label' => 'Konfigurasi',
            'items' => [
                ['label' => 'Konfigurasi Program', 'route' => 'system-configurations.index', 'icon' => 'fa-sliders', 'active' => ['system-configurations.*']],
                ['label' => 'Email & Notifikasi', 'route' => 'email-notifications.index', 'icon' => 'fa-envelope-circle-check', 'active' => ['email-notifications.*']],
                ['label' => 'Audit Log', 'route' => 'management.audit-logs.index', 'icon' => 'fa-clock-rotate-left', 'active' => ['management.audit-logs.*']],
            ],
        ];
    }

    $itemClass = fn (bool $active): string => $active
        ? 'flex items-center gap-3 rounded-lg bg-white/15 px-3 py-2.5 text-sm font-semibold text-white ring-1 ring-white/10'
        : 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-blue-100 transition hover:bg-white/10 hover:text-white focus:bg-white/10 focus:text-white focus:outline-none';

    $mobileItemClass = fn (bool $active): string => $active
        ? 'flex items-center gap-3 rounded-lg bg-blue-50 px-3 py-2.5 text-sm font-semibold text-blue-800'
        : 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-950 focus:bg-gray-50 focus:outline-none';
@endphp

<div x-data="{ open: false }">
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-blue-900 text-white shadow-xl lg:flex">
        <div class="flex h-16 items-center gap-3 border-b border-blue-800 px-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-sm font-bold text-blue-900">SL</span>
                <span class="leading-none">
                    <span class="block text-base font-bold">SiLAT</span>
                    <span class="mt-1 block text-xs text-blue-200">MBKM & Kerja Praktik</span>
                </span>
            </a>
        </div>

        <nav class="min-h-0 flex-1 space-y-6 overflow-y-auto px-3 py-5" data-sidebar-scroll>
            @foreach ($groups as $group)
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-blue-300">{{ $group['label'] }}</p>
                    <div class="mt-2 space-y-1">
                        @foreach ($group['items'] as $item)
                            @php
                                $active = request()->routeIs($item['active']);
                                $badgeCount = isset($item['badge']) ? (int) data_get($actionRequiredSummary, $item['badge'].'.count', 0) : 0;
                            @endphp
                            <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="{{ $itemClass($active) }}">
                                <x-icon :name="$item['icon']" class="w-5 text-center" />
                                <span class="min-w-0 flex-1">{{ $item['label'] }}</span>
                                @if ($badgeCount > 0)
                                    <span class="ml-auto inline-flex min-w-6 items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white">{{ number_format($badgeCount, 0, ',', '.') }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </aside>

    <header class="fixed inset-x-0 top-0 z-30 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur lg:left-64">
        <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button type="button" @click="open = true" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 lg:hidden">
                    <span class="sr-only">Buka menu</span>
                    <x-icon name="fa-bars" />
                </button>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">SiLAT</p>
                    <p class="text-sm text-gray-500">Sistem Laporan Aktivitas Terpadu</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ $documentationUrl }}" class="hidden items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:inline-flex">
                    <x-icon name="fa-book-open" class="text-blue-700" />
                    <span>Dokumentasi</span>
                </a>
                <span class="hidden text-right text-sm sm:block">
                    <span class="block font-semibold text-gray-900">{{ $user->name }}</span>
                    <span class="block text-xs text-gray-500">{{ $activeRoleLabel }}</span>
                </span>
                @if ($roleOptions->count() > 1)
                    <x-dropdown align="right" width="w-56">
                        <x-slot name="trigger">
                            <button type="button" class="hidden items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:inline-flex">
                                <x-icon name="fa-user-gear" class="text-blue-700" />
                                <span>{{ $activeRoleLabel }}</span>
                                <x-icon name="fa-chevron-down" class="text-xs text-gray-400" />
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Mode akses</div>
                            @foreach ($roleOptions as $roleOption)
                                <form method="POST" action="{{ route('active-role.update') }}">
                                    @csrf
                                    <input type="hidden" name="role" value="{{ $roleOption['key'] }}">
                                    <button type="submit" class="flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm transition hover:bg-gray-100 focus:bg-gray-100 focus:outline-none {{ $activeRole === $roleOption['key'] ? 'font-semibold text-blue-700' : 'text-gray-700' }}">
                                        <span>{{ $roleOption['label'] }}</span>
                                        @if ($activeRole === $roleOption['key'])
                                            <x-icon name="fa-check" class="text-xs" />
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </x-slot>
                    </x-dropdown>
                @endif
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-white text-sm font-semibold text-blue-800 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            @if ($userAvatarUrl)
                                <img src="{{ $userAvatarUrl }}" alt="Foto profil {{ $user->name }}" class="h-full w-full object-cover">
                            @else
                                {{ strtoupper(Str::substr($user->name, 0, 1)) }}
                            @endif
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profil') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('logout')">{{ __('Keluar') }}</x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </header>

    <div x-show="open" class="fixed inset-0 z-50 lg:hidden" style="display: none;">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/50" @click="open = false"></div>
        <aside
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="relative flex h-full w-80 max-w-[85vw] flex-col bg-white shadow-xl"
        >
            <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3" @click="open = false">
                    <span class="silat-brand-mark">SL</span>
                    <span class="leading-none">
                        <span class="silat-brand-title block">SiLAT</span>
                        <span class="silat-brand-subtitle block">MBKM & Kerja Praktik</span>
                    </span>
                </a>
                <button type="button" @click="open = false" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100">
                    <span class="sr-only">Tutup menu</span>
                    <x-icon name="fa-xmark" />
                </button>
            </div>

            <nav class="min-h-0 flex-1 space-y-6 overflow-y-auto px-3 py-5">
                @foreach ($groups as $group)
                    <div>
                        <p class="px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $group['label'] }}</p>
                        <div class="mt-2 space-y-1">
                            @foreach ($group['items'] as $item)
                                @php
                                    $active = request()->routeIs($item['active']);
                                    $badgeCount = isset($item['badge']) ? (int) data_get($actionRequiredSummary, $item['badge'].'.count', 0) : 0;
                                @endphp
                                <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="{{ $mobileItemClass($active) }}" @click="open = false">
                                    <x-icon :name="$item['icon']" class="w-5 text-center" />
                                    <span class="min-w-0 flex-1">{{ $item['label'] }}</span>
                                    @if ($badgeCount > 0)
                                        <span class="ml-auto inline-flex min-w-6 items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white">{{ number_format($badgeCount, 0, ',', '.') }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="border-t border-gray-200 p-4">
                <a href="{{ $documentationUrl }}" class="mb-4 flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="open = false">
                    <x-icon name="fa-book-open" class="w-5 text-center text-blue-700" />
                    <span>Dokumentasi</span>
                </a>
                @if ($roleOptions->count() > 1)
                    <div class="mb-4">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Mode akses</p>
                        <div class="grid gap-1">
                            @foreach ($roleOptions as $roleOption)
                                <form method="POST" action="{{ route('active-role.update') }}">
                                    @csrf
                                    <input type="hidden" name="role" value="{{ $roleOption['key'] }}">
                                    <button type="submit" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm transition {{ $activeRole === $roleOption['key'] ? 'bg-blue-50 font-semibold text-blue-800' : 'text-gray-700 hover:bg-gray-50' }}">
                                        <span>{{ $roleOption['label'] }}</span>
                                        @if ($activeRole === $roleOption['key'])
                                            <x-icon name="fa-check" class="text-xs" />
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="flex items-center gap-3">
                    @if ($userAvatarUrl)
                        <img src="{{ $userAvatarUrl }}" alt="Foto profil {{ $user->name }}" class="h-10 w-10 rounded-full object-cover">
                    @else
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-sm font-semibold text-blue-700">{{ strtoupper(Str::substr($user->name, 0, 1)) }}</div>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-900">{{ $user->name }}</p>
                        <p class="truncate text-sm text-gray-500">{{ $user->email }}</p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('[data-sidebar-scroll]');

        if (! sidebar) {
            return;
        }

        const storageKey = 'silat:sidebar-scroll-top';
        const storage = {
            get() {
                try {
                    return sessionStorage.getItem(storageKey) || '0';
                } catch (error) {
                    return '0';
                }
            },
            set(value) {
                try {
                    sessionStorage.setItem(storageKey, value);
                } catch (error) {
                    // Abaikan jika browser memblokir sessionStorage.
                }
            },
        };
        const storedPosition = Number.parseInt(storage.get(), 10);

        if (! Number.isNaN(storedPosition) && storedPosition > 0) {
            sidebar.scrollTop = storedPosition;
        }

        sidebar.addEventListener('scroll', () => {
            storage.set(String(sidebar.scrollTop));
        }, { passive: true });

        sidebar.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', () => {
                storage.set(String(sidebar.scrollTop));
            });
        });
    });
</script>
