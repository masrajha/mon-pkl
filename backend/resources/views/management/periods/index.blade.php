<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Manajemen Periode PKL') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.periods.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Periode</h3>
                <x-input-label for="name" value="Nama Periode" /><x-text-input id="name" name="name" class="block w-full" required />
                <x-input-label for="academic_year" value="Tahun Akademik" /><x-text-input id="academic_year" name="academic_year" class="block w-full" />
                <x-input-label for="semester" value="Semester" /><x-text-input id="semester" name="semester" class="block w-full" />
                <x-input-label for="batch" value="Gelombang" /><x-text-input id="batch" name="batch" class="block w-full" />
                <div class="grid gap-3 sm:grid-cols-2"><div><x-input-label for="starts_at" value="Mulai" /><x-text-input id="starts_at" name="starts_at" type="date" class="block w-full" /></div><div><x-input-label for="ends_at" value="Selesai" /><x-text-input id="ends_at" name="ends_at" type="date" class="block w-full" /></div></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded border-gray-300"> Aktif</label>
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="bg-white shadow-sm sm:rounded-lg"><div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Akademik</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Status</th><th></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">@foreach ($periods as $period)<tr><td class="px-4 py-3">{{ $period->name }}</td><td class="px-4 py-3">{{ $period->academic_year }} / {{ $period->semester }}</td><td class="px-4 py-3">{{ $period->starts_at?->format('d/m/Y') ?: '-' }} - {{ $period->ends_at?->format('d/m/Y') ?: '-' }}</td><td class="px-4 py-3">{{ $period->is_active ? 'Aktif' : 'Nonaktif' }}{{ $period->is_locked ? ' / Terkunci' : '' }}</td><td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('management.periods.edit', $period) }}">Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><div class="p-4">{{ $periods->links() }}</div></div>
        </div>
    </div></div>
</x-app-layout>
