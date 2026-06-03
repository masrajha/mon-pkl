@php
    $isCreate = blank($coordinator ?? null);
    $selectedStudyProgramIds = collect(old('study_program_ids', []))->map(fn ($id) => (string) $id)->all();
@endphp

<x-input-label for="lecturer_id" value="Dosen Koordinator" />
<select id="lecturer_id" name="lecturer_id" class="block w-full rounded-md border-gray-300" required>
    <option value="">Pilih dosen</option>
    @foreach ($lecturers as $lecturer)
        <option value="{{ $lecturer->id }}" @selected((string) old('lecturer_id', $coordinator?->lecturer_id) === (string) $lecturer->id)>{{ $lecturer->name }}{{ $lecturer->nip ? ' - '.$lecturer->nip : '' }}</option>
    @endforeach
</select>

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <x-input-label for="internship_period_id" value="Periode Program" />
        <select id="internship_period_id" name="internship_period_id" class="block w-full rounded-md border-gray-300" required>
            <option value="">Pilih periode program</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected((string) old('internship_period_id', $coordinator?->internship_period_id) === (string) $period->id)>{{ $period->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        @if ($isCreate)
            <x-input-label for="study_program_ids" value="Prodi" />
            <select id="study_program_ids" name="study_program_ids[]" class="block h-28 w-full rounded-md border-gray-300 text-sm leading-5 focus:border-blue-500 focus:ring-blue-500 [&_option:checked]:bg-blue-600 [&_option:checked]:text-white" multiple required>
                @foreach ($studyPrograms as $program)
                    <option class="px-2 py-1 text-sm" value="{{ $program->id }}" @selected(in_array((string) $program->id, $selectedStudyProgramIds, true))>{{ $program->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Tahan Ctrl/Command untuk memilih lebih dari satu prodi.</p>
        @else
            <x-input-label for="study_program_id" value="Prodi" />
            <select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300" required>
                <option value="">Pilih prodi</option>
                @foreach ($studyPrograms as $program)
                    <option value="{{ $program->id }}" @selected((string) old('study_program_id', $coordinator?->study_program_id) === (string) $program->id)>{{ $program->name }}</option>
                @endforeach
            </select>
        @endif
    </div>
</div>

<x-input-label for="status" value="Status" />
<select id="status" name="status" class="block w-full rounded-md border-gray-300" required>
    @foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)
        <option value="{{ $value }}" @selected(old('status', $coordinator?->status ?? 'active') === $value)>{{ $label }}</option>
    @endforeach
</select>
