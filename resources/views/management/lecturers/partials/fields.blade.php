@php($accountMode = old('account_mode', blank($lecturer ?? null) || ($lecturer?->user_id ?? null) ? 'auto' : 'none'))

<div x-data="{ accountMode: @js($accountMode) }" class="space-y-4">
<x-input-label for="name" value="Nama Dosen" />
<x-text-input id="name" name="name" class="block w-full" :value="old('name', $lecturer?->name)" required />

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <x-input-label for="nip" value="NIP" />
        <x-text-input id="nip" name="nip" class="block w-full" :value="old('nip', $lecturer?->nip)" />
    </div>
    <div>
        <x-input-label for="nidn" value="NIDN" />
        <x-text-input id="nidn" name="nidn" class="block w-full" :value="old('nidn', $lecturer?->nidn)" />
    </div>
</div>

<x-input-label for="study_program_id" value="Prodi" />
<select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300">
    <option value="">Belum ditentukan</option>
    @foreach ($studyPrograms as $program)
        <option value="{{ $program->id }}" @selected((string) old('study_program_id', $lecturer?->study_program_id) === (string) $program->id)>{{ $program->name }}</option>
    @endforeach
</select>

<div class="rounded-lg border border-blue-100 bg-blue-50/60 p-4">
    <x-input-label for="account_mode" value="Pengaturan Akun Login" />
    <select id="account_mode" name="account_mode" x-model="accountMode" class="mt-1 block w-full rounded-md border-gray-300">
        <option value="auto">Buat/tautkan otomatis dari email</option>
        <option value="link">Tautkan akun yang sudah ada</option>
        <option value="none">Simpan tanpa akun login</option>
    </select>
    <div class="mt-3" x-show="accountMode === 'auto'">
        <x-input-label for="login_email" value="Email Login" />
        <x-text-input id="login_email" name="login_email" type="email" class="block w-full" :value="old('login_email', $lecturer?->user?->email ?: $lecturer?->email)" />
    </div>
    <div class="mt-3" x-show="accountMode === 'link'">
        <x-input-label for="user_id" value="Akun Login Dosen" />
        <select id="user_id" name="user_id" class="block w-full rounded-md border-gray-300">
            <option value="">Pilih akun</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $lecturer?->user_id) === (string) $user->id)>{{ $user->name }} - {{ $user->email }}</option>
            @endforeach
        </select>
    </div>
    <div class="mt-3" x-show="accountMode === 'none'">
        <x-input-label for="email" value="Email Kontak" />
        <x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email', $lecturer?->email)" />
    </div>
    <p class="mt-2 text-xs text-blue-700">Default: sistem membuat akun login role Dosen. Email profil mengikuti email akun login.</p>
</div>

<x-input-label for="status" value="Status" />
<select id="status" name="status" class="block w-full rounded-md border-gray-300" required>
    @foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)
        <option value="{{ $value }}" @selected(old('status', $lecturer?->status ?? 'active') === $value)>{{ $label }}</option>
    @endforeach
</select>
</div>
