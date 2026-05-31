<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Peserta Periode / Penempatan PKL') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

        <form class="mb-4 flex flex-wrap gap-3 bg-white p-4 shadow-sm sm:rounded-lg">
            <select name="period_id" class="rounded-md border-gray-300"><option value="">Semua periode</option>@foreach ($periods as $period)<option value="{{ $period->id }}" @selected($selectedPeriod === $period->id)>{{ $period->name }}</option>@endforeach</select>
            <select name="study_program_id" class="rounded-md border-gray-300"><option value="">Semua prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected($selectedStudyProgram === $program->id)>{{ $program->name }}</option>@endforeach</select>
            <x-primary-button>Filter</x-primary-button>
        </form>

        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.enrollments.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Peserta</h3>
                @include('management.enrollments.partials.fields', ['enrollment' => null])
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="bg-white shadow-sm sm:rounded-lg"><div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3">Mahasiswa</th><th class="px-4 py-3">Periode/Prodi</th><th class="px-4 py-3">Tempat</th><th class="px-4 py-3">Pembimbing</th><th class="px-4 py-3">Status</th><th></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">@foreach ($enrollments as $enrollment)<tr><td class="px-4 py-3">{{ $enrollment->student?->full_name }}<div class="text-xs text-gray-500">{{ $enrollment->student?->npm }}</div></td><td class="px-4 py-3">{{ $enrollment->internshipPeriod?->name }}<div class="text-xs text-gray-500">{{ $enrollment->studyProgram?->name }}</div></td><td class="px-4 py-3">{{ $enrollment->internshipPlace?->name ?: '-' }}</td><td class="px-4 py-3"><span class="font-medium text-gray-900">{{ $enrollment->lecturer?->name ?: $enrollment->lecturerSupervisor?->name ?: ($enrollment->lecturer_supervisor ?: '-') }}</span><div class="text-xs text-gray-500">{{ $enrollment->field_supervisor ?: '-' }}</div></td><td class="px-4 py-3">{{ $enrollment->status }}</td><td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('management.enrollments.edit', $enrollment) }}">Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><div class="p-4">{{ $enrollments->links() }}</div></div>
        </div>
    </div></div>
</x-app-layout>
