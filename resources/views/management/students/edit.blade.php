<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Edit Mahasiswa') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('management.students.update', $student) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg" x-data="{ accountMode: @js(old('account_mode', $student->user_id ? 'auto' : 'none')) }">
            @csrf @method('PATCH')
            <x-input-label for="npm" value="NPM" /><x-text-input id="npm" name="npm" class="block w-full" :value="$student->npm" required />
            <x-input-label for="full_name" value="Nama Lengkap" /><x-text-input id="full_name" name="full_name" class="block w-full" :value="$student->full_name" required />
            <x-input-label for="phone" value="No HP" /><x-text-input id="phone" name="phone" class="block w-full" :value="old('phone', $student->phone)" />
            <x-input-label for="study_program_id" value="Prodi" />
            <select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300"><option value="">Pilih prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected($student->study_program_id === $program->id)>{{ $program->name }}</option>@endforeach</select>
            <div class="rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                <x-input-label for="account_mode" value="Pengaturan Akun Login" />
                <select id="account_mode" name="account_mode" x-model="accountMode" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="auto">Buat/tautkan otomatis dari email</option>
                    <option value="link">Tautkan akun yang sudah ada</option>
                    <option value="none">Simpan tanpa akun login</option>
                </select>
                <div class="mt-3" x-show="accountMode === 'auto'">
                    <x-input-label for="login_email" value="Email Login" />
                    <x-text-input id="login_email" name="login_email" type="email" class="block w-full" :value="old('login_email', $student->user?->email)" />
                </div>
                <div class="mt-3" x-show="accountMode === 'link'">
                    <x-input-label for="user_id" value="Akun Login Mahasiswa" />
                    <select id="user_id" name="user_id" class="block w-full rounded-md border-gray-300"><option value="">Pilih akun</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected($student->user_id === $user->id)>{{ $user->name }} - {{ $user->email }}</option>@endforeach</select>
                </div>
                <div class="mt-3" x-show="accountMode === 'none'">
                    <x-input-label for="student_email" value="Email Kontak" />
                    <x-text-input id="student_email" name="student_email" type="email" class="block w-full" :value="old('student_email', $student->student_email)" />
                </div>
                <p class="mt-2 text-xs text-blue-700">Jika akun login dibuat atau ditautkan, email profil mengikuti email akun login.</p>
            </div>
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
