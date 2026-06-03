<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Mahasiswa</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Perubahan Pembimbing') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Histori pengajuan perubahan dosen dan pembimbing lapangan.</p>
            </div>
            @unless ($hasPendingRequest)
                <a class="silat-btn" href="{{ route('student.supervisor-requests.create') }}"><x-icon name="fa-plus" /> Ajukan Perubahan</a>
            @endunless
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell space-y-6">
        @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif

        @if ($hasPendingRequest)
            <x-alert variant="warning">Masih ada permohonan perubahan pembimbing berstatus Menunggu. Ajukan permohonan baru setelah permohonan tersebut disetujui, ditolak, atau dibatalkan.</x-alert>
        @endif

        <section class="silat-card overflow-hidden">
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">Histori Pengajuan</h3>
                    <p class="silat-section-description">Data pembimbing sebelumnya, usulan pengganti, alasan, dan keputusan reviewer.</p>
                </div>
            </div>
            <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head">
                        <tr>
                            <th class="silat-table-cell">Periode/Prodi</th>
                            <th class="silat-table-cell">Dosen Pembimbing</th>
                            <th class="silat-table-cell">Pembimbing Lapangan</th>
                            <th class="silat-table-cell">Alasan</th>
                            <th class="silat-table-cell">Status</th>
                            <th class="silat-table-cell text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            @php
                                $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'];
                                $statusVariant = match($request->status) {'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'neutral', default => 'neutral'};
                            @endphp
                            <tr>
                                <td class="silat-table-cell">
                                    {{ $request->enrollment?->internshipPeriod?->display_name ?: '-' }}
                                    <div class="text-xs text-gray-500">{{ $request->enrollment?->studyProgram?->name ?: '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $request->enrollment?->internshipPlace?->name ?: '-' }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    <div class="text-xs text-gray-500">Sebelumnya</div>
                                    <div class="font-medium text-gray-900">{{ $request->currentLecturer?->name ?: '-' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">Usulan</div>
                                    <div class="text-gray-700">{{ $request->requestedLecturer?->name ?: 'Ditentukan reviewer' }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    <div class="text-xs text-gray-500">Sebelumnya</div>
                                    <div class="font-medium text-gray-900">{{ $request->current_field_supervisor ?: '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $request->current_field_supervisor_email ?: '-' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">Usulan</div>
                                    <div class="text-gray-700">{{ $request->requested_field_supervisor ?: '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $request->requested_field_supervisor_email ?: '-' }}</div>
                                </td>
                                <td class="silat-table-cell text-gray-600">{{ Str::limit($request->reason, 100) }}</td>
                                <td class="silat-table-cell">
                                    <x-badge :variant="$statusVariant">{{ $statusLabels[$request->status] ?? Str::headline($request->status) }}</x-badge>
                                    @if ($request->admin_note)
                                        <div class="mt-1 text-xs text-gray-500">{{ $request->admin_note }}</div>
                                    @endif
                                </td>
                                <td class="silat-table-cell text-right">
                                    @if ($request->status === 'pending')
                                        <form method="POST" action="{{ route('student.supervisor-requests.cancel', $request) }}">
                                            @csrf @method('PATCH')
                                            <button class="silat-secondary-link text-red-700" type="submit">Batalkan</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500">{{ $request->reviewer?->name ?: '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada pengajuan perubahan pembimbing" description="Pengajuan yang dikirim akan muncul di sini." icon="fa-user-pen" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
