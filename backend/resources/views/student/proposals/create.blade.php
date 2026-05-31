<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Usulan Tempat PKL Baru') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('student.proposals.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <div><x-input-label for="internship_period_id" value="Periode" /><select id="internship_period_id" name="internship_period_id" class="block w-full rounded-md border-gray-300" required><option value="">Pilih periode</option>@foreach ($periods as $period)<option value="{{ $period->id }}" @selected(old('internship_period_id') == $period->id)>{{ $period->name }}</option>@endforeach</select></div>
                <div><x-input-label for="study_program_id" value="Prodi" /><select id="study_program_id" name="study_program_id" class="block w-full rounded-md border-gray-300" required><option value="">Pilih prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected(old('study_program_id') == $program->id)>{{ $program->name }}</option>@endforeach</select></div>
            </div>
            <x-input-label for="name" value="Nama Instansi" /><x-text-input id="name" name="name" class="block w-full" :value="old('name')" required />
            <x-input-label for="address" value="Alamat" /><textarea id="address" name="address" rows="3" class="block w-full rounded-md border-gray-300">{{ old('address') }}</textarea>
            <x-input-label for="city_name" value="Kab/Kota" /><x-text-input id="city_name" name="city_name" class="block w-full" :value="old('city_name')" />
            <div class="grid gap-3 sm:grid-cols-2"><div><x-input-label for="latitude" value="Latitude" /><x-text-input id="latitude" name="latitude" class="block w-full" :value="old('latitude')" /></div><div><x-input-label for="longitude" value="Longitude" /><x-text-input id="longitude" name="longitude" class="block w-full" :value="old('longitude')" /></div></div>
            <x-input-label for="field_supervisor_name" value="Pembimbing Lapangan Jika Sudah Diketahui" /><x-text-input id="field_supervisor_name" name="field_supervisor_name" class="block w-full" :value="old('field_supervisor_name')" />
            <x-input-label for="field_supervisor_phone" value="HP Pembimbing Lapangan" /><x-text-input id="field_supervisor_phone" name="field_supervisor_phone" class="block w-full" :value="old('field_supervisor_phone')" />
            <x-primary-button>Kirim Usulan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
