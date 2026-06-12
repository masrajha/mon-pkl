@php
    $reportTabs = [
        ['route' => 'reports.progress-funnel', 'label' => 'Progress Funnel', 'icon' => 'fa-chart-simple'],
        ['route' => 'reports.risk-scoring', 'label' => 'Risk Scoring', 'icon' => 'fa-triangle-exclamation'],
        ['route' => 'reports.attendance-heatmap', 'label' => 'Heatmap Kehadiran', 'icon' => 'fa-table-cells'],
        ['route' => 'reports.operational-charts', 'label' => 'Grafik Operasional', 'icon' => 'fa-chart-pie'],
        ['route' => 'reports.sanctions', 'label' => 'Rekap Sanksi', 'icon' => 'fa-scale-balanced'],
        ['route' => 'reports.final-scores', 'label' => 'Rekap Nilai Akhir', 'icon' => 'fa-calculator'],
        ['route' => 'reports.monitoring', 'label' => 'Rekap Monitoring', 'icon' => 'fa-chart-column'],
        ['route' => 'reports.drill-down', 'label' => 'Drill-down', 'icon' => 'fa-magnifying-glass-chart'],
    ];
    $sharedReportQuery = request()->only(['period_id', 'program_id', 'study_program_id', 'start_date', 'end_date']);
@endphp

<nav class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm" aria-label="Navigasi laporan">
    <div class="flex flex-wrap gap-2" role="tablist">
        @foreach ($reportTabs as $tab)
            @php($active = request()->routeIs($tab['route']))
            <a
                href="{{ route($tab['route'], array_filter($sharedReportQuery, fn ($value) => filled($value))) }}"
                role="tab"
                aria-selected="{{ $active ? 'true' : 'false' }}"
                class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-semibold {{ $active ? 'border-blue-700 bg-blue-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
            >
                <x-icon :name="$tab['icon']" />
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
