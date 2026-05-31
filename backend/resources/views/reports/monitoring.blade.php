<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Rekapitulasi Monitoring PKL') }}
            </h2>

            <form method="GET" class="grid gap-3 rounded-md border border-gray-200 bg-white p-4 md:grid-cols-3 lg:grid-cols-6">
                @if (Auth::user()->hasRole(['admin', 'dosen']))
                    <div>
                        <x-input-label for="period_id" :value="__('Periode')" />
                        <select id="period_id" name="period_id" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Semua') }}</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected((string) $selectedPeriod === (string) $period->id)>{{ $period->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="study_program_id" :value="__('Prodi')" />
                        <select id="study_program_id" name="study_program_id" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Semua') }}</option>
                            @foreach ($studyPrograms as $studyProgram)
                                <option value="{{ $studyProgram->id }}" @selected((string) $selectedStudyProgram === (string) $studyProgram->id)>{{ $studyProgram->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <x-input-label for="start_date" :value="__('Dari')" />
                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="$startDate" />
                </div>

                <div>
                    <x-input-label for="end_date" :value="__('Sampai')" />
                    <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="$endDate" />
                </div>

                <div class="space-y-2">
                    <x-input-label :value="__('Termasuk')" />
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="include_saturday" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($includeSaturday)>
                        {{ __('Sabtu') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="include_sunday" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($includeSunday)>
                        {{ __('Minggu') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="include_holidays" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($includeHolidays)>
                        {{ __('Libur') }}
                    </label>
                </div>

                <div class="flex items-end">
                    <x-primary-button>{{ __('Lihat') }}</x-primary-button>
                </div>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">{{ __('Mahasiswa') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($totals['students'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">{{ __('Total Hari Hadir') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($totals['attendance_days'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">{{ __('Total Durasi') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($totals['duration_hours'], 2, ',', '.') }} {{ __('jam') }}</p>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                            <tr>
                                <th class="px-4 py-3">{{ __('Mahasiswa') }}</th>
                                <th class="px-4 py-3">{{ __('NPM') }}</th>
                                <th class="px-4 py-3">{{ __('Email') }}</th>
                                <th class="px-4 py-3">{{ __('Tempat') }}</th>
                                <th class="px-4 py-3">{{ __('Hari') }}</th>
                                <th class="px-4 py-3">{{ __('Check-in') }}</th>
                                <th class="px-4 py-3">{{ __('Rata-rata Jarak') }}</th>
                                <th class="px-4 py-3">{{ __('Durasi') }}</th>
                                <th class="px-4 py-3">{{ __('Jam Masuk') }}</th>
                                <th class="px-4 py-3">{{ __('Jam Pulang') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white text-gray-700">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($row['photo_url'])
                                                <img src="{{ $row['photo_url'] }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                            @else
                                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-500">
                                                    {{ Str::of($row['name'])->substr(0, 1)->upper() }}
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $row['name'] }}</p>
                                                <p class="text-xs text-gray-500">{{ $row['study_program'] }} / {{ $row['period'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $row['npm'] }}</td>
                                    <td class="px-4 py-3">{{ $row['email'] }}</td>
                                    <td class="px-4 py-3">{{ $row['place'] }}</td>
                                    <td class="px-4 py-3">{{ $row['attendance_days'] }}</td>
                                    <td class="px-4 py-3">{{ $row['check_ins_count'] }}</td>
                                    <td class="px-4 py-3">
                                        @if ($row['average_distance_meters'] === null)
                                            -
                                        @else
                                            {{ number_format($row['average_distance_meters'], 2, ',', '.') }} m
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ number_format($row['duration_hours'], 2, ',', '.') }} jam</td>
                                    <td class="px-4 py-3">{{ $row['min_check_in'] }} - {{ $row['max_check_in'] }}</td>
                                    <td class="px-4 py-3">{{ $row['min_check_out'] }} - {{ $row['max_check_out'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-gray-500" colspan="10">{{ __('Tidak ada data pada filter ini.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
