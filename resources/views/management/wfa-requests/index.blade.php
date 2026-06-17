<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Akademik</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">Pengajuan WFA</h2>
            <p class="mt-1 text-sm text-gray-500">Review pengajuan Work from anywhere mahasiswa beserta bukti pendukungnya.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            @include('management.partials.nav')

            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif
            @if ($errors->any())
                <x-alert variant="danger">{{ $errors->first() }}</x-alert>
            @endif

            <section class="silat-card p-5">
                <form method="GET" class="grid gap-4 md:grid-cols-[1fr_220px_260px_auto]">
                    <div>
                        <x-input-label for="q" value="Cari" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Mahasiswa, NPM, mitra, atau lokasi WFA" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full">
                            <option value="">Semua status</option>
                            <option value="pending" @selected($selectedStatus === 'pending')>Diajukan</option>
                            <option value="approved" @selected($selectedStatus === 'approved')>Disetujui</option>
                            <option value="rejected" @selected($selectedStatus === 'rejected')>Ditolak</option>
                            <option value="cancelled" @selected($selectedStatus === 'cancelled')>Dibatalkan</option>
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="period_id" value="Periode Program" />
                        <x-select-input id="period_id" name="period_id" class="mt-1 block w-full">
                            <option value="">Semua periode</option>
                            @foreach ($periodOptions as $period)
                                <option value="{{ $period->id }}" @selected((int) $selectedPeriodId === (int) $period->id)>{{ $period->display_name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="flex items-end"><x-primary-button>Filter</x-primary-button></div>
                </form>
            </section>

            <section class="silat-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Mahasiswa</th>
                                <th class="silat-table-cell">Tanggal WFA</th>
                                <th class="silat-table-cell">Lokasi / Aktivitas</th>
                                <th class="silat-table-cell">Bukti</th>
                                <th class="silat-table-cell">Status</th>
                                <th class="silat-table-cell">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($requests as $item)
                                @php
                                    $statusVariant = match ($item->status) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'cancelled' => 'neutral',
                                        default => 'warning',
                                    };
                                    $statusLabel = match ($item->status) {
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        'cancelled' => 'Dibatalkan',
                                        default => 'Diajukan',
                                    };
                                @endphp
                                <tr>
                                    <td class="silat-table-cell min-w-[240px]">
                                        <p class="font-semibold text-gray-900">{{ $item->enrollment?->student?->full_name ?: '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $item->enrollment?->student?->npm ?: '-' }} · {{ $item->enrollment?->studyProgram?->name ?: '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $item->enrollment?->internshipPeriod?->display_name ?: '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $item->enrollment?->internshipPlace?->name ?: '-' }}</p>
                                    </td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        <p class="font-semibold text-gray-900">{{ $item->starts_at?->format('d/m/Y') }} s.d. {{ $item->ends_at?->format('d/m/Y') }}</p>
                                        <p class="mt-1 text-xs text-gray-500">Diajukan {{ $item->created_at?->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td class="silat-table-cell min-w-[320px]">
                                        <p class="font-semibold text-gray-900">{{ $item->planned_location }}</p>
                                        @if ($item->planned_latitude !== null && $item->planned_longitude !== null)
                                            <p class="mt-1 text-xs text-gray-500">{{ $item->planned_latitude }}, {{ $item->planned_longitude }}</p>
                                        @endif
                                        <p class="mt-2"><span class="font-semibold">Aktivitas:</span> {{ $item->planned_activity }}</p>
                                        <p class="mt-2"><span class="font-semibold">Alasan:</span> {{ $item->reason }}</p>
                                    </td>
                                    <td class="silat-table-cell min-w-[160px]">
                                        <a class="silat-secondary-link" href="{{ route('media.public', ['path' => $item->evidence_path]) }}" target="_blank" rel="noopener">Buka bukti</a>
                                    </td>
                                    <td class="silat-table-cell min-w-[180px]">
                                        <x-badge :variant="$statusVariant">{{ $statusLabel }}</x-badge>
                                        @if ($item->reviewed_at)
                                            <p class="mt-2 text-xs text-gray-500">
                                                {{ $item->reviewed_at?->format('d/m/Y H:i') }}<br>
                                                {{ $item->reviewer?->name ?: '-' }}
                                            </p>
                                        @endif
                                        @if ($item->review_note)
                                            <p class="mt-2 text-xs text-gray-600">{{ $item->review_note }}</p>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell min-w-[260px]">
                                        @if ($item->status === 'pending')
                                            <div class="space-y-2">
                                                <form method="POST" action="{{ route('wfa-requests.management.approve', $item) }}" class="space-y-2">
                                                    @csrf
                                                    <textarea name="review_note" rows="2" class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Catatan persetujuan opsional"></textarea>
                                                    <button class="silat-btn px-3 py-2 text-xs"><x-icon name="fa-check" /> Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('wfa-requests.management.reject', $item) }}" class="space-y-2">
                                                    @csrf
                                                    <textarea name="review_note" rows="2" class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Alasan penolakan opsional"></textarea>
                                                    <button class="silat-btn-secondary px-3 py-2 text-xs"><x-icon name="fa-xmark" /> Tolak</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">Tidak ada aksi.</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="silat-table-cell">
                                        <x-empty-state title="Tidak ada pengajuan WFA" description="Belum ada pengajuan pada filter saat ini." icon="fa-laptop-house" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4">{{ $requests->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
