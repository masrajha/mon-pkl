<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Pembimbing Lapangan</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">Dashboard</h2>
                <p class="mt-1 text-sm text-gray-500">Ringkasan mahasiswa bimbingan dan pekerjaan validasi.</p>
            </div>
            <a href="{{ route('field-supervisor.enrollments.index') }}" class="silat-btn">
                <x-icon name="fa-users" />
                Mahasiswa Bimbingan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif

            <section class="silat-card p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Portal Pembimbing Lapangan</p>
                        <h1 class="mt-1 text-2xl font-semibold text-gray-900">Selamat datang</h1>
                        <p class="mt-1 text-sm text-gray-500">Akses login email untuk {{ $email }}.</p>
                    </div>
                    <x-badge>Login</x-badge>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-4">
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Total Mahasiswa</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($stats['total_students'], 0, ',', '.') }}</p>
                </div>
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Periode Aktif</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($stats['active_students'], 0, ',', '.') }}</p>
                </div>
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Catatan Perlu Validasi</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($stats['pending_validations'], 0, ',', '.') }}</p>
                </div>
                <div class="silat-stat-card">
                    <p class="silat-stat-label">Penilaian Menunggu</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($stats['pending_assessments'], 0, ',', '.') }}</p>
                </div>
            </section>

            @foreach ([
                'Periode Aktif' => $activeSummaries,
                'Periode Selesai' => $completedSummaries,
            ] as $title => $items)
                <section class="silat-card">
                    <div class="silat-section-header">
                        <div>
                            <h2 class="silat-section-title">{{ $title }}</h2>
                            <p class="silat-section-description">{{ number_format($items->count(), 0, ',', '.') }} mahasiswa bimbingan.</p>
                        </div>
                        <a href="{{ route('field-supervisor.enrollments.index', ['period' => $title === 'Periode Aktif' ? 'active' : 'completed']) }}" class="silat-btn-secondary px-3 py-2 text-xs">
                            Lihat Semua
                        </a>
                    </div>

                    <div class="p-5">
                        @if ($items->isNotEmpty())
                            <div class="grid gap-4 lg:grid-cols-2">
                                @foreach ($items->take(4) as $summary)
                                    @php($enrollment = $summary['enrollment'])
                                    <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <h3 class="font-semibold text-gray-900">{{ $summary['student_name'] }}</h3>
                                                <p class="mt-1 text-sm text-gray-500">{{ $summary['student_npm'] }} · {{ $summary['study_program'] }}</p>
                                                <p class="mt-2 text-sm text-gray-700">{{ $summary['period_name'] }}</p>
                                            </div>
                                            <x-badge variant="{{ $summary['status'] === 'active' ? 'success' : 'neutral' }}">{{ $summary['status'] }}</x-badge>
                                        </div>

                                        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                                            <div class="rounded-md bg-gray-50 p-3">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Catatan</p>
                                                <p class="mt-1 font-semibold text-gray-900">{{ $summary['daily_validated'] }}/{{ $summary['daily_total'] }}</p>
                                                @if (($summary['daily_flagged'] ?? 0) > 0)
                                                    <p class="mt-1 text-xs font-semibold text-red-700">{{ number_format($summary['daily_flagged'], 0, ',', '.') }} bermasalah</p>
                                                @endif
                                            </div>
                                            <div class="rounded-md bg-gray-50 p-3">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kehadiran</p>
                                                <p class="mt-1 font-semibold text-gray-900">{{ $summary['attendance_score']['present_days'] }}/{{ $summary['attendance_score']['working_days'] }}</p>
                                            </div>
                                            <div class="rounded-md bg-gray-50 p-3">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nilai</p>
                                                <p class="mt-1 font-semibold text-gray-900">{{ $summary['has_assessment'] ? number_format((float) $summary['assessment_score'], 2, ',', '.') : '-' }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <a href="{{ route('field-supervisor.enrollments.show', ['enrollment' => $enrollment, 'tab' => 'daily']) }}" class="silat-btn-success px-3 py-2 text-xs">
                                                <x-icon name="fa-clipboard-check" />
                                                Validasi Catatan Harian
                                            </a>
                                            <a href="{{ route('field-supervisor.enrollments.show', ['enrollment' => $enrollment, 'tab' => 'assessment']) }}" class="silat-btn px-3 py-2 text-xs">
                                                <x-icon name="fa-star-half-stroke" />
                                                Penilaian
                                            </a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <x-empty-state title="Belum ada mahasiswa" description="Tidak ada mahasiswa pada kelompok ini." icon="fa-users" />
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
