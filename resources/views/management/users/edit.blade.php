<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Edit User') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('management.users.update', $user) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf @method('PATCH')
            <x-input-label for="name" value="Nama" /><x-text-input id="name" name="name" class="block w-full" :value="$user->name" required />
            <x-input-label for="email" value="Email" /><x-text-input id="email" name="email" type="email" class="block w-full" :value="$user->email" required />
            <x-input-label for="role" value="Role" />
            <select id="role" name="role" class="block w-full rounded-md border-gray-300">
                @foreach (['admin', 'dosen', 'mahasiswa'] as $role)<option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>@endforeach
            </select>
            <x-input-label for="password" value="Password baru" /><x-text-input id="password" name="password" type="password" class="block w-full" />
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
