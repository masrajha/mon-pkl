<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Manajemen Periode Program') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.periods.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Periode Program</h3>
                <x-input-label for="program_id" value="Program Kegiatan" />
                <select id="program_id" name="program_id" class="block w-full rounded-md border-gray-300" required>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
                <x-input-label for="name" value="Nama Periode" /><x-text-input id="name" name="name" class="block w-full" required />
                <x-input-label for="academic_year" value="Tahun Akademik" /><x-text-input id="academic_year" name="academic_year" class="block w-full" />
                <x-input-label for="semester" value="Semester" /><x-text-input id="semester" name="semester" class="block w-full" />
                <x-input-label for="batch" value="Gelombang" /><x-text-input id="batch" name="batch" class="block w-full" />
                <div class="grid gap-3 sm:grid-cols-2"><div><x-input-label for="starts_at" value="Mulai" /><x-text-input id="starts_at" name="starts_at" type="date" class="block w-full" /></div><div><x-input-label for="ends_at" value="Selesai" /><x-text-input id="ends_at" name="ends_at" type="date" class="block w-full" /></div></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded border-gray-300"> Aktif</label>
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Periode Program" description="Cari periode, tahun akademik, semester, atau program." search-placeholder="Cari periode program...">
                    <x-slot name="filters">
                        <div>
                            <x-input-label for="filter_program_id" value="Program" />
                            <select id="filter_program_id" name="program_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua program</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected($selectedProgram === $program->id)>{{ $program->name }}</option>@endforeach</select>
                        </div>
                        <div class="mt-3">
                            <x-input-label for="filter_status" value="Status" />
                            <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua status</option><option value="active" @selected($selectedStatus === 'active')>Aktif</option><option value="inactive" @selected($selectedStatus === 'inactive')>Nonaktif</option><option value="locked" @selected($selectedStatus === 'locked')>Selesai/Terkunci</option></select>
                        </div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell"><x-sortable-heading column="name" label="Periode" /></th><th class="silat-table-cell">Program</th><th class="silat-table-cell"><x-sortable-heading column="academic_year" label="Akademik" /></th><th class="silat-table-cell"><x-sortable-heading column="starts_at" label="Tanggal" /></th><th class="silat-table-cell">Status</th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($periods as $period)
                            <tr>
                                <td class="silat-table-cell font-medium text-gray-900">{{ $period->display_name }}</td>
                                <td class="silat-table-cell">{{ $period->program?->name ?: 'Kerja Praktik' }}<div class="text-xs text-gray-500">{{ $period->program?->rule_key ?: 'kerja_praktik' }}</div></td>
                                <td class="silat-table-cell text-gray-600">{{ $period->academic_year }} / {{ $period->semester }}</td>
                                <td class="silat-table-cell text-gray-600">{{ $period->starts_at?->format('d/m/Y') ?: '-' }} - {{ $period->ends_at?->format('d/m/Y') ?: '-' }}</td>
                                <td class="silat-table-cell">
                                    <x-badge :variant="$period->is_locked ? 'neutral' : ($period->is_active ? 'success' : 'neutral')">
                                        {{ $period->is_locked ? 'Selesai' : ($period->is_active ? 'Aktif' : 'Nonaktif') }}
                                    </x-badge>
                                </td>
                                <td class="silat-table-cell">
                                    <div class="flex justify-end gap-3">
                                        <a class="silat-secondary-link" href="{{ route('management.periods.edit', $period) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a>
                                        @unless ($period->is_locked)
                                            <form method="POST" action="{{ route('management.periods.complete', $period) }}" onsubmit="return confirm('Set periode ini sebagai selesai? Semua peserta aktif pada periode ini akan diubah menjadi selesai.');">
                                                @csrf
                                                <button type="submit" class="silat-secondary-link text-amber-700 hover:text-amber-900"><x-icon name="fa-lock" class="mr-1" /> Set Selesai</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div><x-table-pagination :paginator="$periods" /></div>
        </div>
    </div></div>
</x-app-layout>
