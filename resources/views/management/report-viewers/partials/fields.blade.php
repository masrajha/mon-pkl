@php
    $assignment ??= null;
    $selectedLevel = old('level', $assignment?->level ?? 'department');
@endphp

<div x-data="{ level: @js($selectedLevel) }" class="space-y-4">
    <div>
        <x-input-label for="lecturer_id" value="Dosen Viewer" />
        <select id="lecturer_id" name="lecturer_id" class="mt-1 block w-full rounded-md border-gray-300" required>
            <option value="">Pilih dosen</option>
            @foreach ($lecturers as $lecturer)
                <option value="{{ $lecturer->id }}" @selected((string) old('lecturer_id', $assignment?->lecturer_id) === (string) $lecturer->id)>
                    {{ $lecturer->name }}{{ $lecturer->nip ? ' - '.$lecturer->nip : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <x-input-label for="level" value="Level Akses" />
            <select id="level" name="level" x-model="level" class="mt-1 block w-full rounded-md border-gray-300" required>
                @foreach ($levelLabels as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300" required>
                @foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $assignment?->status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div x-show="level !== 'study_program'">
        <x-input-label for="organization_id" value="Organisasi" />
        <select id="organization_id" name="organization_id" class="mt-1 block w-full rounded-md border-gray-300">
            <option value="">Pilih organisasi</option>
            @foreach ($organizations as $organization)
                <option
                    value="{{ $organization->id }}"
                    data-type="{{ $organization->type }}"
                    x-show="level === @js($organization->type)"
                    @selected((string) old('organization_id', $assignment?->organization_id) === (string) $organization->id)
                >
                    {{ $levelLabels[$organization->type] ?? Str::headline($organization->type) }} - {{ $organization->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Viewer melihat semua prodi di bawah organisasi yang dipilih.</p>
    </div>

    <div x-show="level === 'study_program'">
        <x-input-label for="study_program_id" value="Program Studi" />
        <select id="study_program_id" name="study_program_id" class="mt-1 block w-full rounded-md border-gray-300">
            <option value="">Pilih prodi</option>
            @foreach ($studyPrograms as $program)
                <option value="{{ $program->id }}" @selected((string) old('study_program_id', $assignment?->study_program_id) === (string) $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <x-input-label for="starts_at" value="Mulai Berlaku" />
            <x-text-input id="starts_at" name="starts_at" type="date" class="mt-1 block w-full" :value="old('starts_at', $assignment?->starts_at?->toDateString())" />
        </div>
        <div>
            <x-input-label for="ends_at" value="Selesai Berlaku" />
            <x-text-input id="ends_at" name="ends_at" type="date" class="mt-1 block w-full" :value="old('ends_at', $assignment?->ends_at?->toDateString())" />
        </div>
    </div>
</div>
