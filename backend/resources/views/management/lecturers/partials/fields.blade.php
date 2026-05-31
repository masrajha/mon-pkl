<x-input-label for="name" value="Nama Dosen" />
<x-text-input id="name" name="name" class="block w-full" :value="old('name', $lecturer?->name)" required />

<x-input-label for="email" value="Email" />
<x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email', $lecturer?->email)" />

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

<x-input-label for="user_id" value="Akun Login Dosen" />
<select id="user_id" name="user_id" class="block w-full rounded-md border-gray-300">
    <option value="">Tanpa akun login</option>
    @foreach ($users as $user)
        <option value="{{ $user->id }}" @selected((string) old('user_id', $lecturer?->user_id) === (string) $user->id)>{{ $user->name }} - {{ $user->email }}</option>
    @endforeach
</select>

<x-input-label for="status" value="Status" />
<select id="status" name="status" class="block w-full rounded-md border-gray-300" required>
    @foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)
        <option value="{{ $value }}" @selected(old('status', $lecturer?->status ?? 'active') === $value)>{{ $label }}</option>
    @endforeach
</select>
