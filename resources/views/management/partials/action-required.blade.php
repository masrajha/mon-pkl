@php
    $summary ??= [];
    $total = collect($summary)->sum('count');
@endphp

<section class="silat-card border-l-4 {{ $total > 0 ? 'border-l-amber-500' : 'border-l-green-500' }}">
    <div class="silat-section-header">
        <div class="flex items-center gap-3">
            <x-icon :name="$total > 0 ? 'fa-bell' : 'fa-circle-check'" class="{{ $total > 0 ? 'text-amber-500' : 'text-green-600' }}" />
            <div>
                <h3 class="silat-section-title">Tindakan Diperlukan</h3>
                <p class="silat-section-description">
                    {{ $total > 0 ? number_format($total, 0, ',', '.').' pengajuan menunggu tindakan.' : 'Tidak ada tindakan tertunda.' }}
                </p>
            </div>
        </div>
        <x-badge variant="{{ $total > 0 ? 'warning' : 'success' }}">{{ number_format($total, 0, ',', '.') }} pending</x-badge>
    </div>
    <div class="grid gap-3 p-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($summary as $item)
            <a class="silat-action-card flex h-full flex-col justify-between gap-4" href="{{ route($item['route'], $item['params'] ?? []) }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $item['count'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                        <x-icon :name="$item['icon']" />
                    </div>
                    @if ($item['count'] > 0)
                        <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">{{ number_format($item['count'], 0, ',', '.') }}</span>
                    @endif
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ $item['label'] }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $item['description'] }}</p>
                </div>
                <p class="text-sm font-semibold {{ $item['count'] > 0 ? 'text-blue-700' : 'text-gray-400' }}">{{ $item['count'] > 0 ? 'Tinjau pengajuan' : 'Tidak ada pending' }}</p>
            </a>
        @endforeach
    </div>
</section>
