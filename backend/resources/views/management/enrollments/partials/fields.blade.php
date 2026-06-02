<x-input-label for="student_id" value="Mahasiswa" />
<select id="student_id" name="student_id" class="block w-full rounded-md border-gray-300" required>
    <option value="">Pilih mahasiswa</option>
    @foreach ($students as $student)
        <option value="{{ $student->id }}" @selected(old('student_id', $enrollment?->student_id) === $student->id)>{{ $student->full_name }} - {{ $student->npm }}</option>
    @endforeach
</select>

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <x-input-label for="internship_period_id" value="Periode Program" />
        <select id="internship_period_id" name="internship_period_id" class="block w-full rounded-md border-gray-300" required>
            <option value="">Pilih periode program</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected(old('internship_period_id', $enrollment?->internship_period_id) === $period->id)>{{ $period->display_name }}</option>
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

<x-input-label for="internship_place_id" value="Mitra / Tempat Kegiatan" />
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

<div class="rounded-md border border-gray-200 bg-gray-50 p-4">
    <h4 class="text-sm font-semibold text-gray-900">Kelayakan Akademik</h4>
    <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="has_krs_pkl" value="1" @checked(old('has_krs_pkl', $enrollment?->has_krs_pkl)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        <span>Sudah mengambil Kerja Praktik atau program terkait di KRS</span>
    </label>
    <div class="mt-3 grid gap-3 sm:grid-cols-3">
        <div><x-input-label for="total_sks" value="Total SKS" /><x-text-input id="total_sks" name="total_sks" type="number" class="block w-full" :value="old('total_sks', $enrollment?->total_sks)" /></div>
        <div><x-input-label for="current_semester" value="Semester" /><x-text-input id="current_semester" name="current_semester" type="number" class="block w-full" :value="old('current_semester', $enrollment?->current_semester)" /></div>
        <div><x-input-label for="gpa" value="IPK" /><x-text-input id="gpa" name="gpa" type="number" step="0.01" class="block w-full" :value="old('gpa', $enrollment?->gpa)" /></div>
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

<x-input-label for="admin_note" value="Catatan Verifikasi / Revisi" />
<textarea id="admin_note" name="admin_note" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('admin_note', $enrollment?->admin_note) }}</textarea>
