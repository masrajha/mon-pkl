<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Viewer Laporan') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.report-viewers.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <div>
                    <h3 class="font-semibold text-gray-900">Tambah Viewer Laporan</h3>
                    <p class="mt-1 text-sm text-gray-500">Berikan akses Analisis & Laporan tanpa aksi operasional workflow.</p>
                </div>
                @include('management.report-viewers.partials.fields')
                <x-primary-button>Simpan</x-primary-button>
            </form>

            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Viewer Laporan" description="Cari dosen, organisasi, atau prodi." search-placeholder="Cari viewer...">
                    <x-slot name="filters">
                        <div>
                            <x-input-label for="filter_level" value="Level" />
                            <select id="filter_level" name="level" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua level</option>
                                @foreach ($levelLabels as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedLevel === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-3">
                            <x-input-label for="filter_status" value="Status" />
                            <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Semua status</option>
                                <option value="active" @selected($selectedStatus === 'active')>Aktif</option>
                                <option value="inactive" @selected($selectedStatus === 'inactive')>Nonaktif</option>
                            </select>
                        </div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Dosen</th>
                                <th class="silat-table-cell"><x-sortable-heading column="level" label="Level" /></th>
                                <th class="silat-table-cell">Scope</th>
                                <th class="silat-table-cell">Masa Berlaku</th>
                                <th class="silat-table-cell"><x-sortable-heading column="status" label="Status" /></th>
                                <th class="silat-table-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td class="silat-table-cell">
                                        <span class="font-medium text-gray-900">{{ $assignment->lecturer?->name ?: '-' }}</span>
                                        <div class="text-xs text-gray-500">{{ $assignment->lecturer?->nip ?: $assignment->lecturer?->email ?: '-' }}</div>
                                    </td>
                                    <td class="silat-table-cell">{{ $levelLabels[$assignment->level] ?? Str::headline($assignment->level) }}</td>
                                    <td class="silat-table-cell text-gray-600">
                                        {{ $assignment->level === 'study_program' ? ($assignment->studyProgram?->name ?: '-') : ($assignment->organization?->name ?: '-') }}
                                        @if ($assignment->organization?->parent)
                                            <div class="text-xs text-gray-500">Induk: {{ $assignment->organization->parent->name }}</div>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell text-gray-600">
                                        {{ $assignment->starts_at?->format('d/m/Y') ?: 'Sekarang' }}
                                        -
                                        {{ $assignment->ends_at?->format('d/m/Y') ?: 'Tidak dibatasi' }}
                                    </td>
                                    <td class="silat-table-cell">
                                        <x-badge :variant="$assignment->status === 'active' ? 'success' : 'neutral'">{{ $assignment->status === 'active' ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                    </td>
                                    <td class="silat-table-cell text-right">
                                        <a class="silat-secondary-link justify-end" href="{{ route('management.report-viewers.edit', $assignment) }}"><x-icon name="fa-pen-to-square" class="mr-1" /> Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada viewer laporan" icon="fa-chart-simple" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-table-pagination :paginator="$assignments" />
            </div>
        </div>
    </div></div>
</x-app-layout>
