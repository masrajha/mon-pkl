<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Permohonan Pindah Mitra') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

        <div class="silat-card overflow-hidden">
            <x-table-controls title="Daftar Permohonan Pindah Mitra" description="Cari mahasiswa, mitra, atau alasan pindah." search-placeholder="Cari permohonan...">
                <x-slot name="filters">
                    <div>
                        <x-input-label for="filter_status" value="Status" />
                        <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">Semua status</option>
                            @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-slot>
            </x-table-controls>
            <div class="silat-table-wrap">
            <table class="silat-table">
                <thead class="silat-table-head"><tr><th class="silat-table-cell">Mahasiswa</th><th class="silat-table-cell">Periode/Prodi</th><th class="silat-table-cell">Dari</th><th class="silat-table-cell">Ke</th><th class="silat-table-cell">Alasan</th><th class="silat-table-cell"><x-sortable-heading column="status" label="Status" /></th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr>
                            <td class="silat-table-cell"><span class="font-medium text-gray-900">{{ $request->enrollment?->student?->full_name }}</span><div class="text-xs text-gray-500">{{ $request->enrollment?->student?->npm }}</div></td>
                            <td class="silat-table-cell">{{ $request->enrollment?->internshipPeriod?->display_name }}<div class="text-xs text-gray-500">{{ $request->enrollment?->studyProgram?->name }}</div></td>
                            <td class="silat-table-cell text-gray-600">{{ $request->currentPlace?->name ?: '-' }}</td>
                            <td class="silat-table-cell text-gray-600">{{ $request->newPlace?->name ?: '-' }}</td>
                            <td class="silat-table-cell text-gray-600">{{ Str::limit($request->reason, 80) }}</td>
                            <td class="silat-table-cell">@php($statusVariant = match($request->status) {'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'neutral'})<x-badge :variant="$statusVariant">{{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$request->status] ?? Str::headline($request->status) }}</x-badge>@if($request->admin_note)<div class="mt-1 text-xs text-gray-500">{{ $request->admin_note }}</div>@endif</td>
                            <td class="silat-table-cell">
                                @if ($request->status === 'pending')
                                    <form method="POST" action="{{ route('management.relocations.update', $request) }}" class="space-y-2">
                                        @csrf @method('PATCH')
                                        <textarea name="admin_note" rows="2" class="w-56 rounded-md border-gray-300 text-xs" placeholder="Catatan admin"></textarea>
                                        <div class="flex gap-2">
                                            <button name="status" value="approved" class="rounded-md bg-green-700 px-3 py-1 text-xs font-semibold text-white">Setujui</button>
                                            <button name="status" value="rejected" class="rounded-md bg-red-700 px-3 py-1 text-xs font-semibold text-white">Tolak</button>
                                        </div>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-500">{{ $request->reviewer?->name ?: '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="silat-table-cell"><x-empty-state title="Belum ada permohonan pindah tempat" icon="fa-route" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div><x-table-pagination :paginator="$requests" /></div>
    </div></div>
</x-app-layout>
