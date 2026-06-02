<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Permohonan Pindah Mitra') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('student.relocations.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf
            <div>
                <x-input-label for="internship_enrollment_id" value="Enrollment Aktif" />
                <select id="internship_enrollment_id" name="internship_enrollment_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="">Pilih enrollment</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}" @selected(old('internship_enrollment_id') == $enrollment->id)>
                            {{ $enrollment->internshipPeriod?->display_name }} - {{ $enrollment->studyProgram?->name }} - {{ $enrollment->internshipPlace?->name ?: 'Belum ditempatkan' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="new_internship_place_id" value="Mitra Tujuan" />
                <select id="new_internship_place_id" name="new_internship_place_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="">Pilih tempat tujuan</option>
                    @foreach ($places as $place)
                        <option value="{{ $place->id }}" @selected(old('new_internship_place_id') == $place->id)>{{ $place->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="reason" value="Alasan Pindah" />
                <textarea id="reason" name="reason" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('reason') }}</textarea>
            </div>
            <x-primary-button>Kirim Permohonan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
