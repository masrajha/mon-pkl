<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Master Data</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Pembimbing Lapangan') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Diidentifikasi dari email pembimbing lapangan pada data peserta periode.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="silat-shell space-y-6">
            @include('management.partials.nav')

            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert variant="danger">{{ $errors->first() }}</x-alert>
            @endif

            <section class="silat-card p-5">
                <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <x-input-label for="q" value="Cari" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$selectedSearch" placeholder="Nama, email, mahasiswa, NPM, atau mitra" />
                    </div>
                    <x-primary-button>Filter</x-primary-button>
                </form>
            </section>

            <section class="space-y-4">
                @forelse ($fieldSupervisors as $fieldSupervisor)
                    @php
                        $user = $fieldSupervisor['user'];
                        $isFieldSupervisorUser = $user?->role === 'pembimbing_lapangan';
                    @endphp
                    <article class="silat-card overflow-hidden">
                        <div class="border-b border-gray-100 p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $fieldSupervisor['name'] }}</h3>
                                        <x-badge variant="{{ $isFieldSupervisorUser ? 'success' : 'neutral' }}">
                                            {{ $isFieldSupervisorUser ? 'Akun Login Aktif' : 'Belum ada akun login' }}
                                        </x-badge>
                                        @if ($fieldSupervisor['active_token_count'] > 0)
                                            <x-badge variant="info">{{ $fieldSupervisor['active_token_count'] }} token aktif</x-badge>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">{{ $fieldSupervisor['email'] }}</p>
                                    @if ($fieldSupervisor['phone'])
                                        <p class="text-sm text-gray-500">{{ $fieldSupervisor['phone'] }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if (auth()->user()?->hasRole('admin'))
                                        <form method="POST" action="{{ route('management.field-supervisors.create-account') }}">
                                            @csrf
                                            <input type="hidden" name="email" value="{{ $fieldSupervisor['email'] }}">
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                                <x-icon name="fa-user-plus" /> Buat/Hubungkan Akun
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="silat-table">
                                <thead class="silat-table-head">
                                    <tr>
                                        <th class="silat-table-cell">Mahasiswa</th>
                                        <th class="silat-table-cell">Program/Prodi</th>
                                        <th class="silat-table-cell">Mitra</th>
                                        <th class="silat-table-cell">Status Akses</th>
                                        <th class="silat-table-cell text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($fieldSupervisor['enrollments'] as $enrollment)
                                        @php
                                            $activeToken = $enrollment->fieldSupervisorAccessTokens
                                                ->first(fn ($token) => $token->revoked_at === null && $token->expires_at->isFuture());
                                        @endphp
                                        <tr>
                                            <td class="silat-table-cell">
                                                <div class="font-medium text-gray-900">{{ $enrollment->student?->full_name ?: '-' }}</div>
                                                <div class="text-xs text-gray-500">{{ $enrollment->student?->npm ?: '-' }}</div>
                                            </td>
                                            <td class="silat-table-cell">
                                                {{ $enrollment->internshipPeriod?->display_name ?: '-' }}
                                                <div class="text-xs text-gray-500">{{ $enrollment->studyProgram?->name ?: '-' }}</div>
                                            </td>
                                            <td class="silat-table-cell">{{ $enrollment->internshipPlace?->name ?: '-' }}</td>
                                            <td class="silat-table-cell">
                                                @if ($activeToken)
                                                    <x-badge variant="success">Token aktif</x-badge>
                                                    <div class="mt-1 text-xs text-gray-500">Berlaku sampai {{ $activeToken->expires_at->format('d/m/Y H:i') }}</div>
                                                @else
                                                    <x-badge variant="neutral">Tidak ada token aktif</x-badge>
                                                @endif
                                            </td>
                                            <td class="silat-table-cell">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    <form method="POST" action="{{ route('management.enrollments.field-supervisor-access.store', $enrollment) }}">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                                            <x-icon name="fa-paper-plane" /> Kirim Token
                                                        </button>
                                                    </form>
                                                    @if ($activeToken)
                                                        <form method="POST" action="{{ route('management.enrollments.field-supervisor-access.destroy', $enrollment) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                                <x-icon name="fa-ban" /> Cabut
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>
                @empty
                    <section class="silat-card p-5">
                        <x-empty-state title="Belum ada pembimbing lapangan" description="Isi email pembimbing lapangan pada peserta periode agar muncul di sini." icon="fa-user-check" />
                    </section>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
