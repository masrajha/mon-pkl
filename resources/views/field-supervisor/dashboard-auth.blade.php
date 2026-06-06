<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Pembimbing Lapangan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Portal Pembimbing Lapangan') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Validasi catatan harian mahasiswa berdasarkan email pembimbing lapangan.') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        @include('field-supervisor.dashboard', [
            'enrollments' => $enrollments,
            'email' => $email,
            'accessMode' => $accessMode,
            'initialTab' => $initialTab ?? 'daily',
            'dailyRowsByEnrollment' => $dailyRowsByEnrollment,
            'attendanceScoresByEnrollment' => $attendanceScoresByEnrollment,
        ])
    </div>
</x-app-layout>
