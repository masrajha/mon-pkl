<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Peserta Periode / Penempatan Program') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

        <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form method="POST" action="{{ route('management.enrollments.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <h3 class="font-semibold text-gray-900">Tambah Peserta</h3>
                @include('management.enrollments.partials.fields', ['enrollment' => null])
                <x-primary-button>Simpan</x-primary-button>
            </form>
            <div class="silat-card overflow-hidden">
                <x-table-controls title="Daftar Peserta Periode" description="Cari mahasiswa, NPM, mitra, atau pembimbing." search-placeholder="Cari peserta...">
                    <x-slot name="filters">
                        <div><x-input-label value="Periode Program" /><select name="period_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua periode program</option>@foreach ($periods as $period)<option value="{{ $period->id }}" @selected($selectedPeriod === $period->id)>{{ $period->display_name }}</option>@endforeach</select></div>
                        <div class="mt-3"><x-input-label value="Prodi" /><select name="study_program_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua prodi</option>@foreach ($studyPrograms as $program)<option value="{{ $program->id }}" @selected($selectedStudyProgram === $program->id)>{{ $program->name }}</option>@endforeach</select></div>
                        <div class="mt-3"><x-input-label value="Status" /><select name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Semua status</option>@foreach (['draft','pending_verification','revision_required','active','inactive','completed','cancelled','rejected'] as $status)<option value="{{ $status }}" @selected($selectedStatus === $status)>{{ Str::headline($status) }}</option>@endforeach</select></div>
                    </x-slot>
                </x-table-controls>
                <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head"><tr><th class="silat-table-cell">Mahasiswa</th><th class="silat-table-cell">Periode/Prodi</th><th class="silat-table-cell">Mitra</th><th class="silat-table-cell">Pembimbing</th><th class="silat-table-cell"><x-sortable-heading column="status" label="Status" /></th><th class="silat-table-cell text-right">Aksi</th></tr></thead>
                    <tbody>@foreach ($enrollments as $enrollment)<tr><td class="silat-table-cell"><span class="font-medium text-gray-900">{{ $enrollment->student?->full_name }}</span><div class="text-xs text-gray-500">{{ $enrollment->student?->npm }}</div></td><td class="silat-table-cell">{{ $enrollment->internshipPeriod?->display_name }}<div class="text-xs text-gray-500">{{ $enrollment->studyProgram?->name }}</div></td><td class="silat-table-cell text-gray-600">{{ $enrollment->internshipPlace?->name ?: '-' }}</td><td class="silat-table-cell"><span class="font-medium text-gray-900">{{ $enrollment->lecturer?->name ?: $enrollment->lecturerSupervisor?->name ?: ($enrollment->lecturer_supervisor ?: '-') }}</span><div class="text-xs text-gray-500">{{ $enrollment->field_supervisor ?: '-' }}</div><div class="text-xs text-gray-500">{{ $enrollment->field_supervisor_email ?: '-' }}</div></td><td class="silat-table-cell">@php $statusVariant = match($enrollment->status) {'active' => 'success', 'pending_verification', 'draft' => 'warning', 'completed' => 'info', 'revision_required' => 'warning', 'cancelled', 'rejected' => 'danger', default => 'neutral'}; @endphp<x-badge :variant="$statusVariant">{{ Str::headline($enrollment->status) }}</x-badge>@if($enrollment->admin_note)<div class="mt-1 text-xs text-amber-700">{{ Str::limit($enrollment->admin_note, 60) }}</div>@endif</td><td class="silat-table-cell text-right"><a class="silat-secondary-link justify-end" href="{{ route('management.enrollments.edit', ['enrollment' => $enrollment] + request()->only(['q', 'period_id', 'study_program_id', 'status', 'page', 'per_page', 'sort', 'direction'])) }}"><x-icon name="fa-clipboard-check" class="mr-1" /> Validasi/Edit</a></td></tr>@endforeach</tbody>
                </table>
            </div><x-table-pagination :paginator="$enrollments" /></div>
        </div>
    </div></div>
</x-app-layout>
