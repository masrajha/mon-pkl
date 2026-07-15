<x-app-layout>
    @php($avatarUrl = \App\Support\PublicStorage::url($user->avatar_url))

    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Edit User') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('management.users.update', $user) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg" enctype="multipart/form-data" x-data="{ role: @js(old('role', $user->role)) }">
            @csrf @method('PATCH')
            <div>
                <x-input-label for="avatar_photo" value="Foto Profil" />
                <div class="mt-2 flex items-center gap-4">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="Foto profil {{ $user->name }}" class="h-16 w-16 rounded-full object-cover ring-1 ring-gray-200">
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-xl font-semibold text-blue-700">{{ Str::of($user->name)->substr(0, 1)->upper() }}</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <input id="avatar_photo" name="avatar_photo" type="file" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                        <p class="mt-1 text-xs text-gray-500">Gunakan JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                        @if ($user->avatar_url)
                            <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="remove_avatar" value="1" class="rounded border-gray-300 text-blue-600">
                                Hapus foto profil saat ini
                            </label>
                        @endif
                    </div>
                </div>
                <x-input-error :messages="$errors->get('avatar_photo')" class="mt-2" />
            </div>
            <x-input-label for="name" value="Nama" /><x-text-input id="name" name="name" class="block w-full" :value="$user->name" required />
            <x-input-label for="email" value="Email" /><x-text-input id="email" name="email" type="email" class="block w-full" :value="$user->email" required />
            <x-input-label for="role" value="Role" />
            <select id="role" name="role" class="block w-full rounded-md border-gray-300" x-model="role">
                @foreach (['admin' => 'Admin', 'dosen' => 'Dosen', 'mahasiswa' => 'Mahasiswa'] + ($user->role === 'pembimbing_lapangan' ? ['pembimbing_lapangan' => 'Pembimbing Lapangan'] : []) as $role => $label)<option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>@endforeach
            </select>
            <x-input-label for="status" value="Status Akun" />
            <select id="status" name="status" class="block w-full rounded-md border-gray-300">
                <option value="active" @selected(old('status', $user->status ?? 'active') === 'active')>Aktif</option>
                <option value="inactive" @selected(old('status', $user->status ?? 'active') === 'inactive')>Nonaktif</option>
            </select>
            <div x-show="role === 'mahasiswa'" class="space-y-3 rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                <p class="text-sm font-semibold text-blue-900">Profil Mahasiswa</p>
                <x-input-label for="student_npm" value="NPM" /><x-text-input id="student_npm" name="student_npm" class="block w-full" :value="old('student_npm', $user->student?->npm)" />
                <x-input-label for="student_study_program_id" value="Prodi" />
                <select id="student_study_program_id" name="student_study_program_id" class="block w-full rounded-md border-gray-300">
                    <option value="">Pilih prodi</option>
                    @foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected((string) old('student_study_program_id', $user->student?->study_program_id) === (string) $program->id)>{{ $program->name }}</option>@endforeach
                </select>
                <x-input-label for="student_phone" value="No HP" /><x-text-input id="student_phone" name="student_phone" class="block w-full" :value="old('student_phone', $user->student?->phone)" />
                <p class="text-xs text-blue-700">Email profil mahasiswa mengikuti email akun login di atas.</p>
            </div>
            <div x-show="role === 'dosen'" class="space-y-3 rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                <p class="text-sm font-semibold text-blue-900">Profil Dosen</p>
                <x-input-label for="lecturer_study_program_id" value="Prodi" />
                <select id="lecturer_study_program_id" name="lecturer_study_program_id" class="block w-full rounded-md border-gray-300">
                    <option value="">Belum ditentukan</option>
                    @foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected((string) old('lecturer_study_program_id', $user->lecturer?->study_program_id) === (string) $program->id)>{{ $program->name }}</option>@endforeach
                </select>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><x-input-label for="lecturer_nip" value="NIP" /><x-text-input id="lecturer_nip" name="lecturer_nip" class="block w-full" :value="old('lecturer_nip', $user->lecturer?->nip)" /></div>
                    <div><x-input-label for="lecturer_nidn" value="NIDN" /><x-text-input id="lecturer_nidn" name="lecturer_nidn" class="block w-full" :value="old('lecturer_nidn', $user->lecturer?->nidn)" /></div>
                </div>
                <p class="text-xs text-blue-700">Email profil dosen mengikuti email akun login di atas.</p>
                <x-input-label for="lecturer_status" value="Status Dosen" />
                <select id="lecturer_status" name="lecturer_status" class="block w-full rounded-md border-gray-300">
                    <option value="active" @selected(old('lecturer_status', $user->lecturer?->status ?? 'active') === 'active')>Aktif</option>
                    <option value="inactive" @selected(old('lecturer_status', $user->lecturer?->status ?? 'active') === 'inactive')>Nonaktif</option>
                </select>
            </div>
            <x-input-label for="password" value="Password baru" /><x-text-input id="password" name="password" type="password" class="block w-full" />
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>

        <section class="mt-6 rounded-lg border border-red-200 bg-red-50 p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="font-semibold text-red-900">Hapus User</h3>
                    <p class="mt-1 text-sm text-red-800">
                        Sistem akan menghapus permanen user yang belum terkait kegiatan. Jika sudah punya data kegiatan, akun hanya dinonaktifkan agar riwayat tetap aman.
                    </p>
                </div>
                <form method="POST" action="{{ route('management.users.destroy', $user) }}" onsubmit="return confirm('Hapus user ini? Jika sudah terkait kegiatan, akun akan dinonaktifkan dan riwayat tetap disimpan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" @disabled(auth()->id() === $user->id)>
                        <x-icon name="fa-trash" /> Hapus User
                    </button>
                </form>
            </div>
            @if (auth()->id() === $user->id)
                <p class="mt-3 text-xs text-red-700">Akun yang sedang digunakan untuk login tidak dapat dihapus dari halaman ini.</p>
            @endif
        </section>
    </div></div>
</x-app-layout>
