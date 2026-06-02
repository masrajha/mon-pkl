<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Edit Periode Program') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('management.periods.update', $period) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf @method('PATCH')
            <x-input-label for="program_id" value="Program Kegiatan" />
            <select id="program_id" name="program_id" class="block w-full rounded-md border-gray-300" required>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" @selected(old('program_id', $period->program_id) == $program->id)>{{ $program->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500">Rule yang berlaku mengikuti program. Pada fase ini semua program memakai Rule Kerja Praktik.</p>
            <x-input-label for="name" value="Nama Periode" /><x-text-input id="name" name="name" class="block w-full" :value="$period->name" required />
            <x-input-label for="academic_year" value="Tahun Akademik" /><x-text-input id="academic_year" name="academic_year" class="block w-full" :value="$period->academic_year" />
            <x-input-label for="semester" value="Semester" /><x-text-input id="semester" name="semester" class="block w-full" :value="$period->semester" />
            <x-input-label for="batch" value="Gelombang" /><x-text-input id="batch" name="batch" class="block w-full" :value="$period->batch" />
            <div class="grid gap-3 sm:grid-cols-2"><div><x-input-label for="starts_at" value="Mulai" /><x-text-input id="starts_at" name="starts_at" type="date" class="block w-full" :value="$period->starts_at?->toDateString()" /></div><div><x-input-label for="ends_at" value="Selesai" /><x-text-input id="ends_at" name="ends_at" type="date" class="block w-full" :value="$period->ends_at?->toDateString()" /></div></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($period->is_active) class="rounded border-gray-300"> Aktif</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_locked" value="1" @checked($period->is_locked) class="rounded border-gray-300"> Terkunci</label>
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
