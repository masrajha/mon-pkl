<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Manajemen User') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.users.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah User</h3>
                <x-input-label for="name" value="Nama" /><x-text-input id="name" name="name" class="block w-full" required />
                <x-input-label for="email" value="Email" /><x-text-input id="email" name="email" type="email" class="block w-full" required />
                <x-input-label for="role" value="Role" />
                <select id="role" name="role" class="block w-full rounded-md border-gray-300">
                    <option value="admin">Admin</option><option value="dosen">Dosen</option><option value="mahasiswa">Mahasiswa</option>
                </select>
                <x-input-label for="password" value="Password" /><x-text-input id="password" name="password" type="password" class="block w-full" required />
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar User" description="Cari berdasarkan nama, email, atau role." search-placeholder="Cari user...">
                    <x-slot name="filters">
                        <div>
                            <x-input-label for="filter_role" value="Role" />
                            <select id="filter_role" name="role" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua role</option>
                                @foreach (['admin' => 'Admin', 'dosen' => 'Dosen', 'mahasiswa' => 'Mahasiswa'] as $value => $label)<option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell"><x-sortable-heading column="name" label="Nama" /></th><th class="silat-table-cell"><x-sortable-heading column="email" label="Email" /></th><th class="silat-table-cell"><x-sortable-heading column="role" label="Role" /></th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody>@foreach ($users as $user)<tr><td class="silat-table-cell font-medium text-gray-900">{{ $user->name }}</td><td class="silat-table-cell text-gray-600">{{ $user->email }}</td><td class="silat-table-cell"><x-badge variant="neutral">{{ Str::title($user->role) }}</x-badge></td><td class="silat-table-cell text-right"><a class="silat-secondary-link justify-end" href="{{ route('management.users.edit', $user) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><x-table-pagination :paginator="$users" /></div>
        </div>
    </div></div>
</x-app-layout>
