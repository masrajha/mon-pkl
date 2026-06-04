<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Validasi Usulan Mitra') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

        <div class="silat-card overflow-hidden">
            <x-table-controls title="Daftar Usulan Mitra" description="Cari usulan berdasarkan nama mitra, alamat, kota, atau mahasiswa." search-placeholder="Cari usulan mitra...">
                <x-slot name="filters">
                    <div>
                        <x-input-label for="filter_status" value="Status" />
                        <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">Semua status</option>
                            @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'merged' => 'Digabungkan', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'] as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-slot>
            </x-table-controls>
            <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head">
                        <tr>
                            <th class="silat-table-cell">Mahasiswa</th>
                            <th class="silat-table-cell">Periode/Prodi</th>
                            <th class="silat-table-cell"><x-sortable-heading column="name" label="Mitra Usulan" /></th>
                            <th class="silat-table-cell">Lokasi Mitra</th>
                            <th class="silat-table-cell"><x-sortable-heading column="status" label="Status" /></th>
                            <th class="silat-table-cell text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proposals as $proposal)
                            @php
                                $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'merged' => 'Digabungkan', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'];
                                $statusVariant = match($proposal->status) {'pending' => 'warning', 'approved', 'merged' => 'success', 'rejected' => 'danger', 'cancelled' => 'neutral', default => 'neutral'};
                            @endphp
                            <tr>
                                <td class="silat-table-cell">
                                    <span class="font-medium text-gray-900">{{ $proposal->student?->full_name ?: '-' }}</span>
                                    <div class="text-xs text-gray-500">{{ $proposal->student?->npm ?: '-' }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    {{ $proposal->internshipPeriod?->display_name ?: '-' }}
                                    <div class="text-xs text-gray-500">{{ $proposal->studyProgram?->name ?: '-' }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    <div class="font-medium text-gray-900">{{ $proposal->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $proposal->field_supervisor_name ?: 'Kontak belum diisi' }}
                                        @if ($proposal->field_supervisor_phone)
                                            · {{ $proposal->field_supervisor_phone }}
                                        @endif
                                    </div>
                                </td>
                                <td class="silat-table-cell text-gray-600">
                                    {{ Str::limit($proposal->address ?: '-', 80) }}
                                    <div class="text-xs text-gray-500">{{ $proposal->city_name ?: $proposal->city?->name ?: '-' }} · {{ $proposal->latitude }}, {{ $proposal->longitude }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    <x-badge :variant="$statusVariant">{{ $statusLabels[$proposal->status] ?? Str::headline($proposal->status) }}</x-badge>
                                    @if ($proposal->admin_note)
                                        <div class="mt-1 text-xs text-gray-500">{{ $proposal->admin_note }}</div>
                                    @endif
                                    @if ($proposal->approvedPlace)
                                        <div class="mt-1 text-xs text-green-700">Master: {{ $proposal->approvedPlace->name }}</div>
                                    @endif
                                </td>
                                <td class="silat-table-cell">
                                    @if ($proposal->status === 'pending')
                                        <div class="grid gap-3 xl:grid-cols-2">
                                            <form method="POST" action="{{ route('management.place-proposals.approve', $proposal) }}" class="space-y-2">
                                                @csrf
                                                <select name="mode" class="w-56 rounded-md border-gray-300 text-xs">
                                                    <option value="new">Jadikan master baru</option>
                                                    <option value="merge">Gabungkan ke master</option>
                                                </select>
                                                <select name="internship_place_id" class="w-56 rounded-md border-gray-300 text-xs">
                                                    <option value="">Pilih master jika merge</option>
                                                    @foreach ($places as $place)
                                                        <option value="{{ $place->id }}">{{ $place->name }}</option>
                                                    @endforeach
                                                </select>
                                                <textarea name="admin_note" rows="2" class="w-56 rounded-md border-gray-300 text-xs" placeholder="Catatan admin"></textarea>
                                                <button class="rounded-md bg-green-700 px-3 py-1 text-xs font-semibold text-white">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('management.place-proposals.reject', $proposal) }}" class="space-y-2">
                                                @csrf
                                                <textarea name="admin_note" rows="4" class="w-56 rounded-md border-gray-300 text-xs" placeholder="Alasan penolakan" required></textarea>
                                                <button class="rounded-md bg-red-700 px-3 py-1 text-xs font-semibold text-white">Tolak</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500">{{ $proposal->reviewer?->name ?: '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada usulan mitra" icon="fa-building-circle-check" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-pagination :paginator="$proposals" />
        </div>
    </div></div>
</x-app-layout>
