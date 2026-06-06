<x-app-layout>
    @php($avatarUrl = \App\Support\PublicStorage::url($user->avatar_url))

    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Edit User') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('management.users.update', $user) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg" enctype="multipart/form-data">
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
            <select id="role" name="role" class="block w-full rounded-md border-gray-300">
                @foreach (['admin' => 'Admin', 'dosen' => 'Dosen', 'mahasiswa' => 'Mahasiswa'] + ($user->role === 'pembimbing_lapangan' ? ['pembimbing_lapangan' => 'Pembimbing Lapangan'] : []) as $role => $label)<option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>@endforeach
            </select>
            <x-input-label for="password" value="Password baru" /><x-text-input id="password" name="password" type="password" class="block w-full" />
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
