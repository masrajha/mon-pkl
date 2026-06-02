<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Profil Mahasiswa') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('student.profile.update') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf @method('PATCH')
            <x-input-label for="npm" value="NPM" /><x-text-input id="npm" name="npm" class="block w-full" :value="old('npm', $student?->npm)" required />
            <x-input-label for="full_name" value="Nama Lengkap" /><x-text-input id="full_name" name="full_name" class="block w-full" :value="old('full_name', $student?->full_name ?? Auth::user()->name)" required />
            <x-input-label for="student_email" value="Email Student" /><x-text-input id="student_email" name="student_email" type="email" class="block w-full" :value="old('student_email', $student?->student_email ?? Auth::user()->email)" required />
            <x-input-label for="phone" value="Nomor HP" /><x-text-input id="phone" name="phone" class="block w-full" :value="old('phone', $student?->phone)" required />
            <x-input-label for="study_program_id" value="Prodi" />
            <select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300" required>
                <option value="">Pilih prodi</option>
                @foreach ($studyPrograms as $program)
                    <option value="{{ $program->id }}" @selected((string) old('study_program_id', $student?->study_program_id) === (string) $program->id)>{{ $program->name }}</option>
                @endforeach
            </select>
            <x-primary-button>Simpan Profil</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
