@props([
    'deadlines' => collect(),
    'title' => 'Deadline Terdekat',
    'description' => 'Deadline dalam 7 hari ke depan, atau deadline terdekat berikutnya jika tidak ada.',
])

@php
    $deadlineLabels = config('monpkl.deadline_types', []);
@endphp

<section class="silat-card">
    <div class="silat-section-header">
        <div>
            <h3 class="silat-section-title">{{ $title }}</h3>
            <p class="silat-section-description">{{ $description }}</p>
        </div>
        <x-icon name="fa-calendar-day" class="text-2xl text-blue-600" />
    </div>
    <div class="grid gap-3 p-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($deadlines as $deadline)
            @php
                $date = $deadline->deadline_date;
                $days = $date ? today()->startOfDay()->diffInDays($date->copy()->startOfDay(), false) : null;
                $tone = $days === 0
                    ? 'border-rose-200 bg-rose-50 text-rose-900'
                    : ($days !== null && $days <= 7 ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-blue-200 bg-blue-50 text-blue-900');
            @endphp
            <article class="rounded-lg border p-4 {{ $tone }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-950">{{ $deadlineLabels[$deadline->deadline_type] ?? Str::headline($deadline->deadline_type) }}</p>
                        <p class="mt-1 text-xs text-gray-600">{{ $deadline->internshipPeriod?->display_name ?: 'Periode' }}</p>
                    </div>
                    <p class="shrink-0 text-right text-base font-bold">{{ $date?->format('d/m') }}</p>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($days === 0)
                        <x-badge variant="danger">Hari ini</x-badge>
                    @elseif ($days !== null && $days > 0)
                        <x-badge variant="{{ $days <= 7 ? 'warning' : 'info' }}">{{ $days }} hari lagi</x-badge>
                    @endif
                    @if ($deadline->penalty_points > 0)
                        <span class="text-xs font-semibold">{{ number_format($deadline->penalty_points, 0, ',', '.') }} poin {{ $deadline->is_fixed_penalty ? 'tetap' : '/ hari' }}</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="md:col-span-2 xl:col-span-3">
                <x-empty-state title="Belum ada deadline" icon="fa-calendar-xmark" />
            </div>
        @endforelse
    </div>
</section>
