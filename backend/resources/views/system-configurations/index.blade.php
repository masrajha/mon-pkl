<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Konfigurasi Sistem') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('Periode') }}</th>
                                <th class="px-6 py-3">{{ __('Tahun Akademik') }}</th>
                                <th class="px-6 py-3">{{ __('Status') }}</th>
                                <th class="px-6 py-3">{{ __('Konfigurasi') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($periods as $period)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $period->name }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $period->academic_year }} / {{ $period->semester }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $period->is_active ? __('Aktif') : __('Tidak aktif') }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $period->setting ? __('Sudah ada') : __('Memakai default') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a class="font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('system-configurations.edit', $period) }}">
                                            {{ __('Atur') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">{{ __('Belum ada periode PKL.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
