<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Koordinator PKL') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <h3 class="font-semibold text-gray-900">Penugasan Koordinator</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Prodi</th><th class="px-4 py-3">Monitoring</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($assignments as $assignment)
                            <tr>
                                <td class="px-4 py-3">{{ $assignment->internshipPeriod?->name }}</td>
                                <td class="px-4 py-3">{{ $assignment->studyProgram?->name }}</td>
                                <td class="space-x-3 px-4 py-3">
                                    <a class="text-indigo-600" href="{{ route('maps.monitoring', ['period_id' => $assignment->internship_period_id, 'study_program_id' => $assignment->study_program_id]) }}">Peta</a>
                                    <a class="text-indigo-600" href="{{ route('reports.monitoring', ['period_id' => $assignment->internship_period_id, 'study_program_id' => $assignment->study_program_id]) }}">Rekap</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Belum ada penugasan koordinator aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div></div>
</x-app-layout>
