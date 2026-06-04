@php($isAdmin = Auth::user()?->hasRole('admin'))

<div class="mb-6 flex flex-wrap gap-2 text-sm">
    @if ($isAdmin)
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.dashboard') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.dashboard') }}">Ringkasan</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.users.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.users.index') }}">User</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.students.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.students.index') }}">Mahasiswa</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.lecturers.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.lecturers.index') }}">Dosen</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.coordinators.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.coordinators.index') }}">Koordinator</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.study-programs.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.study-programs.index') }}">Prodi</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.programs.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.programs.index') }}">Program</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.periods.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.periods.index') }}">Periode Program</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.places.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.places.index') }}">Mitra</a>
    @endif
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.place-proposals.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.place-proposals.index') }}">Usulan Mitra</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.relocations.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.relocations.index') }}">Pindah Mitra</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.supervisor-requests.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.supervisor-requests.index') }}">Perubahan Pembimbing</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.orientation-events.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.orientation-events.index') }}">Pembekalan</a>
    @if ($isAdmin)
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.enrollments.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.enrollments.index') }}">Peserta Periode</a>
    @endif
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.enrollment-validations.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.enrollment-validations.index') }}">Validasi Pendaftaran</a>
    @if ($isAdmin)
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('system-configurations.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('system-configurations.index') }}">Konfigurasi</a>
    @endif
</div>
