<form method="GET" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-6">
    <div>
        <x-input-label for="period_id" :value="__('Periode Program')" />
        <x-select-input id="period_id" name="period_id" class="mt-1 text-sm">
            <option value="">{{ __('Semua periode program') }}</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected((string) $selectedPeriod === (string) $period->id)>
                    {{ $period->display_name }}
                </option>
            @endforeach
        </x-select-input>
    </div>

    @if (Auth::user()->hasRole(['admin', 'dosen', 'koordinator']))
        <div>
            <x-input-label for="study_program_id" :value="__('Prodi')" />
            <x-select-input id="study_program_id" name="study_program_id" class="mt-1 text-sm">
                <option value="">{{ __('Semua prodi') }}</option>
                @foreach ($studyPrograms as $studyProgram)
                    <option value="{{ $studyProgram->id }}" @selected((string) $selectedStudyProgram === (string) $studyProgram->id)>
                        {{ $studyProgram->name }}
                    </option>
                @endforeach
            </x-select-input>
        </div>
    @endif

    @if ($showCityFilter ?? false)
        <div>
            <x-input-label for="city_id" :value="__('Kab/Kota')" />
            <x-select-input id="city_id" name="city_id" class="mt-1 text-sm">
                <option value="">{{ __('Semua kab/kota') }}</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected((string) $selectedCity === (string) $city->id)>
                        {{ $city->name }}
                    </option>
                @endforeach
            </x-select-input>
        </div>
    @endif

    @if ($showCoordinatorAllStudyPrograms ?? false)
        <div class="flex items-end">
            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                <input type="checkbox" name="all_study_programs" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" @checked($selectedAllStudyPrograms ?? false)>
                {{ __('Tampilkan semua prodi') }}
            </label>
        </div>
    @endif

    @if ($showDateFilters ?? false)
        <div>
            <x-input-label for="start_date" :value="__('Dari')" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full text-sm" :value="$startDate" />
        </div>

        <div>
            <x-input-label for="end_date" :value="__('Sampai')" />
            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full text-sm" :value="$endDate" />
        </div>

        <div class="flex items-end">
            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                <input type="checkbox" name="today_only" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" @checked($todayOnly)>
                {{ __('Hari Ini') }}
            </label>
        </div>
    @endif

    <div class="flex items-end">
        <x-primary-button><x-icon name="fa-filter" /> {{ __('Terapkan') }}</x-primary-button>
    </div>
</form>
