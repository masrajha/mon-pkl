<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-base font-semibold">{{ __('Peta Tempat PKL') }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ __('Lihat sebaran instansi PKL dari data hasil import.') }}</p>
                        <a class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('maps.places') }}">
                            {{ __('Buka peta tempat PKL') }}
                        </a>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-base font-semibold">{{ __('Peta Monitoring') }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ __('Pantau check-in mahasiswa dengan marker lokasi dan garis ke instansi.') }}</p>
                        <a class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('maps.monitoring') }}">
                            {{ __('Buka peta monitoring') }}
                        </a>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-base font-semibold">{{ __('Rekap Monitoring') }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ __('Lihat rekap hari hadir, durasi, jarak, jam masuk, dan jam pulang.') }}</p>
                        <a class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('reports.monitoring') }}">
                            {{ __('Buka rekap monitoring') }}
                        </a>
                    </div>
                </div>

                @if (Auth::user()->hasRole('mahasiswa'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-base font-semibold">{{ __('Check-In PKL') }}</h3>
                            <p class="mt-2 text-sm text-gray-600">{{ __('Kirim lokasi, foto bukti, dan catatan harian melalui backend baru.') }}</p>
                            <a class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('check-ins.create') }}">
                                {{ __('Buka check-in') }}
                            </a>
                        </div>
                    </div>
                @endif

                @if (Auth::user()->hasRole(['admin', 'dosen']))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-base font-semibold">{{ __('Input Lokasi PKL') }}</h3>
                            <p class="mt-2 text-sm text-gray-600">{{ __('Tambahkan atau perbarui koordinat instansi memakai Leaflet.') }}</p>
                            <a class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('internship-places.create') }}">
                                {{ __('Input lokasi') }}
                            </a>
                        </div>
                    </div>
                @endif

                @if (Auth::user()->hasRole('admin'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-base font-semibold">{{ __('Manajemen') }}</h3>
                            <p class="mt-2 text-sm text-gray-600">{{ __('Kelola user, master data, periode, konfigurasi, dan peserta PKL per periode.') }}</p>
                            <a class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('management.dashboard') }}">
                                {{ __('Buka manajemen') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
