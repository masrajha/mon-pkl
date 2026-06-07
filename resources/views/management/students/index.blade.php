<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Manajemen Mahasiswa') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.students.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg" x-data="{ accountMode: @js(old('account_mode', 'auto')) }">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Mahasiswa</h3>
                <x-input-label for="npm" value="NPM" /><x-text-input id="npm" name="npm" class="block w-full" required />
                <x-input-label for="full_name" value="Nama Lengkap" /><x-text-input id="full_name" name="full_name" class="block w-full" required />
                <x-input-label for="phone" value="No HP" /><x-text-input id="phone" name="phone" class="block w-full" :value="old('phone')" />
                <x-input-label for="study_program_id" value="Prodi" />
                <select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300"><option value="">Pilih prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select>
                <div class="rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                    <x-input-label for="account_mode" value="Pengaturan Akun Login" />
                    <select id="account_mode" name="account_mode" x-model="accountMode" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="auto">Buat/tautkan otomatis dari email</option>
                        <option value="link">Tautkan akun yang sudah ada</option>
                        <option value="none">Simpan tanpa akun login</option>
                    </select>
                    <div class="mt-3" x-show="accountMode === 'auto'">
                        <x-input-label for="login_email" value="Email Login" />
                        <x-text-input id="login_email" name="login_email" type="email" class="block w-full" :value="old('login_email')" />
                    </div>
                    <div class="mt-3" x-show="accountMode === 'link'">
                        <x-input-label for="user_id" value="Akun Login Mahasiswa" />
                        <select id="user_id" name="user_id" class="block w-full rounded-md border-gray-300"><option value="">Pilih akun</option>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>@endforeach</select>
                    </div>
                    <div class="mt-3" x-show="accountMode === 'none'">
                        <x-input-label for="student_email" value="Email Kontak" />
                        <x-text-input id="student_email" name="student_email" type="email" class="block w-full" :value="old('student_email')" />
                    </div>
                    <p class="mt-2 text-xs text-blue-700">Default: sistem membuat akun login role Mahasiswa. Email profil mengikuti email akun login.</p>
                </div>
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Mahasiswa" description="Cari, filter, dan urutkan data mahasiswa." search-placeholder="Cari NPM, nama, atau email...">
                    <x-slot name="filters">
                        <div>
                            <x-input-label for="filter_study_program_id" value="Prodi" />
                            <select id="filter_study_program_id" name="study_program_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua prodi</option>
                                @foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected($selectedStudyProgram === $program->id)>{{ $program->name }}</option>@endforeach
                            </select>
                        </div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell"><x-sortable-heading column="npm" label="NPM" /></th><th class="silat-table-cell"><x-sortable-heading column="full_name" label="Nama" /></th><th class="silat-table-cell">Prodi</th><th class="silat-table-cell">Akun</th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody>@foreach ($students as $student)<tr><td class="silat-table-cell font-medium text-gray-900">{{ $student->npm }}</td><td class="silat-table-cell">{{ $student->full_name }}</td><td class="silat-table-cell text-gray-600">{{ $student->studyProgram?->name ?: '-' }}</td><td class="silat-table-cell text-gray-600">{{ $student->user?->email ?: '-' }}</td><td class="silat-table-cell text-right"><a class="silat-secondary-link justify-end" href="{{ route('management.students.edit', $student) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><x-table-pagination :paginator="$students" /></div>
        </div>
    </div></div>
</x-app-layout>
