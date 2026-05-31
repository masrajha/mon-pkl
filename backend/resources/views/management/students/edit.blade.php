<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Edit Mahasiswa') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('management.students.update', $student) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf @method('PATCH')
            <x-input-label for="npm" value="NPM" /><x-text-input id="npm" name="npm" class="block w-full" :value="$student->npm" required />
            <x-input-label for="full_name" value="Nama Lengkap" /><x-text-input id="full_name" name="full_name" class="block w-full" :value="$student->full_name" required />
            <x-input-label for="study_program_id" value="Prodi" />
            <select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300"><option value="">Pilih prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected($student->study_program_id === $program->id)>{{ $program->name }}</option>@endforeach</select>
            <x-input-label for="user_id" value="Akun Login" />
            <select id="user_id" name="user_id" class="block w-full rounded-md border-gray-300"><option value="">Belum dihubungkan</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected($student->user_id === $user->id)>{{ $user->name }} - {{ $user->email }}</option>@endforeach</select>
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
