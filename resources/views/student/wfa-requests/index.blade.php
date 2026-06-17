<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Layanan Program</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Pengajuan WFA') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Histori pengajuan Work from Anywhere beserta bukti pendukung.</p>
            </div>
            @unless ($hasPendingRequest)
                <a class="silat-btn" href="{{ route('student.wfa-requests.create') }}"><x-icon name="fa-plus" /> Ajukan WFA</a>
            @endunless
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell space-y-6">
        @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif

        @if ($hasPendingRequest)
            <x-alert variant="warning">Masih ada pengajuan WFA berstatus Menunggu. Ajukan WFA baru setelah pengajuan tersebut disetujui, ditolak, atau dibatalkan.</x-alert>
        @endif

        <section class="silat-card overflow-hidden">
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">Histori Pengajuan</h3>
                    <p class="silat-section-description">Tanggal WFA, lokasi rencana, alasan, bukti, status, dan catatan reviewer.</p>
                </div>
            </div>
            <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head">
                        <tr>
                            <th class="silat-table-cell">Periode/Prodi</th>
                            <th class="silat-table-cell">Tanggal</th>
                            <th class="silat-table-cell">Lokasi & Aktivitas</th>
                            <th class="silat-table-cell">Bukti</th>
                            <th class="silat-table-cell">Status</th>
                            <th class="silat-table-cell text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $wfaRequest)
                            @php
                                $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'];
                                $statusVariant = match($wfaRequest->status) {'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'neutral', default => 'neutral'};
                            @endphp
                            <tr>
                                <td class="silat-table-cell">
                                    {{ $wfaRequest->enrollment?->internshipPeriod?->display_name ?: '-' }}
                                    <div class="text-xs text-gray-500">{{ $wfaRequest->enrollment?->studyProgram?->name ?: '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $wfaRequest->enrollment?->internshipPlace?->name ?: 'Mitra belum ditentukan' }}</div>
                                </td>
                                <td class="silat-table-cell text-gray-700">
                                    {{ $wfaRequest->starts_at?->format('d/m/Y') }}
                                    <span class="text-gray-400">s.d.</span>
                                    {{ $wfaRequest->ends_at?->format('d/m/Y') }}
                                </td>
                                <td class="silat-table-cell">
                                    <p class="font-medium text-gray-900">{{ $wfaRequest->planned_location }}</p>
                                    <p class="text-xs text-gray-500">{{ Str::limit($wfaRequest->planned_activity, 90) }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Alasan: {{ Str::limit($wfaRequest->reason, 90) }}</p>
                                </td>
                                <td class="silat-table-cell">
                                    <a class="silat-secondary-link" href="{{ route('media.public', ['path' => $wfaRequest->evidence_path]) }}" target="_blank">Buka bukti</a>
                                </td>
                                <td class="silat-table-cell">
                                    <x-badge :variant="$statusVariant">{{ $statusLabels[$wfaRequest->status] ?? Str::headline($wfaRequest->status) }}</x-badge>
                                    @if ($wfaRequest->review_note)
                                        <div class="mt-1 text-xs text-gray-500">{{ $wfaRequest->review_note }}</div>
                                    @endif
                                </td>
                                <td class="silat-table-cell text-right">
                                    @if ($wfaRequest->status === 'pending')
                                        <form method="POST" action="{{ route('student.wfa-requests.cancel', $wfaRequest) }}">
                                            @csrf @method('PATCH')
                                            <button class="silat-secondary-link text-red-700" type="submit">Batalkan</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500">{{ $wfaRequest->reviewer?->name ?: '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada pengajuan WFA" description="Pengajuan WFA yang dikirim akan muncul di sini." icon="fa-laptop-house" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
