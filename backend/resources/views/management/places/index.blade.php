<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Master Tempat PKL') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form class="flex gap-2"><x-text-input name="q" class="w-72" :value="request('q')" placeholder="Cari tempat PKL" /><x-primary-button>Cari</x-primary-button></form>
            <a class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white" href="{{ route('internship-places.create') }}">Tambah Tempat PKL</a>
        </div>
        <form method="POST" action="{{ route('management.places.bulk') }}" class="bg-white shadow-sm sm:rounded-lg">
            @csrf
            <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-end">
                <div>
                    <x-input-label for="action" value="Bulk action" />
                    <select id="action" name="action" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="delete">Hapus yang peserta 0</option>
                        <option value="merge">Merge ke tempat tujuan</option>
                    </select>
                </div>
                <div class="min-w-80 flex-1">
                    <x-input-label for="target_place_id" value="Tujuan merge" />
                    <select id="target_place_id" name="target_place_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option value="">Pilih salah satu tempat yang dicentang</option>
                        @foreach ($allPlaces as $target)
                            <option value="{{ $target->id }}">{{ $target->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Jalankan</x-primary-button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3"><input type="checkbox" class="rounded border-gray-300" onclick="document.querySelectorAll('[data-place-checkbox]').forEach((el) => el.checked = this.checked)"></th><th class="px-4 py-3">Instansi</th><th class="px-4 py-3">Kota</th><th class="px-4 py-3">Koordinat</th><th class="px-4 py-3">Peserta</th><th></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">@foreach ($places as $place)<tr><td class="px-4 py-3"><input data-place-checkbox type="checkbox" name="place_ids[]" value="{{ $place->id }}" class="rounded border-gray-300"></td><td class="px-4 py-3"><div class="font-medium">{{ $place->name }}</div><div class="text-xs text-gray-500">{{ $place->address }}</div></td><td class="px-4 py-3">{{ $place->city?->name ?: '-' }}</td><td class="px-4 py-3">{{ $place->latitude && $place->longitude ? $place->latitude.', '.$place->longitude : '-' }}</td><td class="px-4 py-3">{{ $place->enrollments_count }}</td><td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('internship-places.edit', $place) }}">Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div>
            <div class="p-4">{{ $places->links() }}</div>
        </form>
    </div></div>
</x-app-layout>
