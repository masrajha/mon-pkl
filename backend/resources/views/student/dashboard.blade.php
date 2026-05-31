<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Mahasiswa PKL') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif

        <div class="grid gap-4 md:grid-cols-3">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <p class="text-sm text-gray-500">Profil</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ $student?->full_name ?: 'Belum lengkap' }}</p>
                <p class="text-sm text-gray-500">{{ $student?->npm ?: '-' }}</p>
                <a class="mt-4 inline-flex text-sm font-medium text-indigo-600" href="{{ route('student.profile.edit') }}">Lengkapi profil</a>
            </div>
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <p class="text-sm text-gray-500">Pendaftaran</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $enrollments->count() }}</p>
                <a class="mt-4 inline-flex text-sm font-medium text-indigo-600" href="{{ route('student.enrollments.create') }}">Daftar PKL</a>
            </div>
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <p class="text-sm text-gray-500">Usulan Tempat</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $proposals->count() }}</p>
                <a class="mt-4 inline-flex text-sm font-medium text-indigo-600" href="{{ route('student.proposals.create') }}">Ajukan tempat</a>
            </div>
        </div>

        <div class="mt-6 bg-white shadow-sm sm:rounded-lg"><div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3">Periode/Prodi</th><th class="px-4 py-3">Tempat</th><th class="px-4 py-3">Pembimbing</th><th class="px-4 py-3">Status</th><th></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td class="px-4 py-3">{{ $enrollment->internshipPeriod?->name }}<div class="text-xs text-gray-500">{{ $enrollment->studyProgram?->name }}</div></td>
                            <td class="px-4 py-3">{{ $enrollment->internshipPlace?->name ?: 'Belum ditempatkan' }}</td>
                            <td class="px-4 py-3">{{ $enrollment->lecturer?->name ?: 'Dosen belum ditentukan' }}<div class="text-xs text-gray-500">{{ $enrollment->field_supervisor ?: 'Pembimbing lapangan belum diisi' }}</div></td>
                            <td class="px-4 py-3">{{ $enrollment->status }}</td>
                            <td class="space-x-3 px-4 py-3 text-right">
                                @if ($enrollment->status === 'active')
                                    <a class="text-indigo-600" href="{{ route('check-ins.create') }}">Presensi</a>
                                @endif
                                <a class="text-indigo-600" href="{{ route('student.reports.show', $enrollment) }}">Laporan</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada pendaftaran PKL.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div></div>

        <div class="mt-6 bg-white shadow-sm sm:rounded-lg"><div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500"><tr><th class="px-4 py-3">Usulan Tempat</th><th class="px-4 py-3">Periode/Prodi</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Catatan</th></tr></thead>
                <tbody class="divide-y divide-gray-100">@forelse ($proposals as $proposal)<tr><td class="px-4 py-3">{{ $proposal->name }}</td><td class="px-4 py-3">{{ $proposal->internshipPeriod?->name }}<div class="text-xs text-gray-500">{{ $proposal->studyProgram?->name }}</div></td><td class="px-4 py-3">{{ $proposal->status }}</td><td class="px-4 py-3">{{ $proposal->admin_note ?: '-' }}</td></tr>@empty<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada usulan tempat PKL.</td></tr>@endforelse</tbody>
            </table>
        </div></div>
    </div></div>
</x-app-layout>
