<div class="mb-6 flex flex-wrap gap-2 text-sm">
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.dashboard') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.dashboard') }}">Ringkasan</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.users.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.users.index') }}">User</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.students.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.students.index') }}">Mahasiswa</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.lecturers.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.lecturers.index') }}">Dosen</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.coordinators.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.coordinators.index') }}">Koordinator</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.study-programs.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.study-programs.index') }}">Prodi</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.periods.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.periods.index') }}">Periode</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.places.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.places.index') }}">Tempat PKL</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.place-proposals.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.place-proposals.index') }}">Usulan Tempat</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('management.enrollments.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('management.enrollments.index') }}">Peserta Periode</a>
    <a class="rounded-md border px-3 py-2 {{ request()->routeIs('system-configurations.*') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}" href="{{ route('system-configurations.index') }}">Konfigurasi</a>
</div>
