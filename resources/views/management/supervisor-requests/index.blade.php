<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Permohonan Perubahan Pembimbing') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        @include('management.partials.nav')
        @if (session('status'))<x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="danger" class="mb-4">{{ $errors->first() }}</x-alert>@endif

        <div class="silat-card overflow-hidden">
            <x-table-controls title="Daftar Permohonan Perubahan Pembimbing" description="Cari mahasiswa, dosen, pembimbing lapangan, atau alasan." search-placeholder="Cari permohonan...">
                <x-slot name="filters">
                    <div>
                        <x-input-label for="filter_status" value="Status" />
                        <select id="filter_status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">Semua status</option>
                            @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-slot>
            </x-table-controls>

            <div class="silat-table-wrap">
                <table class="silat-table">
                    <thead class="silat-table-head">
                        <tr>
                            <th class="silat-table-cell">Mahasiswa</th>
                            <th class="silat-table-cell">Periode/Prodi</th>
                            <th class="silat-table-cell">Dosen</th>
                            <th class="silat-table-cell">Lapangan</th>
                            <th class="silat-table-cell">Alasan</th>
                            <th class="silat-table-cell"><x-sortable-heading column="status" label="Status" /></th>
                            <th class="silat-table-cell text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            @php
                                $statusVariant = match($request->status) {'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'neutral'};
                                $defaultLecturerId = old('lecturer_supervisor_id', $request->requested_lecturer_supervisor_id ?: $request->enrollment?->lecturer_supervisor_id);
                                $defaultFieldSupervisor = old('field_supervisor', $request->requested_field_supervisor ?: $request->enrollment?->field_supervisor);
                                $defaultFieldSupervisorPhone = old('field_supervisor_phone', $request->requested_field_supervisor_phone ?: $request->enrollment?->field_supervisor_phone);
                                $defaultFieldSupervisorEmail = old('field_supervisor_email', $request->requested_field_supervisor_email ?: $request->enrollment?->field_supervisor_email);
                            @endphp
                            <tr>
                                <td class="silat-table-cell">
                                    <span class="font-medium text-gray-900">{{ $request->enrollment?->student?->full_name }}</span>
                                    <div class="text-xs text-gray-500">{{ $request->enrollment?->student?->npm }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    {{ $request->enrollment?->internshipPeriod?->display_name }}
                                    <div class="text-xs text-gray-500">{{ $request->enrollment?->studyProgram?->name }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    <div class="text-xs text-gray-500">Saat ini</div>
                                    <div class="font-medium text-gray-900">{{ $request->currentLecturer?->name ?: $request->enrollment?->lecturer?->name ?: '-' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">Usulan</div>
                                    <div class="text-gray-700">{{ $request->requestedLecturer?->name ?: 'Ditentukan reviewer' }}</div>
                                </td>
                                <td class="silat-table-cell">
                                    <div class="text-xs text-gray-500">Saat ini</div>
                                    <div class="font-medium text-gray-900">{{ $request->current_field_supervisor ?: '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $request->current_field_supervisor_email ?: '-' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">Usulan</div>
                                    <div class="text-gray-700">{{ $request->requested_field_supervisor ?: '-' }}{{ $request->requested_field_supervisor_phone ? ' - '.$request->requested_field_supervisor_phone : '' }}</div>
                                    <div class="text-xs text-gray-500">{{ $request->requested_field_supervisor_email ?: '-' }}</div>
                                </td>
                                <td class="silat-table-cell text-gray-600">{{ Str::limit($request->reason, 80) }}</td>
                                <td class="silat-table-cell">
                                    <x-badge :variant="$statusVariant">{{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$request->status] ?? Str::headline($request->status) }}</x-badge>
                                    @if($request->admin_note)<div class="mt-1 text-xs text-gray-500">{{ $request->admin_note }}</div>@endif
                                </td>
                                <td class="silat-table-cell">
                                    @if ($request->status === 'pending')
                                        <form method="POST" action="{{ route('management.supervisor-requests.update', $request) }}" class="w-72 space-y-2 text-left">
                                            @csrf @method('PATCH')
                                            <select name="lecturer_supervisor_id" class="w-full rounded-md border-gray-300 text-xs">
                                                <option value="">Pilih dosen pembimbing</option>
                                                @foreach ($lecturers as $lecturer)
                                                    <option value="{{ $lecturer->id }}" @selected((string) $defaultLecturerId === (string) $lecturer->id)>{{ $lecturer->name }}</option>
                                                @endforeach
                                            </select>
                                            <input name="field_supervisor" value="{{ $defaultFieldSupervisor }}" class="w-full rounded-md border-gray-300 text-xs" placeholder="Pembimbing lapangan">
                                            <input name="field_supervisor_phone" value="{{ $defaultFieldSupervisorPhone }}" class="w-full rounded-md border-gray-300 text-xs" placeholder="HP pembimbing lapangan">
                                            <input name="field_supervisor_email" value="{{ $defaultFieldSupervisorEmail }}" type="email" class="w-full rounded-md border-gray-300 text-xs" placeholder="Email pembimbing lapangan">
                                            <textarea name="admin_note" rows="2" class="w-full rounded-md border-gray-300 text-xs" placeholder="Catatan reviewer"></textarea>
                                            <div class="flex gap-2">
                                                <button name="status" value="approved" class="rounded-md bg-green-700 px-3 py-1 text-xs font-semibold text-white">Setujui</button>
                                                <button name="status" value="rejected" class="rounded-md bg-red-700 px-3 py-1 text-xs font-semibold text-white">Tolak</button>
                                            </div>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500">{{ $request->reviewer?->name ?: '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="silat-table-cell"><x-empty-state title="Belum ada permohonan perubahan pembimbing" icon="fa-user-pen" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-pagination :paginator="$requests" />
        </div>
    </div></div>
</x-app-layout>
