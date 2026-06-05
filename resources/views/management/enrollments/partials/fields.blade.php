@php
    $isEdit = filled($enrollment);
    $currentStudent = $enrollment?->student ?? $selectedStudent;
    $currentStudyProgram = $currentStudent?->studyProgram ?? $enrollment?->studyProgram;
    $studentLabel = $currentStudent ? trim($currentStudent->full_name.' - '.$currentStudent->npm) : '';
    $periodValue = old('internship_period_id', $enrollment?->internship_period_id ?? request('period_id'));
@endphp

<div>
    <x-input-label for="student_search" value="Mahasiswa" />
    @if ($isEdit)
        <input type="hidden" name="student_id" value="{{ $enrollment->student_id }}">
        <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800">
            <span class="font-medium">{{ $studentLabel ?: '-' }}</span>
        </div>
    @else
        <input id="student_id" type="hidden" name="student_id" value="{{ old('student_id', $currentStudent?->id) }}" required>
        <div class="relative mt-1" data-student-search data-search-url="{{ route('management.enrollments.students.search') }}">
            <x-text-input id="student_search" type="search" class="block w-full" value="{{ $studentLabel }}" autocomplete="off" placeholder="Cari nama atau NPM mahasiswa" required />
            <div data-student-suggestions class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"></div>
        </div>
    @endif
</div>

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <x-input-label for="internship_period_id" value="Periode Program" />
        <select id="internship_period_id" name="internship_period_id" class="block w-full rounded-md border-gray-300" required>
            <option value="">Pilih periode program</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected((string) $periodValue === (string) $period->id)>{{ $period->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label value="Prodi" />
        <div data-student-study-program class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800">
            {{ $currentStudyProgram?->name ?: 'Ikut prodi mahasiswa' }}
        </div>
    </div>
</div>

<x-input-label for="internship_place_id" value="Mitra" />
<select id="internship_place_id" name="internship_place_id" class="block w-full rounded-md border-gray-300">
    <option value="">Mitra belum ditentukan</option>
    @foreach ($places as $place)
        <option value="{{ $place->id }}" @selected((string) old('internship_place_id', $enrollment?->internship_place_id) === (string) $place->id)>{{ $place->name }}</option>
    @endforeach
</select>

<div class="rounded-md border border-blue-100 bg-blue-50 p-4">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h4 class="text-sm font-semibold text-gray-900">Periode Presensi Peserta</h4>
            <p class="mt-1 text-xs text-gray-600">Kosongkan tanggal khusus untuk mengikuti Mulai/Selesai Pelaksanaan dari Periode Program.</p>
        </div>
        @if ($enrollment?->hasAttendanceOverride())
            <x-badge variant="info">Khusus</x-badge>
        @endif
    </div>
    <div class="mt-3 grid gap-3 sm:grid-cols-2">
        <div>
            <x-input-label for="attendance_starts_at" value="Mulai Presensi Khusus" />
            <x-text-input id="attendance_starts_at" name="attendance_starts_at" type="date" class="block w-full" :value="old('attendance_starts_at', $enrollment?->attendance_starts_at?->toDateString())" />
        </div>
        <div>
            <x-input-label for="attendance_ends_at" value="Selesai Presensi Khusus" />
            <x-text-input id="attendance_ends_at" name="attendance_ends_at" type="date" class="block w-full" :value="old('attendance_ends_at', $enrollment?->attendance_ends_at?->toDateString())" />
        </div>
    </div>
</div>

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
        <x-input-label for="field_supervisor_email" value="Email Pembimbing Lapangan" />
        <x-text-input id="field_supervisor_email" name="field_supervisor_email" type="email" class="block w-full" :value="old('field_supervisor_email', $enrollment?->field_supervisor_email)" />
    </div>
</div>

<div>
    <x-input-label for="contact_student_phone" value="HP Kontak Mahasiswa Periode Ini" />
    <x-text-input id="contact_student_phone" name="contact_student_phone" class="block w-full" :value="old('contact_student_phone', $enrollment?->contact_student_phone)" />
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

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-student-search]').forEach((root) => {
                const input = root.querySelector('#student_search');
                const hidden = document.getElementById('student_id');
                const suggestions = root.querySelector('[data-student-suggestions]');
                const studyProgram = document.querySelector('[data-student-study-program]');
                let timeout;

                const clearPick = () => {
                    hidden.value = '';
                    studyProgram.textContent = 'Ikut prodi mahasiswa';
                };

                const render = (students) => {
                    suggestions.innerHTML = '';
                    if (! students.length) {
                        suggestions.classList.add('hidden');
                        return;
                    }

                    students.forEach((student) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-gray-50 focus:bg-gray-50';
                        const name = document.createElement('span');
                        name.className = 'font-medium text-gray-900';
                        name.textContent = student.full_name;
                        const npm = document.createElement('span');
                        npm.className = 'ml-2 text-gray-500';
                        npm.textContent = student.npm;
                        const program = document.createElement('div');
                        program.className = 'text-xs text-gray-500';
                        program.textContent = student.study_program_name || 'Prodi belum diisi';
                        button.append(name, npm, program);
                        button.addEventListener('click', () => {
                            hidden.value = student.id;
                            input.value = student.label;
                            studyProgram.textContent = student.study_program_name || 'Prodi belum diisi';
                            suggestions.classList.add('hidden');
                        });
                        suggestions.appendChild(button);
                    });
                    suggestions.classList.remove('hidden');
                };

                input.addEventListener('input', () => {
                    clearTimeout(timeout);
                    clearPick();
                    const query = input.value.trim();

                    if (query.length < 2) {
                        suggestions.classList.add('hidden');
                        return;
                    }

                    timeout = setTimeout(async () => {
                        const response = await fetch(`${root.dataset.searchUrl}?q=${encodeURIComponent(query)}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        render(response.ok ? await response.json() : []);
                    }, 250);
                });

                document.addEventListener('click', (event) => {
                    if (! root.contains(event.target)) {
                        suggestions.classList.add('hidden');
                    }
                });
            });
        });
    </script>
@endonce
