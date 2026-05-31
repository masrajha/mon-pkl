<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Manajemen Mahasiswa') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.students.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Mahasiswa</h3>
                <x-input-label for="npm" value="NPM" /><x-text-input id="npm" name="npm" class="block w-full" required />
                <x-input-label for="full_name" value="Nama Lengkap" /><x-text-input id="full_name" name="full_name" class="block w-full" required />
                <x-input-label for="study_program_id" value="Prodi" />
                <select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300"><option value="">Pilih prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select>
                <x-input-label for="user_id" value="Akun Login" />
                <select id="user_id" name="user_id" class="block w-full rounded-md border-gray-300"><option value="">Belum dihubungkan</option>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>@endforeach</select>
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="bg-white shadow-sm sm:rounded-lg"><div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3">NPM</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Prodi</th><th class="px-4 py-3">Akun</th><th></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">@foreach ($students as $student)<tr><td class="px-4 py-3">{{ $student->npm }}</td><td class="px-4 py-3">{{ $student->full_name }}</td><td class="px-4 py-3">{{ $student->studyProgram?->name ?: '-' }}</td><td class="px-4 py-3">{{ $student->user?->email ?: '-' }}</td><td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('management.students.edit', $student) }}">Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><div class="p-4">{{ $students->links() }}</div></div>
        </div>
    </div></div>
</x-app-layout>
