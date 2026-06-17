<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Akademik</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">Lupa Presensi</h2>
            <p class="mt-1 text-sm text-gray-500">Review pengajuan koreksi presensi mahasiswa.</p>
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
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Mahasiswa, NPM, atau mitra" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full">
                            <option value="">Semua status</option>
                            <option value="pending" @selected($selectedStatus === 'pending')>Diajukan</option>
                            <option value="approved" @selected($selectedStatus === 'approved')>Disetujui</option>
                            <option value="rejected" @selected($selectedStatus === 'rejected')>Ditolak</option>
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
                                <th class="silat-table-cell">Presensi Diajukan</th>
                                <th class="silat-table-cell">Alasan / Catatan</th>
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
                                        default => 'warning',
                                    };
                                    $statusLabel = match ($item->status) {
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        default => 'Diajukan',
                                    };
                                    $photoUrl = \App\Support\PublicStorage::url($item->photo_path);
                                @endphp
                                <tr>
                                    <td class="silat-table-cell min-w-[240px]">
                                        <p class="font-semibold text-gray-900">{{ $item->enrollment?->student?->full_name ?: '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $item->enrollment?->student?->npm ?: '-' }} · {{ $item->enrollment?->studyProgram?->name ?: '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $item->enrollment?->internshipPeriod?->display_name ?: '-' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $item->enrollment?->internshipPlace?->name ?: '-' }}</p>
                                    </td>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        <x-badge>{{ $item->action === 'check_out' ? 'Pulang' : 'Masuk' }}</x-badge>
                                        <p class="mt-2 font-semibold text-gray-900">{{ $item->requested_checked_at?->format('d/m/Y H:i') }}</p>
                                        <p class="mt-1 text-xs text-gray-500">Diajukan {{ $item->created_at?->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td class="silat-table-cell min-w-[300px]">
                                        <p><span class="font-semibold">Catatan:</span> {{ $item->note }}</p>
                                        <p class="mt-2"><span class="font-semibold">Alasan:</span> {{ $item->reason }}</p>
                                    </td>
                                    <td class="silat-table-cell min-w-[180px] text-sm text-gray-600">
                                        <p>Jarak: {{ $item->distance_meters !== null ? number_format($item->distance_meters, 0, ',', '.').' m' : '-' }}</p>
                                        @if ($photoUrl)
                                            <a href="{{ $photoUrl }}" target="_blank" rel="noopener" class="mt-3 block w-32 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow-sm transition hover:border-blue-300 hover:shadow">
                                                <img src="{{ $photoUrl }}" alt="Foto bukti lupa presensi {{ $item->enrollment?->student?->full_name ?: 'mahasiswa' }}" class="aspect-[4/3] w-full object-cover">
                                            </a>
                                            <a href="{{ $photoUrl }}" target="_blank" rel="noopener" class="mt-2 inline-flex text-xs font-semibold text-blue-700 hover:text-blue-900">
                                                Lihat foto
                                            </a>
                                        @else
                                            <p class="mt-2 text-xs text-gray-500">Foto: -</p>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell min-w-[180px]">
                                        <x-badge :variant="$statusVariant">{{ $statusLabel }}</x-badge>
                                        @if ($item->reviewed_at)
                                            <p class="mt-2 text-xs text-gray-500">
                                                {{ $item->reviewed_at?->format('d/m/Y H:i') }}<br>
                                                {{ $item->reviewed_by_name ?: $item->reviewed_by_email }}
                                            </p>
                                        @endif
                                        @if ($item->review_note)
                                            <p class="mt-2 text-xs text-gray-600">{{ $item->review_note }}</p>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell min-w-[260px]">
                                        @if ($item->status === 'pending')
                                            <div class="space-y-2">
                                                <form method="POST" action="{{ route('forgotten-attendance-requests.management.approve', $item) }}" class="space-y-2">
                                                    @csrf
                                                    <textarea name="review_note" rows="2" class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Catatan persetujuan opsional"></textarea>
                                                    <button class="silat-btn px-3 py-2 text-xs"><x-icon name="fa-check" /> Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('forgotten-attendance-requests.management.reject', $item) }}" class="space-y-2">
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
                                        <x-empty-state title="Tidak ada pengajuan" description="Belum ada pengajuan lupa presensi pada filter saat ini." icon="fa-calendar-xmark" />
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
