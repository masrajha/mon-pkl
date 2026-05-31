@php
    $user = Auth::user();
    $navButton = 'inline-flex h-16 items-center border-b-2 border-transparent px-1 text-sm font-semibold leading-5 text-slate-600 transition duration-150 ease-in-out hover:border-amber-500 hover:text-slate-900 focus:border-amber-500 focus:text-slate-900 focus:outline-none';
    $navButtonActive = 'inline-flex h-16 items-center border-b-2 border-amber-500 px-1 text-sm font-semibold leading-5 text-slate-950 transition duration-150 ease-in-out focus:border-amber-600 focus:outline-none';
@endphp

<nav x-data="{ open: false }" class="monpkl-institution-bar">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <span class="monpkl-brand-mark">MP</span>
                        <span class="hidden leading-none md:block">
                            <span class="monpkl-brand-title">MonPKL</span>
                            <span class="monpkl-brand-subtitle block">Universitas Lampung</span>
                        </span>
                    </a>
                </div>

                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if ($user->hasRole('mahasiswa'))
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="{{ request()->routeIs('student.*') || request()->routeIs('check-ins.*') ? $navButtonActive : $navButton }}">
                                    {{ __('PKL Saya') }}
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('student.dashboard')">{{ __('Ringkasan PKL') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('student.profile.edit')">{{ __('Profil Saya') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('student.enrollments.create')">{{ __('Pendaftaran PKL') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('student.proposals.create')">{{ __('Usulan Tempat') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('check-ins.create')">{{ __('Presensi') }}</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif

                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button class="{{ request()->routeIs('maps.*') || request()->routeIs('reports.monitoring') ? $navButtonActive : $navButton }}">
                                {{ __('Monitoring') }}
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('maps.places')">{{ __('Peta Tempat PKL') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('maps.monitoring')">{{ __('Peta Monitoring') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('reports.monitoring')">{{ __('Rekap Monitoring') }}</x-dropdown-link>
                            @if ($user->hasRole(['admin', 'dosen']))
                                <x-dropdown-link :href="route('internship-places.create')">{{ __('Input Lokasi') }}</x-dropdown-link>
                            @endif
                        </x-slot>
                    </x-dropdown>

                    @if ($user->hasRole('koordinator'))
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="{{ request()->routeIs('coordinator.*') ? $navButtonActive : $navButton }}">
                                    {{ __('Koordinator') }}
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('coordinator.dashboard')">{{ __('Dashboard Koordinator') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('maps.monitoring')">{{ __('Monitoring Prodi') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('reports.monitoring')">{{ __('Rekap Prodi') }}</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif

                    @if ($user->hasRole('admin'))
                        <x-dropdown align="left" width="64">
                            <x-slot name="trigger">
                                <button class="{{ request()->routeIs('management.*') || request()->routeIs('system-configurations.*') ? $navButtonActive : $navButton }}">
                                    {{ __('Manajemen') }}
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="px-4 py-2 text-xs font-semibold uppercase text-gray-400">{{ __('Akademik PKL') }}</div>
                                <x-dropdown-link :href="route('management.periods.index')">{{ __('Periode PKL') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('management.coordinators.index')">{{ __('Koordinator PKL') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('management.enrollments.index')">{{ __('Peserta Periode') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('management.place-proposals.index')">{{ __('Usulan Tempat') }}</x-dropdown-link>
                                <div class="border-t border-gray-100 px-4 py-2 text-xs font-semibold uppercase text-gray-400">{{ __('Master Data') }}</div>
                                <x-dropdown-link :href="route('management.users.index')">{{ __('User') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('management.students.index')">{{ __('Mahasiswa') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('management.lecturers.index')">{{ __('Dosen') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('management.study-programs.index')">{{ __('Prodi') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('management.places.index')">{{ __('Tempat PKL') }}</x-dropdown-link>
                                <div class="border-t border-gray-100 px-4 py-2 text-xs font-semibold uppercase text-gray-400">{{ __('Konfigurasi') }}</div>
                                <x-dropdown-link :href="route('system-configurations.index')">{{ __('Konfigurasi Sistem') }}</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium leading-4 text-slate-600 transition duration-150 ease-in-out hover:border-slate-300 hover:text-slate-900 focus:outline-none">
                            <div>{{ $user->name }}</div>
                            <div class="ms-1">
                                <svg class="h-4 w-4 fill-current text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-responsive-nav-link>

            @if ($user->hasRole('mahasiswa'))
                <div class="px-4 pb-1 pt-3 text-xs font-semibold uppercase text-gray-400">{{ __('PKL Saya') }}</div>
                <x-responsive-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">{{ __('Ringkasan PKL') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('student.profile.edit')" :active="request()->routeIs('student.profile.*')">{{ __('Profil Saya') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('student.enrollments.create')" :active="request()->routeIs('student.enrollments.*')">{{ __('Pendaftaran PKL') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('student.proposals.create')" :active="request()->routeIs('student.proposals.*')">{{ __('Usulan Tempat') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('check-ins.create')" :active="request()->routeIs('check-ins.*')">{{ __('Presensi') }}</x-responsive-nav-link>
            @endif

            <div class="px-4 pb-1 pt-3 text-xs font-semibold uppercase text-gray-400">{{ __('Monitoring') }}</div>
            <x-responsive-nav-link :href="route('maps.places')" :active="request()->routeIs('maps.places')">{{ __('Peta Tempat PKL') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('maps.monitoring')" :active="request()->routeIs('maps.monitoring')">{{ __('Peta Monitoring') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.monitoring')" :active="request()->routeIs('reports.monitoring')">{{ __('Rekap Monitoring') }}</x-responsive-nav-link>
            @if ($user->hasRole(['admin', 'dosen']))
                <x-responsive-nav-link :href="route('internship-places.create')" :active="request()->routeIs('internship-places.*')">{{ __('Input Lokasi') }}</x-responsive-nav-link>
            @endif

            @if ($user->hasRole('koordinator'))
                <div class="px-4 pb-1 pt-3 text-xs font-semibold uppercase text-gray-400">{{ __('Koordinator') }}</div>
                <x-responsive-nav-link :href="route('coordinator.dashboard')" :active="request()->routeIs('coordinator.*')">{{ __('Dashboard Koordinator') }}</x-responsive-nav-link>
            @endif

            @if ($user->hasRole('admin'))
                <div class="px-4 pb-1 pt-3 text-xs font-semibold uppercase text-gray-400">{{ __('Manajemen') }}</div>
                <x-responsive-nav-link :href="route('management.dashboard')" :active="request()->routeIs('management.dashboard')">{{ __('Ringkasan Manajemen') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('management.periods.index')" :active="request()->routeIs('management.periods.*')">{{ __('Periode PKL') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('management.coordinators.index')" :active="request()->routeIs('management.coordinators.*')">{{ __('Koordinator PKL') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('management.enrollments.index')" :active="request()->routeIs('management.enrollments.*')">{{ __('Peserta Periode') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('management.place-proposals.index')" :active="request()->routeIs('management.place-proposals.*')">{{ __('Usulan Tempat') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('management.users.index')" :active="request()->routeIs('management.users.*')">{{ __('User') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('management.students.index')" :active="request()->routeIs('management.students.*')">{{ __('Mahasiswa') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('management.lecturers.index')" :active="request()->routeIs('management.lecturers.*')">{{ __('Dosen') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('management.study-programs.index')" :active="request()->routeIs('management.study-programs.*')">{{ __('Prodi') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('management.places.index')" :active="request()->routeIs('management.places.*')">{{ __('Tempat PKL') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('system-configurations.index')" :active="request()->routeIs('system-configurations.*')">{{ __('Konfigurasi Sistem') }}</x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-gray-200 pb-1 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800">{{ $user->name }}</div>
                <div class="text-sm font-medium text-gray-500">{{ $user->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
