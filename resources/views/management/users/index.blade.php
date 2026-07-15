<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Manajemen User') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.users.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg" enctype="multipart/form-data" x-data="{ role: @js(old('role', 'admin')) }">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah User</h3>
                <x-input-label for="avatar_photo" value="Foto Profil" />
                <input id="avatar_photo" name="avatar_photo" type="file" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                <x-input-error :messages="$errors->get('avatar_photo')" />
                <x-input-label for="name" value="Nama" /><x-text-input id="name" name="name" class="block w-full" required />
                <x-input-label for="email" value="Email" /><x-text-input id="email" name="email" type="email" class="block w-full" required />
                <x-input-label for="role" value="Role" />
                <select id="role" name="role" class="block w-full rounded-md border-gray-300" x-model="role">
                    <option value="admin">Admin</option><option value="dosen">Dosen</option><option value="mahasiswa">Mahasiswa</option>
                </select>
                <div x-show="role === 'mahasiswa'" class="space-y-3 rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                    <p class="text-sm font-semibold text-blue-900">Profil Mahasiswa</p>
                    <x-input-label for="student_npm" value="NPM" /><x-text-input id="student_npm" name="student_npm" class="block w-full" :value="old('student_npm')" />
                    <x-input-label for="student_study_program_id" value="Prodi" />
                    <select id="student_study_program_id" name="student_study_program_id" class="block w-full rounded-md border-gray-300">
                        <option value="">Pilih prodi</option>
                        @foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected((string) old('student_study_program_id') === (string) $program->id)>{{ $program->name }}</option>@endforeach
                    </select>
                    <x-input-label for="student_phone" value="No HP" /><x-text-input id="student_phone" name="student_phone" class="block w-full" :value="old('student_phone')" />
                    <p class="text-xs text-blue-700">Email profil mahasiswa mengikuti email akun login di atas.</p>
                </div>
                <div x-show="role === 'dosen'" class="space-y-3 rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                    <p class="text-sm font-semibold text-blue-900">Profil Dosen</p>
                    <x-input-label for="lecturer_study_program_id" value="Prodi" />
                    <select id="lecturer_study_program_id" name="lecturer_study_program_id" class="block w-full rounded-md border-gray-300">
                        <option value="">Belum ditentukan</option>
                        @foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected((string) old('lecturer_study_program_id') === (string) $program->id)>{{ $program->name }}</option>@endforeach
                    </select>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><x-input-label for="lecturer_nip" value="NIP" /><x-text-input id="lecturer_nip" name="lecturer_nip" class="block w-full" :value="old('lecturer_nip')" /></div>
                        <div><x-input-label for="lecturer_nidn" value="NIDN" /><x-text-input id="lecturer_nidn" name="lecturer_nidn" class="block w-full" :value="old('lecturer_nidn')" /></div>
                    </div>
                    <p class="text-xs text-blue-700">Email profil dosen mengikuti email akun login di atas.</p>
                    <input type="hidden" name="lecturer_status" value="active">
                </div>
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
                                @foreach (['admin' => 'Admin', 'dosen' => 'Dosen', 'mahasiswa' => 'Mahasiswa', 'pembimbing_lapangan' => 'Pembimbing Lapangan'] as $value => $label)<option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell"><x-sortable-heading column="name" label="Nama" /></th><th class="silat-table-cell"><x-sortable-heading column="email" label="Email" /></th><th class="silat-table-cell"><x-sortable-heading column="role" label="Role" /></th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody>@foreach ($users as $user)@php($avatarUrl = \App\Support\PublicStorage::url($user->avatar_url))<tr><td class="silat-table-cell font-medium text-gray-900"><div class="flex items-center gap-3">@if ($avatarUrl)<img src="{{ $avatarUrl }}" alt="Foto profil {{ $user->name }}" class="h-9 w-9 rounded-full object-cover">@else<div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-xs font-semibold text-blue-700">{{ Str::of($user->name)->substr(0, 1)->upper() }}</div>@endif<span>{{ $user->name }}</span></div></td><td class="silat-table-cell text-gray-600">{{ $user->email }}</td><td class="silat-table-cell"><div class="flex flex-wrap gap-2"><x-badge variant="neutral">{{ Str::title($user->role) }}</x-badge><x-badge :variant="($user->status ?? 'active') === 'active' ? 'success' : 'neutral'">{{ ($user->status ?? 'active') === 'active' ? 'Aktif' : 'Nonaktif' }}</x-badge></div></td><td class="silat-table-cell text-right"><a class="silat-secondary-link justify-end" href="{{ route('management.users.edit', $user) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><x-table-pagination :paginator="$users" /></div>
        </div>
    </div></div>
</x-app-layout>
