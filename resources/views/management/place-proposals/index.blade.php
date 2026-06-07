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
                    <div>
                        <x-input-label for="filter_period_id" value="Periode Program" />
                        <select id="filter_period_id" name="period_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">Semua periode</option>
                            @foreach ($periodOptions as $period)
                                <option value="{{ $period->id }}" @selected((int) $selectedPeriodId === (int) $period->id)>{{ $period->display_name }}</option>
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
                            <th class="silat-table-cell">Aksi</th>
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
                                <td class="silat-table-cell min-w-[24rem]">
                                    @if ($proposal->status === 'pending')
                                        <div class="space-y-3 text-left">
                                            <form method="POST" action="{{ route('management.place-proposals.approve', $proposal) }}" class="rounded-lg border border-emerald-100 bg-emerald-50/40 p-3">
                                                @csrf
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <div>
                                                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Keputusan</label>
                                                        <select name="mode" class="silat-field h-9 text-xs">
                                                            <option value="new">Jadikan master baru</option>
                                                            <option value="merge">Gabungkan ke master</option>
                                                        </select>
                                                    </div>
                                                    <div data-management-place-search data-search-url="{{ route('management.places.search') }}" class="relative">
                                                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Master Mitra</label>
                                                        <input type="hidden" name="internship_place_id" data-place-id>
                                                        <input type="search" data-place-input class="silat-field h-9 text-xs" autocomplete="off" placeholder="Cari jika digabung">
                                                        <div data-place-suggestions class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-auto rounded-md border border-gray-200 bg-white text-left shadow-lg"></div>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Catatan</label>
                                                    <textarea name="admin_note" rows="2" class="silat-field text-xs" placeholder="Catatan admin opsional"></textarea>
                                                </div>
                                                <div class="mt-2 flex justify-end">
                                                    <button class="silat-btn px-3 py-2 text-xs"><x-icon name="fa-check" /> Setujui</button>
                                                </div>
                                            </form>
                                            <form method="POST" action="{{ route('management.place-proposals.reject', $proposal) }}" class="rounded-lg border border-red-100 bg-red-50/40 p-3">
                                                @csrf
                                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-red-700">Alasan Penolakan</label>
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                                                    <textarea name="admin_note" rows="2" class="silat-field min-h-16 text-xs" placeholder="Alasan penolakan" required></textarea>
                                                    <button class="silat-btn-danger shrink-0 px-3 py-2 text-xs"><x-icon name="fa-xmark" /> Tolak</button>
                                                </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-management-place-search]').forEach((root) => {
                const input = root.querySelector('[data-place-input]');
                const hidden = root.querySelector('[data-place-id]');
                const suggestions = root.querySelector('[data-place-suggestions]');
                let timeout;

                const render = (places) => {
                    suggestions.innerHTML = '';

                    if (! places.length) {
                        suggestions.innerHTML = '<div class="px-3 py-2 text-xs text-gray-500">Mitra tidak ditemukan.</div>';
                        suggestions.classList.remove('hidden');
                        return;
                    }

                    places.forEach((place) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'block w-full px-3 py-2 text-left text-xs hover:bg-gray-50 focus:bg-gray-50';
                        const name = document.createElement('span');
                        name.className = 'font-medium text-gray-900';
                        name.textContent = place.name;
                        const meta = document.createElement('div');
                        meta.className = 'text-[11px] text-gray-500';
                        meta.textContent = [place.city, place.address].filter(Boolean).join(' · ') || 'Alamat belum diisi';
                        button.append(name, meta);
                        button.addEventListener('click', () => {
                            hidden.value = place.id;
                            input.value = place.label;
                            suggestions.classList.add('hidden');
                        });
                        suggestions.appendChild(button);
                    });

                    suggestions.classList.remove('hidden');
                };

                input.addEventListener('input', () => {
                    clearTimeout(timeout);
                    hidden.value = '';

                    const query = input.value.trim();
                    if (query.length < 2) {
                        suggestions.classList.add('hidden');
                        return;
                    }

                    timeout = setTimeout(async () => {
                        const response = await fetch(`${root.dataset.searchUrl}?q=${encodeURIComponent(query)}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        render(response.ok ? await response.json() : []);
                    }, 250);
                });

                document.addEventListener('click', (event) => {
                    if (! root.contains(event.target)) suggestions.classList.add('hidden');
                });
            });
        });
    </script>
</x-app-layout>
