<x-input-label for="lecturer_id" value="Dosen Koordinator" />
<select id="lecturer_id" name="lecturer_id" class="block w-full rounded-md border-gray-300" required>
    <option value="">Pilih dosen</option>
    @foreach ($lecturers as $lecturer)
        <option value="{{ $lecturer->id }}" @selected((string) old('lecturer_id', $coordinator?->lecturer_id) === (string) $lecturer->id)>{{ $lecturer->name }}{{ $lecturer->nip ? ' - '.$lecturer->nip : '' }}</option>
    @endforeach
</select>

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <x-input-label for="internship_period_id" value="Periode PKL" />
        <select id="internship_period_id" name="internship_period_id" class="block w-full rounded-md border-gray-300" required>
            <option value="">Pilih periode</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected((string) old('internship_period_id', $coordinator?->internship_period_id) === (string) $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="study_program_id" value="Prodi" />
        <select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300" required>
            <option value="">Pilih prodi</option>
            @foreach ($studyPrograms as $program)
                <option value="{{ $program->id }}" @selected((string) old('study_program_id', $coordinator?->study_program_id) === (string) $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<x-input-label for="status" value="Status" />
<select id="status" name="status" class="block w-full rounded-md border-gray-300" required>
    @foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)
        <option value="{{ $value }}" @selected(old('status', $coordinator?->status ?? 'active') === $value)>{{ $label }}</option>
    @endforeach
</select>
