<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Koordinator Program') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.coordinators.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Koordinator</h3>
                @include('management.coordinators.partials.fields', ['coordinator' => null])
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Koordinator" description="Cari dosen, periode, atau prodi." search-placeholder="Cari koordinator...">
                    <x-slot name="filters">
                        <div>
                            <x-input-label for="filter_period_id" value="Periode Program" />
                            <select id="filter_period_id" name="period_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua periode</option>@foreach ($periods as $period)<option value="{{ $period->id }}" @selected($selectedPeriod === $period->id)>{{ $period->display_name }}</option>@endforeach</select>
                        </div>
                        <div class="mt-3">
                            <x-input-label for="filter_study_program_id" value="Prodi" />
                            <select id="filter_study_program_id" name="study_program_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected($selectedStudyProgram === $program->id)>{{ $program->name }}</option>@endforeach</select>
                        </div>
                        <div class="mt-3">
                            <x-input-label for="filter_status" value="Status" />
                            <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua status</option><option value="active" @selected($selectedStatus === 'active')>Aktif</option><option value="inactive" @selected($selectedStatus === 'inactive')>Nonaktif</option></select>
                        </div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell">Dosen</th><th class="silat-table-cell">Periode Program</th><th class="silat-table-cell">Prodi</th><th class="silat-table-cell"><x-sortable-heading column="status" label="Status" /></th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody>@foreach ($coordinators as $coordinator)<tr><td class="silat-table-cell"><span class="font-medium text-gray-900">{{ $coordinator->lecturer?->name }}</span><div class="text-xs text-gray-500">{{ $coordinator->lecturer?->nip ?: '-' }}</div></td><td class="silat-table-cell">{{ $coordinator->internshipPeriod?->display_name }}</td><td class="silat-table-cell text-gray-600">{{ $coordinator->studyProgram?->name }}</td><td class="silat-table-cell"><x-badge :variant="$coordinator->status === 'active' ? 'success' : 'neutral'">{{ $coordinator->status === 'active' ? 'Aktif' : 'Nonaktif' }}</x-badge></td><td class="silat-table-cell text-right"><a class="silat-secondary-link justify-end" href="{{ route('management.coordinators.edit', $coordinator) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><x-table-pagination :paginator="$coordinators" /></div>
        </div>
    </div></div>
</x-app-layout>
