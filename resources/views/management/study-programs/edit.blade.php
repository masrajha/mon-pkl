<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Edit Prodi') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('management.study-programs.update', $studyProgram) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf @method('PATCH')
            <x-input-label for="code" value="Kode" /><x-text-input id="code" name="code" class="block w-full" :value="$studyProgram->code" required />
            <x-input-label for="name" value="Nama" /><x-text-input id="name" name="name" class="block w-full" :value="$studyProgram->name" required />
            <x-input-label for="degree_level" value="Jenjang" />
            <select id="degree_level" name="degree_level" class="block w-full rounded-md border-gray-300" required>
                @foreach (['D3', 'S1', 'S2'] as $degreeLevel)
                    <option value="{{ $degreeLevel }}" @selected(old('degree_level', $studyProgram->degree_level) === $degreeLevel)>{{ $degreeLevel }}</option>
                @endforeach
            </select>
            <x-input-label for="organization_id" value="Jurusan" />
            <select id="organization_id" name="organization_id" class="block w-full rounded-md border-gray-300" required>
                <option value="">Pilih jurusan</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('organization_id', $studyProgram->organization_id) === (string) $department->id)>{{ $department->name }}{{ $department->parent?->name ? ' - '.$department->parent->name : '' }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($studyProgram->is_active) class="rounded border-gray-300"> Aktif</label>
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
