<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Edit Program Kegiatan') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('management.programs.update', $program) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf @method('PATCH')
            <x-input-label for="code" value="Kode" /><x-text-input id="code" name="code" class="block w-full uppercase" :value="$program->code" required />
            <x-input-label for="name" value="Nama Program" /><x-text-input id="name" name="name" class="block w-full" :value="$program->name" required />
            <x-input-label for="rule_key" value="Rule Aktif" />
            <select id="rule_key" name="rule_key" class="block w-full rounded-md border-gray-300">
                <option value="kerja_praktik" @selected($program->rule_key === 'kerja_praktik')>Rule Kerja Praktik</option>
            </select>
            <p class="text-xs text-gray-500">Program lain sementara memakai rule Kerja Praktik. Kolom ini disiapkan agar rule dapat dipisah di masa datang.</p>
            <x-input-label for="description" value="Deskripsi" />
            <textarea id="description" name="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $program->description) }}</textarea>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($program->is_active) class="rounded border-gray-300"> Aktif</label>
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
