<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Konfigurasi Program') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Konfigurasi Periode" description="Cari periode, program, atau tahun akademik." search-placeholder="Cari konfigurasi...">
                    <x-slot name="filters">
                        <div>
                            <x-input-label for="filter_status" value="Status Periode" />
                            <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua status</option>
                                <option value="active" @selected($selectedStatus === 'active')>Aktif</option>
                                <option value="inactive" @selected($selectedStatus === 'inactive')>Tidak aktif</option>
                            </select>
                        </div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell"><x-sortable-heading column="name" :label="__('Periode')" /></th>
                                <th class="silat-table-cell"><x-sortable-heading column="academic_year" :label="__('Tahun Akademik')" /></th>
                                <th class="silat-table-cell"><x-sortable-heading column="is_active" :label="__('Status')" /></th>
                                <th class="silat-table-cell">{{ __('Konfigurasi') }}</th>
                                <th class="silat-table-cell text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periods as $period)
                                <tr>
                                    <td class="silat-table-cell font-medium text-gray-900">{{ $period->display_name }}</td>
                                    <td class="silat-table-cell text-gray-600">{{ $period->academic_year }} / {{ $period->semester }}</td>
                                    <td class="silat-table-cell"><x-badge :variant="$period->is_active ? 'success' : 'neutral'">{{ $period->is_active ? __('Aktif') : __('Tidak aktif') }}</x-badge></td>
                                    <td class="silat-table-cell"><x-badge :variant="$period->setting ? 'info' : 'neutral'">{{ $period->setting ? __('Sudah ada') : __('Memakai default') }}</x-badge></td>
                                    <td class="silat-table-cell text-right">
                                        <a class="silat-secondary-link justify-end" href="{{ route('system-configurations.edit', $period) }}">
                                            <x-icon name="fa-sliders" class="mr-1" /> {{ __('Atur') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="silat-table-cell"><x-empty-state :title="__('Belum ada periode program')" icon="fa-calendar-days" /></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-table-pagination :paginator="$periods" />
            </div>
        </div>
    </div>
</x-app-layout>
