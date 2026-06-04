<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Master Mitra') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white" href="{{ route('internship-places.create') }}">Tambah Mitra</a>
        </div>
        <div class="silat-card overflow-hidden">
            <x-table-controls title="Daftar Mitra" description="Cari instansi, alamat, atau kota." search-placeholder="Cari mitra...">
                <x-slot name="filters">
                    <div>
                        <x-input-label for="filter_status" value="Status" />
                        <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">Semua status</option>
                            <option value="active" @selected($selectedStatus === 'active')>Aktif</option>
                            <option value="inactive" @selected($selectedStatus === 'inactive')>Nonaktif</option>
                        </select>
                    </div>
                    <div class="mt-3">
                        <x-input-label for="filter_period_id" value="Periode Program" />
                        <select id="filter_period_id" name="period_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">Semua periode</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected($selectedPeriod === $period->id)>{{ $period->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-slot>
            </x-table-controls>
            <form method="POST" action="{{ route('management.places.bulk') }}">
                @csrf
                <div class="silat-table-toolbar lg:items-end">
                <div>
                    <x-input-label for="action" value="Bulk action" />
                    <select id="action" name="action" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="delete">Hapus yang peserta 0</option>
                        <option value="merge">Merge ke mitra tujuan</option>
                    </select>
                </div>
                <div class="min-w-80 flex-1">
                    <x-input-label for="target_place_id" value="Tujuan merge" />
                    <select id="target_place_id" name="target_place_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option value="">Pilih salah satu mitra yang dicentang</option>
                        @foreach ($allPlaces as $target)
                            <option value="{{ $target->id }}">{{ $target->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Jalankan</x-primary-button>
            </div>
            <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell"><input type="checkbox" class="rounded border-gray-300" onclick="document.querySelectorAll('[data-place-checkbox]').forEach((el) => el.checked = this.checked)"></th><th class="silat-table-cell"><x-sortable-heading column="name" label="Mitra" /></th><th class="silat-table-cell">Kota</th><th class="silat-table-cell"><x-sortable-heading column="is_active" label="Status" /></th><th class="silat-table-cell">Lokasi Mitra</th><th class="silat-table-cell">{{ $selectedPeriod ? 'Peserta Periode' : 'Peserta Total' }}</th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody>@foreach ($places as $place)<tr><td class="silat-table-cell"><input data-place-checkbox type="checkbox" name="place_ids[]" value="{{ $place->id }}" class="rounded border-gray-300"></td><td class="silat-table-cell"><div class="font-medium text-gray-900">{{ $place->name }}</div><div class="text-xs text-gray-500">{{ $place->address }}</div></td><td class="silat-table-cell text-gray-600">{{ $place->city?->name ?: '-' }}</td><td class="silat-table-cell"><x-badge :variant="$place->is_active ? 'success' : 'neutral'">{{ $place->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td><td class="silat-table-cell text-gray-600">{{ $place->latitude && $place->longitude ? $place->latitude.', '.$place->longitude : '-' }}</td><td class="silat-table-cell text-gray-600">{{ $place->enrollments_count }}</td><td class="silat-table-cell text-right"><a class="silat-secondary-link justify-end" href="{{ route('internship-places.edit', $place) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div>
            </form>
            <x-table-pagination :paginator="$places" />
        </div>
    </div></div>
</x-app-layout>
