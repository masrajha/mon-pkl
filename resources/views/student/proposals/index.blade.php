<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Mahasiswa</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Usulan Mitra') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Histori usulan mitra kegiatan yang pernah dikirim.</p>
            </div>
            <a class="silat-btn" href="{{ route('student.proposals.create') }}"><x-icon name="fa-plus" /> Ajukan Mitra</a>
        </div>
    </x-slot>

    <div class="py-8"><div class="silat-shell space-y-6">
        @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif

        <section class="silat-card overflow-hidden">
            <div class="silat-section-header">
                <div>
                    <h3 class="silat-section-title">Histori Usulan</h3>
                    <p class="silat-section-description">Nama mitra, lokasi mitra, periode, status, dan catatan reviewer.</p>
                </div>
            </div>
            <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head">
                        <tr>
                            <th class="silat-table-cell">Periode/Prodi</th>
                            <th class="silat-table-cell">Mitra Usulan</th>
                            <th class="silat-table-cell">Lokasi Mitra</th>
                            <th class="silat-table-cell">Kontak</th>
                            <th class="silat-table-cell">Status</th>
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
                                    {{ $proposal->internshipPeriod?->display_name ?: '-' }}
                                    <div class="text-xs text-gray-500">{{ $proposal->studyProgram?->name ?: '-' }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    <div class="font-medium text-gray-900">{{ $proposal->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $proposal->approvedPlace?->name ? 'Master: '.$proposal->approvedPlace->name : 'Dikirim '.$proposal->created_at?->format('d M Y H:i') }}</div>
                                </td>
                                <td class="silat-table-cell text-gray-600">
                                    {{ Str::limit($proposal->address ?: '-', 80) }}
                                    <div class="text-xs text-gray-500">{{ $proposal->city_name ?: $proposal->city?->name ?: '-' }} · {{ $proposal->latitude }}, {{ $proposal->longitude }}</div>
                                </td>
                                <td class="silat-table-cell text-gray-600">
                                    {{ $proposal->field_supervisor_name ?: '-' }}
                                    <div class="text-xs text-gray-500">{{ $proposal->field_supervisor_phone ?: '-' }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    <x-badge :variant="$statusVariant">{{ $statusLabels[$proposal->status] ?? Str::headline($proposal->status) }}</x-badge>
                                    @if ($proposal->admin_note)
                                        <div class="mt-1 text-xs text-gray-500">{{ $proposal->admin_note }}</div>
                                    @endif
                                </td>
                                <td class="silat-table-cell text-right">
                                    @if ($proposal->status === 'pending')
                                        <div class="flex justify-end gap-3">
                                            <a class="silat-secondary-link" href="{{ route('student.proposals.edit', $proposal) }}">Edit</a>
                                            <form method="POST" action="{{ route('student.proposals.cancel', $proposal) }}">
                                                @csrf @method('PATCH')
                                                <button class="silat-secondary-link text-red-700" type="submit">Batalkan</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500">{{ $proposal->reviewer?->name ?: '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada usulan mitra" description="Usulan yang dikirim akan muncul di sini." icon="fa-building-circle-arrow-right" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
