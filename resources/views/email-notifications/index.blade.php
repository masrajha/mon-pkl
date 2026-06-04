@php
    $tabs = [
        'status' => ['label' => 'Status Notifikasi', 'icon' => 'fa-toggle-on'],
        'queue' => ['label' => 'Antrean Email', 'icon' => 'fa-list-check'],
        'mail' => ['label' => 'Mail Server', 'icon' => 'fa-server'],
    ];

    $statusVariant = fn (string $status): string => match ($status) {
        'sent' => 'success',
        'failed' => 'danger',
        default => 'neutral',
    };

    $statusLabel = fn (string $status): string => match ($status) {
        'sent' => 'Terkirim',
        'failed' => 'Gagal',
        default => 'Pending',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Email & Notifikasi') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @include('management.partials.nav')

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="silat-card p-4">
                <div class="flex flex-wrap gap-2">
                    @foreach ($tabs as $key => $item)
                        <a href="{{ route('email-notifications.index', ['tab' => $key]) }}" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-semibold {{ $tab === $key ? 'border-blue-700 bg-blue-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
                            <x-icon :name="$item['icon']" class="w-4" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($tab === 'status')
                <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div class="rounded-lg border {{ $enabled ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide {{ $enabled ? 'text-emerald-700' : 'text-rose-700' }}">Status Sistem</p>
                        <p class="mt-3 text-2xl font-bold text-gray-950">{{ $enabled ? 'Aktif' : 'Nonaktif' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pending</p>
                        <p class="mt-3 text-2xl font-bold text-gray-950">{{ number_format((int) ($statusCounts['pending'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Terkirim</p>
                        <p class="mt-3 text-2xl font-bold text-gray-950">{{ number_format((int) ($statusCounts['sent'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Gagal</p>
                        <p class="mt-3 text-2xl font-bold text-gray-950">{{ number_format((int) ($statusCounts['failed'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
                    <section class="silat-card p-6">
                        <h3 class="text-base font-semibold text-gray-950">Kontrol Pengiriman</h3>
                        <p class="mt-1 text-sm text-gray-500">Saat nonaktif, event tetap masuk antrean tetapi email tidak dikirim.</p>
                        <form method="POST" action="{{ route('email-notifications.status.update') }}" class="mt-5 space-y-4">
                            @csrf
                            @method('PATCH')
                            <label class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-4">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-900">Email notifikasi aktif</span>
                                    <span class="mt-1 block text-xs text-gray-500">Matikan sementara ketika SMTP sedang bermasalah atau masa uji coba.</span>
                                </span>
                                <input type="checkbox" name="enabled" value="1" class="h-5 w-5 rounded border-gray-300 text-blue-600" @checked($enabled)>
                            </label>
                            <x-primary-button>Simpan Status</x-primary-button>
                        </form>
                    </section>

                    <section class="silat-card overflow-hidden">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-base font-semibold text-gray-950">Cakupan Notifikasi</h3>
                            <p class="mt-1 text-sm text-gray-500">Pilih workflow yang boleh membuat antrean email baru.</p>
                        </div>
                        <form method="POST" action="{{ route('email-notifications.coverage.update') }}">
                            @csrf
                            @method('PATCH')
                            <div class="silat-table-wrap">
                                <table class="silat-table">
                                    <thead class="silat-table-head">
                                        <tr>
                                            <th class="silat-table-cell">Workflow</th>
                                            <th class="silat-table-cell">Prefix Event</th>
                                            <th class="silat-table-cell">Status</th>
                                            <th class="silat-table-cell text-right">Aktif</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($categories as $category)
                                        <tr>
                                            <td class="silat-table-cell font-medium text-gray-900">{{ $category['label'] }}</td>
                                            <td class="silat-table-cell text-gray-600">{{ $category['type'] }}</td>
                                            <td class="silat-table-cell">
                                                @if (! $category['implemented'])
                                                    <x-badge variant="neutral">Belum tersedia</x-badge>
                                                @elseif ($category['enabled'])
                                                    <x-badge variant="success">Aktif</x-badge>
                                                @else
                                                    <x-badge variant="neutral">Nonaktif</x-badge>
                                                @endif
                                            </td>
                                            <td class="silat-table-cell text-right">
                                                <label class="inline-flex items-center justify-end gap-2 text-sm font-medium text-gray-700">
                                                    <span class="sr-only">Aktifkan {{ $category['label'] }}</span>
                                                    <input
                                                        type="checkbox"
                                                        name="categories[{{ $category['key'] }}]"
                                                        value="1"
                                                        class="h-5 w-5 rounded border-gray-300 text-blue-600 disabled:bg-gray-100"
                                                        @checked($category['enabled'])
                                                        @disabled(! $category['implemented'])
                                                        aria-label="Aktifkan notifikasi {{ $category['label'] }}"
                                                    >
                                                </label>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-t border-gray-100 px-6 py-4">
                                <p class="text-xs text-gray-500">Workflow yang belum tersedia ditampilkan sebagai referensi roadmap dan belum dapat diaktifkan.</p>
                                <x-primary-button>Simpan Cakupan</x-primary-button>
                            </div>
                        </form>
                    </section>
                </div>
            @elseif ($tab === 'queue')
                <div class="silat-card overflow-hidden">
                    <x-table-controls title="Daftar Antrean Email" description="Pantau status pengiriman email sistem." search-placeholder="Cari email, subjek, atau tipe...">
                        <x-slot name="filters">
                            <div>
                                <x-input-label for="filter_status" value="Status" />
                                <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Semua status</option>
                                    <option value="pending" @selected($selectedStatus === 'pending')>Pending</option>
                                    <option value="sent" @selected($selectedStatus === 'sent')>Terkirim</option>
                                    <option value="failed" @selected($selectedStatus === 'failed')>Gagal</option>
                                </select>
                                <input type="hidden" name="tab" value="queue">
                            </div>
                        </x-slot>
                    </x-table-controls>

                    <div class="flex flex-wrap gap-2 border-b border-gray-100 px-6 py-4">
                        <form method="POST" action="{{ route('email-notifications.process') }}">
                            @csrf
                            <x-primary-button>
                                <x-icon name="fa-paper-plane" class="mr-2" /> Proses Antrean
                            </x-primary-button>
                        </form>
                        <form method="POST" action="{{ route('email-notifications.retry-failed') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                <x-icon name="fa-rotate-left" class="mr-2" /> Retry Gagal
                            </button>
                        </form>
                    </div>

                    <div class="silat-table-wrap">
                        <table class="silat-table">
                            <thead class="silat-table-head">
                                <tr>
                                    <th class="silat-table-cell">Waktu</th>
                                    <th class="silat-table-cell">Penerima</th>
                                    <th class="silat-table-cell">Tipe/Subjek</th>
                                    <th class="silat-table-cell">Status</th>
                                    <th class="silat-table-cell">Attempt</th>
                                    <th class="silat-table-cell">Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($notifications as $notification)
                                    <tr>
                                        <td class="silat-table-cell text-gray-600">
                                            {{ $notification->created_at?->format('d/m/Y H:i') }}
                                            <div class="text-xs text-gray-400">Jadwal: {{ $notification->scheduled_for?->format('d/m/Y H:i') ?: 'segera' }}</div>
                                        </td>
                                        <td class="silat-table-cell">
                                            <span class="font-medium text-gray-900">{{ $notification->recipient_name ?: '-' }}</span>
                                            <div class="text-xs text-gray-500">{{ $notification->recipient_email }}</div>
                                        </td>
                                        <td class="silat-table-cell">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $notification->type }}</span>
                                            <div class="mt-1 text-sm text-gray-900">{{ $notification->subject }}</div>
                                        </td>
                                        <td class="silat-table-cell"><x-badge :variant="$statusVariant($notification->status)">{{ $statusLabel($notification->status) }}</x-badge></td>
                                        <td class="silat-table-cell text-gray-600">{{ $notification->attempts }}</td>
                                        <td class="silat-table-cell max-w-xs text-xs text-rose-700">{{ $notification->error_message ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="silat-table-cell">
                                            <x-empty-state title="Belum ada antrean email" icon="fa-envelope-open-text" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-table-pagination :paginator="$notifications" />
                </div>
            @else
                <section class="silat-card p-6">
                    <h3 class="text-base font-semibold text-gray-950">Konfigurasi Mail Server</h3>
                    <p class="mt-1 text-sm text-gray-500">Nilai ini menjadi override konfigurasi mail aplikasi saat antrean email diproses.</p>

                    <form method="POST" action="{{ route('email-notifications.mail.update') }}" class="mt-6 grid gap-5 md:grid-cols-2">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="mailer" value="Mailer" />
                            <select id="mailer" name="mailer" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="smtp" @selected(old('mailer', $mailSettings['mailer']) === 'smtp')>SMTP</option>
                                <option value="log" @selected(old('mailer', $mailSettings['mailer']) === 'log')>Log</option>
                                <option value="array" @selected(old('mailer', $mailSettings['mailer']) === 'array')>Array</option>
                            </select>
                            <x-input-error :messages="$errors->get('mailer')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="host" value="SMTP Host" />
                            <x-text-input id="host" name="host" class="mt-1 block w-full" :value="old('host', $mailSettings['host'])" />
                            <x-input-error :messages="$errors->get('host')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="port" value="SMTP Port" />
                            <x-text-input id="port" name="port" type="number" class="mt-1 block w-full" :value="old('port', $mailSettings['port'])" />
                            <x-input-error :messages="$errors->get('port')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="encryption" value="Enkripsi" />
                            <select id="encryption" name="encryption" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="" @selected(blank(old('encryption', $mailSettings['encryption'])))>Tanpa enkripsi</option>
                                <option value="tls" @selected(old('encryption', $mailSettings['encryption']) === 'tls')>TLS</option>
                                <option value="ssl" @selected(old('encryption', $mailSettings['encryption']) === 'ssl')>SSL</option>
                            </select>
                            <x-input-error :messages="$errors->get('encryption')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="username" value="Username" />
                            <x-text-input id="username" name="username" class="mt-1 block w-full" :value="old('username', $mailSettings['username'])" />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password" value="Password" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" placeholder="Kosongkan jika tidak diubah" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="from_address" value="Email Pengirim" />
                            <x-text-input id="from_address" name="from_address" type="email" class="mt-1 block w-full" :value="old('from_address', $mailSettings['from_address'])" />
                            <x-input-error :messages="$errors->get('from_address')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="from_name" value="Nama Pengirim" />
                            <x-text-input id="from_name" name="from_name" class="mt-1 block w-full" :value="old('from_name', $mailSettings['from_name'])" />
                            <x-input-error :messages="$errors->get('from_name')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-primary-button>Simpan Mail Server</x-primary-button>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
