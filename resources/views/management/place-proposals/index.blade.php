<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Validasi Usulan Mitra') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <x-table-controls class="silat-card mb-4" title="Daftar Usulan Mitra" description="Cari usulan berdasarkan nama mitra, alamat, kota, atau mahasiswa." search-placeholder="Cari usulan mitra...">
            <x-slot name="filters">
                <div>
                    <x-input-label for="filter_status" value="Status" />
                    <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua status</option>@foreach (['pending','approved','merged','rejected'] as $status)<option value="{{ $status }}" @selected($selectedStatus === $status)>{{ Str::headline($status) }}</option>@endforeach</select>
                </div>
            </x-slot>
        </x-table-controls>
        <div class="space-y-4">
            @forelse ($proposals as $proposal)
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <div class="flex flex-wrap justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $proposal->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $proposal->student?->full_name }} · {{ $proposal->internshipPeriod?->display_name }} · {{ $proposal->studyProgram?->name }}</p>
                            <p class="mt-2 text-sm text-gray-700">{{ $proposal->address ?: '-' }}</p>
                            <p class="text-xs text-gray-500">{{ $proposal->city_name ?: $proposal->city?->name ?: '-' }} · {{ $proposal->latitude }}, {{ $proposal->longitude }}</p>
                        </div>
                        <div class="text-sm font-semibold text-gray-700">{{ $proposal->status }}</div>
                    </div>
                    @if ($proposal->status === 'pending')
                        <div class="mt-4 grid gap-3 lg:grid-cols-2">
                            <form method="POST" action="{{ route('management.place-proposals.approve', $proposal) }}" class="space-y-3 rounded-md border p-4">
                                @csrf
                                <select name="mode" class="block w-full rounded-md border-gray-300"><option value="new">Jadikan master baru</option><option value="merge">Gabungkan ke master</option></select>
                                <select name="internship_place_id" class="block w-full rounded-md border-gray-300"><option value="">Pilih master jika merge</option>@foreach ($places as $place)<option value="{{ $place->id }}">{{ $place->name }}</option>@endforeach</select>
                                <textarea name="admin_note" rows="2" class="block w-full rounded-md border-gray-300" placeholder="Catatan admin"></textarea>
                                <x-primary-button>Setujui</x-primary-button>
                            </form>
                            <form method="POST" action="{{ route('management.place-proposals.reject', $proposal) }}" class="space-y-3 rounded-md border p-4">
                                @csrf
                                <textarea name="admin_note" rows="4" class="block w-full rounded-md border-gray-300" placeholder="Alasan penolakan" required></textarea>
                                <x-danger-button>Tolak</x-danger-button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white p-6 text-center text-sm text-gray-500 shadow-sm sm:rounded-lg">Belum ada usulan mitra.</div>
            @endforelse
        </div>
        <x-table-pagination :paginator="$proposals" class="mt-4 rounded-lg border bg-white shadow-sm" />
    </div></div>
</x-app-layout>
