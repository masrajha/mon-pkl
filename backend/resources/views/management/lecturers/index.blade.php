<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Manajemen Dosen') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.lecturers.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Dosen</h3>
                @include('management.lecturers.partials.fields', ['lecturer' => null])
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="bg-white shadow-sm sm:rounded-lg"><div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3">Dosen</th><th class="px-4 py-3">NIP/NIDN</th><th class="px-4 py-3">Prodi</th><th class="px-4 py-3">Status</th><th></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">@foreach ($lecturers as $lecturer)<tr><td class="px-4 py-3">{{ $lecturer->name }}<div class="text-xs text-gray-500">{{ $lecturer->email ?: $lecturer->user?->email ?: '-' }}</div></td><td class="px-4 py-3">{{ $lecturer->nip ?: '-' }}<div class="text-xs text-gray-500">{{ $lecturer->nidn ?: '-' }}</div></td><td class="px-4 py-3">{{ $lecturer->studyProgram?->name ?: '-' }}</td><td class="px-4 py-3">{{ $lecturer->status === 'active' ? 'Aktif' : 'Nonaktif' }}</td><td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('management.lecturers.edit', $lecturer) }}">Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><div class="p-4">{{ $lecturers->links() }}</div></div>
        </div>
    </div></div>
</x-app-layout>
