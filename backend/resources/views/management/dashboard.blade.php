<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Manajemen') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @include('management.partials.nav')
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ([
                    'User' => $counts['users'],
                    'Mahasiswa' => $counts['students'],
                    'Dosen' => $counts['lecturers'],
                    'Prodi' => $counts['studyPrograms'],
                    'Periode' => $counts['periods'],
                    'Tempat PKL' => $counts['places'],
                    'Peserta Periode' => $counts['enrollments'],
                ] as $label => $count)
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($count, 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
