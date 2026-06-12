<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Manajemen</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">Audit Log</h2>
            <p class="mt-1 text-sm text-gray-500">Jejak perubahan data penting: konfigurasi, sanksi, nilai, master data, peserta, dan Lupa Presensi.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('management.partials.nav')

            <form method="GET" class="silat-card grid gap-4 p-5 md:grid-cols-4">
                <div>
                    <x-input-label for="q" value="Cari" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" placeholder="Label, user, email, atau URL" :value="request('q')" />
                </div>
                <div>
                    <x-input-label for="category" value="Kategori" />
                    <select id="category" name="category" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ Str::headline($category) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="event" value="Event" />
                    <select id="event" name="event" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="">Semua event</option>
                        @foreach ($events as $event)
                            <option value="{{ $event }}" @selected($selectedEvent === $event)>{{ Str::headline($event) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button class="silat-btn w-full justify-center" type="submit"><x-icon name="fa-filter" /> Terapkan</button>
                </div>
            </form>

            <section class="silat-card overflow-hidden">
                <div class="silat-section-header">
                    <div>
                        <h3 class="silat-section-title">Daftar Jejak Perubahan</h3>
                        <p class="silat-section-description">Nilai before/after disimpan dalam JSON untuk kebutuhan audit dan penelusuran insiden.</p>
                    </div>
                    <x-badge variant="neutral">{{ number_format($logs->total(), 0, ',', '.') }} log</x-badge>
                </div>

                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Waktu</th>
                                <th class="silat-table-cell">Aktor</th>
                                <th class="silat-table-cell">Objek</th>
                                <th class="silat-table-cell">Event</th>
                                <th class="silat-table-cell">Perubahan</th>
                                <th class="silat-table-cell">Konteks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="silat-table-cell whitespace-nowrap">
                                        <p class="font-semibold text-gray-900">{{ $log->created_at?->format('d/m/Y H:i:s') }}</p>
                                        <p class="text-xs text-gray-500">{{ $log->ip_address ?: '-' }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        <p class="font-semibold text-gray-900">{{ $log->user_name ?: 'Sistem' }}</p>
                                        <p class="text-xs text-gray-500">{{ $log->user_email ?: '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $log->user_role ?: '-' }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        <x-badge variant="neutral">{{ Str::headline($log->category) }}</x-badge>
                                        <p class="mt-2 font-semibold text-gray-900">{{ $log->auditable_label ?: class_basename($log->auditable_type).' #'.$log->auditable_id }}</p>
                                        <p class="break-all text-xs text-gray-500">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id ?: '-' }}</p>
                                    </td>
                                    <td class="silat-table-cell">
                                        <x-badge :variant="$log->event === 'deleted' ? 'danger' : ($log->event === 'created' ? 'success' : 'info')">{{ Str::headline($log->event) }}</x-badge>
                                    </td>
                                    <td class="silat-table-cell min-w-[24rem]">
                                        @if ($log->event === 'updated' && filled($log->changed_values))
                                            <div class="space-y-2">
                                                @foreach ($log->changed_values as $field => $change)
                                                    <details class="rounded-md border border-gray-200 bg-gray-50 p-2 text-xs">
                                                        <summary class="cursor-pointer font-semibold text-gray-800">{{ $field }}</summary>
                                                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                                                            <div><span class="font-semibold text-gray-500">Sebelum</span><pre class="mt-1 whitespace-pre-wrap rounded bg-white p-2 text-gray-700">{{ json_encode($change['old'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div>
                                                            <div><span class="font-semibold text-gray-500">Sesudah</span><pre class="mt-1 whitespace-pre-wrap rounded bg-white p-2 text-gray-700">{{ json_encode($change['new'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div>
                                                        </div>
                                                    </details>
                                                @endforeach
                                            </div>
                                        @else
                                            <details class="rounded-md border border-gray-200 bg-gray-50 p-2 text-xs">
                                                <summary class="cursor-pointer font-semibold text-gray-800">{{ $log->event === 'created' ? 'Data dibuat' : 'Data sebelum dihapus' }}</summary>
                                                <pre class="mt-2 max-h-72 overflow-auto whitespace-pre-wrap rounded bg-white p-2 text-gray-700">{{ json_encode($log->event === 'created' ? $log->new_values : $log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell max-w-sm">
                                        <p class="text-xs font-semibold text-gray-500">{{ $log->method ?: '-' }}</p>
                                        <p class="break-all text-xs text-gray-600">{{ $log->url ?: '-' }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="silat-table-cell">
                                        <x-empty-state title="Belum ada audit log" icon="fa-clock-rotate-left" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-table-pagination :paginator="$logs" />
            </section>
        </div>
    </div>
</x-app-layout>
