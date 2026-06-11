<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800">{{ __('Edit Organisasi') }}</h2>
            <a href="{{ route('management.organizations.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">Kembali</a>
        </div>
    </x-slot>

    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('management.organizations.update', $organization) }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf
            @method('PATCH')

            <div>
                <x-input-label for="code" value="Kode" />
                <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $organization->code)" required />
            </div>

            <div>
                <x-input-label for="name" value="Nama Organisasi" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $organization->name)" required />
            </div>

            <div>
                <x-input-label for="type" value="Jenis" />
                <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300" required data-organization-type>
                    @foreach ($types as $type => $label)
                        <option value="{{ $type }}" @selected(old('type', $organization->type) === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="parent_id" value="Parent Organisasi" />
                <select id="parent_id" name="parent_id" class="mt-1 block w-full rounded-md border-gray-300" data-organization-parent>
                    <option value="" data-parent-for="university">Tanpa parent</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" data-parent-for="{{ $parent->type === 'university' ? 'faculty' : 'department' }}" @selected((string) old('parent_id', $organization->parent_id) === (string) $parent->id)>{{ $types[$parent->type] ?? $parent->type }} - {{ $parent->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Fakultas berada di bawah universitas, jurusan berada di bawah fakultas.</p>
            </div>

            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $organization->is_active)) class="rounded border-gray-300"> Aktif</label>
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div></div>
    @include('management.organizations.partials.parent-filter-script')
</x-app-layout>
