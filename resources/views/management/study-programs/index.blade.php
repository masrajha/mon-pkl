<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Manajemen Prodi') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.study-programs.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Prodi</h3>
                <x-input-label for="code" value="Kode" /><x-text-input id="code" name="code" class="block w-full" required />
                <x-input-label for="name" value="Nama" /><x-text-input id="name" name="name" class="block w-full" required />
                <x-input-label for="faculty" value="Fakultas" /><x-text-input id="faculty" name="faculty" class="block w-full" />
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300"> Aktif</label>
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Prodi" description="Cari kode, nama, atau fakultas." search-placeholder="Cari prodi...">
                    <x-slot name="filters">
                        <div>
                            <x-input-label for="filter_status" value="Status" />
                            <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua status</option>
                                <option value="active" @selected($selectedStatus === 'active')>Aktif</option>
                                <option value="inactive" @selected($selectedStatus === 'inactive')>Nonaktif</option>
                            </select>
                        </div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell"><x-sortable-heading column="code" label="Kode" /></th><th class="silat-table-cell"><x-sortable-heading column="name" label="Nama" /></th><th class="silat-table-cell"><x-sortable-heading column="faculty" label="Fakultas" /></th><th class="silat-table-cell"><x-sortable-heading column="is_active" label="Status" /></th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody>@foreach ($studyPrograms as $program)<tr><td class="silat-table-cell font-medium text-gray-900">{{ $program->code }}</td><td class="silat-table-cell">{{ $program->name }}</td><td class="silat-table-cell text-gray-600">{{ $program->faculty ?: '-' }}</td><td class="silat-table-cell"><x-badge :variant="$program->is_active ? 'success' : 'neutral'">{{ $program->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td><td class="silat-table-cell text-right"><a class="silat-secondary-link justify-end" href="{{ route('management.study-programs.edit', $program) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><x-table-pagination :paginator="$studyPrograms" /></div>
        </div>
    </div></div>
</x-app-layout>
