<form method="GET" class="flex flex-wrap items-end gap-3">
    @if (Auth::user()->hasRole(['admin', 'dosen']))
        <div>
            <x-input-label for="period_id" :value="__('Periode')" />
            <select id="period_id" name="period_id" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Semua periode') }}</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}" @selected((string) $selectedPeriod === (string) $period->id)>
                        {{ $period->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="study_program_id" :value="__('Prodi')" />
            <select id="study_program_id" name="study_program_id" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Semua prodi') }}</option>
                @foreach ($studyPrograms as $studyProgram)
                    <option value="{{ $studyProgram->id }}" @selected((string) $selectedStudyProgram === (string) $studyProgram->id)>
                        {{ $studyProgram->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <x-primary-button>{{ __('Terapkan') }}</x-primary-button>
    @else
        <p class="text-sm text-gray-600">{{ __('Data dibatasi untuk penempatan PKL Anda.') }}</p>
    @endif
</form>
