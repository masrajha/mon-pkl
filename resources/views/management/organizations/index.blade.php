<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Manajemen Organisasi') }}</h2></x-slot>

    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.organizations.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Organisasi</h3>

                <div>
                    <x-input-label for="code" value="Kode" />
                    <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code')" required />
                </div>

                <div>
                    <x-input-label for="name" value="Nama Organisasi" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                </div>

                <div>
                    <x-input-label for="type" value="Jenis" />
                    <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300" required data-organization-type>
                        @foreach ($types as $type => $label)
                            <option value="{{ $type }}" @selected(old('type') === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="parent_id" value="Parent Organisasi" />
                    <select id="parent_id" name="parent_id" class="mt-1 block w-full rounded-md border-gray-300" data-organization-parent>
                        <option value="" data-parent-for="university">Tanpa parent</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" data-parent-for="{{ $parent->type === 'university' ? 'faculty' : 'department' }}" @selected((string) old('parent_id') === (string) $parent->id)>{{ $types[$parent->type] ?? $parent->type }} - {{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Fakultas berada di bawah universitas, jurusan berada di bawah fakultas.</p>
                </div>

                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-gray-300"> Aktif</label>
                <x-primary-button>Simpan</x-primary-button>
            </form>

            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Organisasi" description="Kelola hierarki universitas, fakultas, dan jurusan." search-placeholder="Cari organisasi...">
                    <x-slot name="filters">
                        <div>
                            <x-input-label for="filter_type" value="Jenis" />
                            <select id="filter_type" name="type" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua jenis</option>
                                @foreach ($types as $type => $label)
                                    <option value="{{ $type }}" @selected($selectedType === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-3">
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
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell"><x-sortable-heading column="code" label="Kode" /></th>
                                <th class="silat-table-cell"><x-sortable-heading column="name" label="Nama" /></th>
                                <th class="silat-table-cell"><x-sortable-heading column="type" label="Jenis" /></th>
                                <th class="silat-table-cell">Parent</th>
                                <th class="silat-table-cell"><x-sortable-heading column="is_active" label="Status" /></th>
                                <th class="silat-table-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($organizations as $organization)
                                <tr>
                                    <td class="silat-table-cell font-medium text-gray-900">{{ $organization->code }}</td>
                                    <td class="silat-table-cell">{{ $organization->name }}</td>
                                    <td class="silat-table-cell">{{ $types[$organization->type] ?? $organization->type }}</td>
                                    <td class="silat-table-cell text-gray-600">{{ $organization->parent?->name ?: '-' }}</td>
                                    <td class="silat-table-cell"><x-badge :variant="$organization->is_active ? 'success' : 'neutral'">{{ $organization->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                                    <td class="silat-table-cell text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a class="silat-secondary-link justify-end" href="{{ route('management.organizations.edit', $organization) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a>
                                            <form method="POST" action="{{ route('management.organizations.destroy', $organization) }}" onsubmit="return confirm('Hapus organisasi ini? Organisasi yang sudah dipakai data lain akan ditolak.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                    <x-icon name="fa-trash" /> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-table-pagination :paginator="$organizations" />
            </div>
        </div>
    </div></div>
    @include('management.organizations.partials.parent-filter-script')
</x-app-layout>
