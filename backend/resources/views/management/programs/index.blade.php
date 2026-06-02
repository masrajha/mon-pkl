<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Program Kegiatan') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.programs.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Program</h3>
                <x-input-label for="code" value="Kode" /><x-text-input id="code" name="code" class="block w-full uppercase" required />
                <x-input-label for="name" value="Nama Program" /><x-text-input id="name" name="name" class="block w-full" required />
                <x-input-label for="rule_key" value="Rule Aktif" />
                <select id="rule_key" name="rule_key" class="block w-full rounded-md border-gray-300">
                    <option value="kerja_praktik">Rule Kerja Praktik</option>
                </select>
                <x-input-label for="description" value="Deskripsi" />
                <textarea id="description" name="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300"> Aktif</label>
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Program" description="Cari kode, nama, atau rule program." search-placeholder="Cari program...">
                    <x-slot name="filters">
                        <div>
                            <x-input-label for="filter_status" value="Status" />
                            <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua status</option><option value="active" @selected($selectedStatus === 'active')>Aktif</option><option value="inactive" @selected($selectedStatus === 'inactive')>Nonaktif</option></select>
                        </div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell"><x-sortable-heading column="name" label="Program" /></th><th class="silat-table-cell"><x-sortable-heading column="rule_key" label="Rule" /></th><th class="silat-table-cell">Periode</th><th class="silat-table-cell"><x-sortable-heading column="is_active" label="Status" /></th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($programs as $program)
                            <tr>
                                <td class="silat-table-cell"><span class="font-medium text-gray-900">{{ $program->name }}</span><div class="text-xs text-gray-500">{{ $program->code }}</div></td>
                                <td class="silat-table-cell"><x-badge>{{ $program->rule_key === 'kerja_praktik' ? 'Rule Kerja Praktik' : $program->rule_key }}</x-badge></td>
                                <td class="silat-table-cell text-gray-600">{{ $program->periods_count }}</td>
                                <td class="silat-table-cell"><x-badge :variant="$program->is_active ? 'success' : 'neutral'">{{ $program->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                                <td class="silat-table-cell text-right"><a class="silat-secondary-link justify-end" href="{{ route('management.programs.edit', $program) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div><x-table-pagination :paginator="$programs" /></div>
        </div>
    </div></div>
</x-app-layout>
