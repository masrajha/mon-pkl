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
            <div class="bg-white shadow-sm sm:rounded-lg"><div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Fakultas</th><th class="px-4 py-3">Status</th><th></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">@foreach ($studyPrograms as $program)<tr><td class="px-4 py-3">{{ $program->code }}</td><td class="px-4 py-3">{{ $program->name }}</td><td class="px-4 py-3">{{ $program->faculty ?: '-' }}</td><td class="px-4 py-3">{{ $program->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('management.study-programs.edit', $program) }}">Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><div class="p-4">{{ $studyPrograms->links() }}</div></div>
        </div>
    </div></div>
</x-app-layout>
