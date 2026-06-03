@php
    $user = Auth::user();
    $groups = [];

    $groups[] = [
        'label' => 'Utama',
        'items' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'active' => ['dashboard']],
        ],
    ];

    if ($user->hasRole('mahasiswa')) {
        $groups[] = [
            'label' => 'Program Saya',
            'items' => [
                ['label' => 'Ringkasan Program', 'route' => 'student.dashboard', 'icon' => 'fa-house-user', 'active' => ['student.dashboard']],
                ['label' => 'Profil Saya', 'route' => 'student.profile.edit', 'icon' => 'fa-id-card', 'active' => ['student.profile.*']],
                ['label' => 'Pendaftaran Program', 'route' => 'student.enrollments.create', 'icon' => 'fa-clipboard-list', 'active' => ['student.enrollments.*']],
                ['label' => 'Usulan Tempat', 'route' => 'student.proposals.create', 'icon' => 'fa-building-circle-arrow-right', 'active' => ['student.proposals.*']],
                ['label' => 'Pindah Tempat', 'route' => 'student.relocations.create', 'icon' => 'fa-route', 'active' => ['student.relocations.*']],
                ['label' => 'Perubahan Pembimbing', 'route' => 'student.supervisor-requests.create', 'icon' => 'fa-user-pen', 'active' => ['student.supervisor-requests.*']],
                ['label' => 'Presensi', 'route' => 'check-ins.create', 'icon' => 'fa-fingerprint', 'active' => ['check-ins.*']],
                ['label' => 'Laporan & Log', 'route' => 'student.dashboard', 'icon' => 'fa-book-open', 'active' => ['student.reports.*']],
            ],
        ];

        $groups[] = [
            'label' => 'Mitra',
            'items' => [
                ['label' => 'Data Mitra', 'route' => 'student.places.index', 'icon' => 'fa-building', 'active' => ['student.places.*']],
            ],
        ];
    }

    if ($user->hasRole('koordinator')) {
        $groups[] = [
            'label' => 'Koordinator',
            'items' => [
                ['label' => 'Dashboard Koordinator', 'route' => 'coordinator.dashboard', 'icon' => 'fa-user-tie', 'active' => ['coordinator.*']],
                ['label' => 'Validasi Pendaftaran', 'route' => 'management.enrollment-validations.index', 'icon' => 'fa-user-check', 'active' => ['management.enrollment-validations.*']],
                ['label' => 'Pindah Tempat', 'route' => 'management.relocations.index', 'icon' => 'fa-route', 'active' => ['management.relocations.*']],
                ['label' => 'Perubahan Pembimbing', 'route' => 'management.supervisor-requests.index', 'icon' => 'fa-user-pen', 'active' => ['management.supervisor-requests.*']],
                ['label' => 'Review Laporan', 'route' => 'management.submission-progress.index', 'icon' => 'fa-file-circle-check', 'active' => ['management.submission-progress.*']],
                ['label' => 'Monitoring Prodi', 'route' => 'maps.monitoring', 'icon' => 'fa-map-location-dot', 'active' => ['maps.monitoring']],
                ['label' => 'Rekap Prodi', 'route' => 'reports.monitoring', 'icon' => 'fa-chart-column', 'active' => ['reports.monitoring']],
            ],
        ];
    }

    if ($user->hasRole('dosen')) {
        $groups[] = [
            'label' => 'Dosen Pembimbing',
            'items' => [
                ['label' => 'Review Laporan', 'route' => 'management.submission-progress.index', 'icon' => 'fa-file-circle-check', 'active' => ['management.submission-progress.*']],
                ['label' => 'Peta Monitoring', 'route' => 'maps.monitoring', 'icon' => 'fa-map-location-dot', 'active' => ['maps.monitoring']],
                ['label' => 'Rekap Bimbingan', 'route' => 'reports.monitoring', 'icon' => 'fa-chart-column', 'active' => ['reports.monitoring']],
            ],
        ];
    }

    if ($user->hasRole('admin')) {
        $groups[] = [
            'label' => 'Workflow Akademik',
            'items' => [
                ['label' => 'Ringkasan Manajemen', 'route' => 'management.dashboard', 'icon' => 'fa-chart-line', 'active' => ['management.dashboard']],
                ['label' => 'Program Kegiatan', 'route' => 'management.programs.index', 'icon' => 'fa-layer-group', 'active' => ['management.programs.*']],
                ['label' => 'Periode Program', 'route' => 'management.periods.index', 'icon' => 'fa-calendar-days', 'active' => ['management.periods.*']],
                ['label' => 'Koordinator Program', 'route' => 'management.coordinators.index', 'icon' => 'fa-user-gear', 'active' => ['management.coordinators.*']],
                ['label' => 'Validasi Pendaftaran', 'route' => 'management.enrollment-validations.index', 'icon' => 'fa-user-check', 'active' => ['management.enrollment-validations.*']],
                ['label' => 'Peserta Periode', 'route' => 'management.enrollments.index', 'icon' => 'fa-users-viewfinder', 'active' => ['management.enrollments.*']],
                ['label' => 'Usulan Tempat', 'route' => 'management.place-proposals.index', 'icon' => 'fa-building-circle-check', 'active' => ['management.place-proposals.*']],
                ['label' => 'Pindah Tempat', 'route' => 'management.relocations.index', 'icon' => 'fa-route', 'active' => ['management.relocations.*']],
                ['label' => 'Perubahan Pembimbing', 'route' => 'management.supervisor-requests.index', 'icon' => 'fa-user-pen', 'active' => ['management.supervisor-requests.*']],
                ['label' => 'Review Laporan', 'route' => 'management.submission-progress.index', 'icon' => 'fa-file-circle-check', 'active' => ['management.submission-progress.*']],
            ],
        ];

        $groups[] = [
            'label' => 'Master Data',
            'items' => [
                ['label' => 'User', 'route' => 'management.users.index', 'icon' => 'fa-users-gear', 'active' => ['management.users.*']],
                ['label' => 'Mahasiswa', 'route' => 'management.students.index', 'icon' => 'fa-user-graduate', 'active' => ['management.students.*']],
                ['label' => 'Dosen', 'route' => 'management.lecturers.index', 'icon' => 'fa-chalkboard-user', 'active' => ['management.lecturers.*']],
                ['label' => 'Prodi', 'route' => 'management.study-programs.index', 'icon' => 'fa-school', 'active' => ['management.study-programs.*']],
                ['label' => 'Mitra', 'route' => 'management.places.index', 'icon' => 'fa-building', 'active' => ['management.places.*']],
            ],
        ];
    }

    $monitoringItems = [
        ['label' => 'Peta Mitra', 'route' => 'maps.places', 'icon' => 'fa-map', 'active' => ['maps.places']],
        ['label' => 'Peta Monitoring', 'route' => 'maps.monitoring', 'icon' => 'fa-map-location-dot', 'active' => ['maps.monitoring']],
        ['label' => 'Rekap Monitoring', 'route' => 'reports.monitoring', 'icon' => 'fa-chart-column', 'active' => ['reports.monitoring']],
    ];

    if ($user->hasRole(['admin', 'dosen'])) {
        $monitoringItems[] = ['label' => 'Input Lokasi', 'route' => 'internship-places.create', 'icon' => 'fa-location-crosshairs', 'active' => ['internship-places.*']];
    }

    $groups[] = [
        'label' => 'Monitoring',
        'items' => $monitoringItems,
    ];

    if ($user->hasRole('admin')) {
        $groups[] = [
            'label' => 'Konfigurasi',
            'items' => [
                ['label' => 'Konfigurasi Sistem', 'route' => 'system-configurations.index', 'icon' => 'fa-sliders', 'active' => ['system-configurations.*']],
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

        <nav class="min-h-0 flex-1 space-y-6 overflow-y-auto px-3 py-5">
            @foreach ($groups as $group)
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-blue-300">{{ $group['label'] }}</p>
                    <div class="mt-2 space-y-1">
                        @foreach ($group['items'] as $item)
                            @php($active = request()->routeIs($item['active']))
                            <a href="{{ route($item['route']) }}" class="{{ $itemClass($active) }}">
                                <x-icon :name="$item['icon']" class="w-5 text-center" />
                                <span>{{ $item['label'] }}</span>
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
                <span class="hidden text-right text-sm sm:block">
                    <span class="block font-semibold text-gray-900">{{ $user->name }}</span>
                    <span class="block text-xs text-gray-500">{{ ucfirst($user->role) }}</span>
                </span>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-sm font-semibold text-blue-800 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            {{ strtoupper(Str::substr($user->name, 0, 1)) }}
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
                                @php($active = request()->routeIs($item['active']))
                                <a href="{{ route($item['route']) }}" class="{{ $mobileItemClass($active) }}" @click="open = false">
                                    <x-icon :name="$item['icon']" class="w-5 text-center" />
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="border-t border-gray-200 p-4">
                <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
            </div>
        </aside>
    </div>
</div>
