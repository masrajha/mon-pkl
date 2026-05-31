<x-input-label for="student_id" value="Mahasiswa" />
<select id="student_id" name="student_id" class="block w-full rounded-md border-gray-300" required>
    <option value="">Pilih mahasiswa</option>
    @foreach ($students as $student)
        <option value="{{ $student->id }}" @selected(old('student_id', $enrollment?->student_id) === $student->id)>{{ $student->full_name }} - {{ $student->npm }}</option>
    @endforeach
</select>

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <x-input-label for="internship_period_id" value="Periode" />
        <select id="internship_period_id" name="internship_period_id" class="block w-full rounded-md border-gray-300" required>
            <option value="">Pilih periode</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected(old('internship_period_id', $enrollment?->internship_period_id) === $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="study_program_id" value="Prodi" />
        <select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300" required>
            <option value="">Pilih prodi</option>
            @foreach ($studyPrograms as $program)
                <option value="{{ $program->id }}" @selected(old('study_program_id', $enrollment?->study_program_id) === $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<x-input-label for="internship_place_id" value="Master Tempat PKL" />
<select id="internship_place_id" name="internship_place_id" class="block w-full rounded-md border-gray-300">
    <option value="">Belum ditempatkan</option>
    @foreach ($places as $place)
        <option value="{{ $place->id }}" @selected(old('internship_place_id', $enrollment?->internship_place_id) === $place->id)>{{ $place->name }}</option>
    @endforeach
</select>

<x-input-label for="lecturer_supervisor_id" value="Dosen Pembimbing" />
<select id="lecturer_supervisor_id" name="lecturer_supervisor_id" class="block w-full rounded-md border-gray-300">
    <option value="">Belum ditentukan</option>
    @foreach ($lecturers as $lecturer)
        <option value="{{ $lecturer->id }}" @selected((string) old('lecturer_supervisor_id', $enrollment?->lecturer_supervisor_id) === (string) $lecturer->id)>{{ $lecturer->name }}{{ $lecturer->nidn ? ' - NIDN '.$lecturer->nidn : '' }}</option>
    @endforeach
</select>
@if ($enrollment?->lecturer_supervisor && ! $enrollment?->lecturer_supervisor_id)
    <p class="text-xs text-amber-700">Data legacy: {{ $enrollment->lecturer_supervisor }}. Pilih dosen dari daftar untuk merapikan relasi.</p>
@endif

<x-input-label for="field_supervisor" value="Pembimbing Lapangan Periode Ini" />
<x-text-input id="field_supervisor" name="field_supervisor" class="block w-full" :value="old('field_supervisor', $enrollment?->field_supervisor)" />

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <x-input-label for="field_supervisor_phone" value="HP Pembimbing Lapangan" />
        <x-text-input id="field_supervisor_phone" name="field_supervisor_phone" class="block w-full" :value="old('field_supervisor_phone', $enrollment?->field_supervisor_phone)" />
    </div>
    <div>
        <x-input-label for="contact_student_phone" value="HP Kontak Mahasiswa Periode Ini" />
        <x-text-input id="contact_student_phone" name="contact_student_phone" class="block w-full" :value="old('contact_student_phone', $enrollment?->contact_student_phone)" />
    </div>
</div>

<x-input-label for="status" value="Status" />
<select id="status" name="status" class="block w-full rounded-md border-gray-300" required>
    @foreach ([
        'draft' => 'Draft',
        'pending_verification' => 'Menunggu Verifikasi',
        'revision_required' => 'Perlu Revisi',
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'completed' => 'Selesai',
        'cancelled' => 'Batal',
        'rejected' => 'Ditolak',
    ] as $value => $label)
        <option value="{{ $value }}" @selected(old('status', $enrollment?->status ?? 'pending_verification') === $value)>{{ $label }}</option>
    @endforeach
</select>
