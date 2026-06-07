<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Workflow Akademik</p>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ __('Review Seminar') }}</h2>
            <p class="mt-1 text-sm text-gray-500">ACC seminar via sistem, validasi berkas ACC manual, jadwalkan, dan isi nilai seminar.</p>
        </div>
    </x-slot>

    @php
        $statusVariants = [
            'lecturer_approved' => 'success',
            'manual_acc_approved' => 'success',
            'scheduled' => 'info',
            'completed' => 'success',
            'waiting_lecturer_approval' => 'warning',
            'waiting_manual_acc_validation' => 'warning',
            'waiting_assessment_validation' => 'warning',
            'assessment_revision_required' => 'warning',
            'revision_required' => 'warning',
            'rejected' => 'danger',
            'cancelled' => 'neutral',
        ];
    @endphp

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
                <form method="GET" class="grid gap-4 md:grid-cols-[1fr_260px_260px_auto]">
                    <div>
                        <x-input-label for="q" value="Cari" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Mahasiswa, NPM, judul, atau mitra" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full">
                            <option value="">Semua status</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="period_id" value="Periode Program" />
                        <x-select-input id="period_id" name="period_id" class="mt-1 block w-full">
                            <option value="">Semua periode</option>
                            @foreach ($periodOptions as $period)
                                <option value="{{ $period->id }}" @selected((int) $selectedPeriodId === (int) $period->id)>{{ $period->display_name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="flex items-end">
                        <x-primary-button>Filter</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="silat-card">
                <div class="overflow-x-auto">
                    <table class="silat-table">
                        <thead class="silat-table-head">
                            <tr>
                                <th class="silat-table-cell">Mahasiswa</th>
                                <th class="silat-table-cell">Seminar</th>
                                <th class="silat-table-cell">ACC</th>
                                <th class="silat-table-cell">Status</th>
                                <th class="silat-table-cell">Jadwal</th>
                                <th class="silat-table-cell">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($seminarRequests as $seminarRequest)
                                @php
                                    $enrollment = $seminarRequest->enrollment;
                                    $canLecturerAct = auth()->user()?->hasRole('dosen') && (int) $enrollment?->lecturer_supervisor_user_id === (int) auth()->id();
                                    $canSchedule = auth()->user()?->hasRole(['admin', 'koordinator']);
                                    $isFinalized = (bool) $enrollment?->finalAssessment;
                                @endphp
                                <tr>
                                    <td class="silat-table-cell">
                                        <div class="font-semibold text-gray-900">{{ $enrollment?->student?->full_name ?: '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $enrollment?->student?->npm ?: '-' }} · {{ $enrollment?->studyProgram?->name ?: '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $enrollment?->internshipPlace?->name ?: '-' }}</div>
                                    </td>
                                    <td class="silat-table-cell">
                                        <div class="font-medium text-gray-900">{{ $seminarRequest->title }}</div>
                                        <div class="text-xs text-gray-500">{{ $enrollment?->internshipPeriod?->display_name ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-gray-500">Usulan: {{ $seminarRequest->proposed_date?->format('d/m/Y') ?: '-' }} {{ $seminarRequest->proposed_time ? substr((string) $seminarRequest->proposed_time, 0, 5) : '' }}</div>
                                        @if ($seminarRequest->student_note)
                                            <div class="mt-1 text-xs text-gray-500">{{ Str::limit($seminarRequest->student_note, 100) }}</div>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell">
                                        <div>{{ $seminarRequest->approval_method === 'manual_upload' ? 'Berkas ACC' : 'Sistem' }}</div>
                                        <div class="mt-1 space-x-2">
                                            @if ($seminarRequest->seminar_document_path)
                                                <a class="silat-secondary-link text-xs" href="{{ route('seminar-requests.file', [$seminarRequest, 'document']) }}" target="_blank">Dokumen</a>
                                            @endif
                                            @if ($seminarRequest->manual_acc_path)
                                                <a class="silat-secondary-link text-xs" href="{{ route('seminar-requests.file', [$seminarRequest, 'manual-acc']) }}" target="_blank">ACC</a>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="silat-table-cell">
                                        <x-badge :variant="$statusVariants[$seminarRequest->status] ?? 'neutral'">{{ $statusLabels[$seminarRequest->status] ?? Str::headline($seminarRequest->status) }}</x-badge>
                                        @if ($seminarRequest->lecturer_note)
                                            <div class="mt-1 text-xs text-amber-700">{{ $seminarRequest->lecturer_note }}</div>
                                        @endif
                                        @if ($seminarRequest->admin_note)
                                            <div class="mt-1 text-xs text-gray-500">{{ $seminarRequest->admin_note }}</div>
                                        @endif
                                        @if ($seminarRequest->seminar_score !== null)
                                            <div class="mt-1 text-xs text-green-700">Nilai: {{ number_format((float) $seminarRequest->seminar_score, 2, ',', '.') }}</div>
                                            @if ($seminarRequest->assessment_method)
                                                <div class="mt-1 text-xs text-gray-500">Penilaian: {{ $seminarRequest->assessment_method === 'manual' ? 'Manual' : 'Sistem' }}</div>
                                            @endif
                                            @if ($seminarRequest->assessment_file_path)
                                                <a class="mt-1 block silat-secondary-link text-xs" href="{{ route('seminar-requests.file', [$seminarRequest, 'assessment']) }}" target="_blank">Form penilaian</a>
                                            @endif
                                        @endif
                                        @if ($isFinalized)
                                            <div class="mt-1 text-xs text-blue-700">Terkunci karena nilai akhir sudah difinalisasi.</div>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell">
                                        {{ $seminarRequest->scheduled_at?->format('d/m/Y H:i') ?: '-' }}
                                        <div class="text-xs text-gray-500">{{ Str::headline($seminarRequest->mode) }}</div>
                                        @if ($seminarRequest->location)
                                            <div class="text-xs text-gray-500">{{ $seminarRequest->location }}</div>
                                        @endif
                                        @if ($seminarRequest->meeting_url)
                                            <a class="silat-secondary-link text-xs" href="{{ $seminarRequest->meeting_url }}" target="_blank">Link meeting</a>
                                        @endif
                                    </td>
                                    <td class="silat-table-cell min-w-[360px]">
                                        <div class="space-y-4">
                                            @if ($canLecturerAct && $seminarRequest->status === 'waiting_lecturer_approval')
                                                <form method="POST" action="{{ route('management.seminar-requests.lecturer-decision', $seminarRequest) }}" class="space-y-2 rounded-lg border border-gray-200 p-3">
                                                    @csrf @method('PATCH')
                                                    <x-select-input name="decision" class="block w-full" required>
                                                        <option value="approve">Setujui ACC Seminar</option>
                                                        <option value="revision_required">Minta Revisi</option>
                                                        <option value="rejected">Tolak</option>
                                                    </x-select-input>
                                                    <x-textarea-input name="lecturer_note" rows="2" class="block w-full text-sm" placeholder="Catatan dosen"></x-textarea-input>
                                                    <x-primary-button>Simpan ACC</x-primary-button>
                                                </form>
                                            @endif

                                            @if ($canSchedule && $seminarRequest->status === 'waiting_manual_acc_validation')
                                                <form method="POST" action="{{ route('management.seminar-requests.manual-acc', $seminarRequest) }}" class="space-y-2 rounded-lg border border-gray-200 p-3">
                                                    @csrf @method('PATCH')
                                                    <x-select-input name="decision" class="block w-full" required>
                                                        <option value="approve">Validasi ACC Manual</option>
                                                        <option value="revision_required">Minta Revisi Bukti</option>
                                                        <option value="rejected">Tolak</option>
                                                    </x-select-input>
                                                    <x-textarea-input name="admin_note" rows="2" class="block w-full text-sm" placeholder="Catatan validator"></x-textarea-input>
                                                    <x-primary-button>Simpan Validasi</x-primary-button>
                                                </form>
                                            @endif

                                            @if ($canSchedule && in_array($seminarRequest->status, ['lecturer_approved', 'manual_acc_approved', 'scheduled'], true))
                                                @php
                                                    $defaultScheduledAt = $seminarRequest->scheduled_at?->format('Y-m-d\TH:i');

                                                    if (! $defaultScheduledAt && $seminarRequest->proposed_date) {
                                                        $proposedTime = $seminarRequest->proposed_time
                                                            ? substr((string) $seminarRequest->proposed_time, 0, 5)
                                                            : '08:00';
                                                        $defaultScheduledAt = $seminarRequest->proposed_date->format('Y-m-d').'T'.$proposedTime;
                                                    }
                                                @endphp
                                                <form method="POST" action="{{ route('management.seminar-requests.schedule', $seminarRequest) }}" class="space-y-2 rounded-lg border border-gray-200 p-3">
                                                    @csrf @method('PATCH')
                                                    <x-input-label value="Jadwal Final" />
                                                    <x-text-input name="scheduled_at" type="datetime-local" class="block w-full text-sm" :value="old('scheduled_at', $defaultScheduledAt)" required />
                                                    <x-select-input name="mode" class="block w-full text-sm" required>
                                                        @foreach (['offline' => 'Offline', 'online' => 'Online', 'hybrid' => 'Hybrid'] as $value => $label)
                                                            <option value="{{ $value }}" @selected($seminarRequest->mode === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </x-select-input>
                                                    <x-text-input name="location" class="block w-full text-sm" :value="$seminarRequest->location" placeholder="Lokasi / ruang" />
                                                    <x-text-input name="meeting_url" type="url" class="block w-full text-sm" :value="$seminarRequest->meeting_url" placeholder="Link meeting" />
                                                    <x-textarea-input name="admin_note" rows="2" class="block w-full text-sm" placeholder="Catatan jadwal">{{ $seminarRequest->admin_note }}</x-textarea-input>
                                                    <x-primary-button>Simpan Jadwal</x-primary-button>
                                                </form>
                                            @endif

                                            @if ($isFinalized)
                                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900">
                                                    Nilai dosen sudah terkunci karena nilai akhir telah difinalisasi.
                                                </div>
                                            @endif

                                            @if (! $isFinalized && $canSchedule && $seminarRequest->status === 'waiting_assessment_validation')
                                                <form method="POST" action="{{ route('management.seminar-requests.manual-assessment', $seminarRequest) }}" class="space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                                    @csrf @method('PATCH')
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-900">Validasi Nilai Manual</p>
                                                        <p class="mt-1 text-xs text-gray-600">Cocokkan komponen nilai dengan bukti form penilaian yang diunggah mahasiswa.</p>
                                                    </div>
                                                    <div class="overflow-x-auto rounded-lg border border-amber-100 bg-white">
                                                        <table class="min-w-[560px] w-full text-xs">
                                                            <thead class="bg-gray-50 text-left uppercase text-gray-500">
                                                                <tr>
                                                                    <th class="px-3 py-2">Aspek</th>
                                                                    <th class="px-3 py-2 w-24">Nilai</th>
                                                                    <th class="px-3 py-2 w-24">Bobot</th>
                                                                    <th class="px-3 py-2 w-24">NA</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100">
                                                                @php
                                                                    $storedScores = $seminarRequest->assessment_scores ?? [];
                                                                    $rowSeminarRubric = $seminarRequest->assessment_rubric_snapshot
                                                                        ?: data_get($seminarRubricsByPeriod ?? [], $seminarRequest->enrollment?->internship_period_id, $seminarRubric);
                                                                @endphp
                                                                @foreach ($rowSeminarRubric as $key => $item)
                                                                    @php
                                                                        $scoreValue = data_get($storedScores, $key.'.score');
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="px-3 py-2 text-gray-700">{{ $item['label'] }}</td>
                                                                        <td class="px-3 py-2">{{ $scoreValue !== null ? number_format((float) $scoreValue, 2, ',', '.') : '-' }}</td>
                                                                        <td class="px-3 py-2">{{ $item['weight'] }}%</td>
                                                                        <td class="px-3 py-2">{{ data_get($storedScores, $key.'.weighted_score') !== null ? number_format((float) data_get($storedScores, $key.'.weighted_score'), 2, ',', '.') : '-' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                                <tr class="bg-gray-50 font-semibold text-gray-900">
                                                                    <td class="px-3 py-2">Nilai Total</td>
                                                                    <td class="px-3 py-2"></td>
                                                                    <td class="px-3 py-2">100%</td>
                                                                    <td class="px-3 py-2">{{ $seminarRequest->seminar_score !== null ? number_format((float) $seminarRequest->seminar_score, 2, ',', '.') : '-' }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    @if ($seminarRequest->assessment_file_path)
                                                        <a class="silat-secondary-link text-xs" href="{{ route('seminar-requests.file', [$seminarRequest, 'assessment']) }}" target="_blank">Buka bukti form penilaian</a>
                                                    @endif
                                                    <x-select-input name="decision" class="block w-full text-sm" required>
                                                        <option value="approve">Validasi Nilai Manual</option>
                                                        <option value="revision_required">Minta Revisi Nilai/Bukti</option>
                                                        <option value="rejected">Tolak</option>
                                                    </x-select-input>
                                                    <x-textarea-input name="admin_note" rows="2" class="block w-full text-sm" placeholder="Catatan validator">{{ $seminarRequest->admin_note }}</x-textarea-input>
                                                    <x-primary-button>Simpan Validasi Nilai</x-primary-button>
                                                </form>
                                            @endif

                                            @if (! $isFinalized && $canLecturerAct && ($seminarRequest->status === 'scheduled' || ($seminarRequest->status === 'completed' && $seminarRequest->assessment_method === 'system')))
                                                @php
                                                    $storedScores = $seminarRequest->assessment_scores ?? [];
                                                    $rowSeminarRubric = $seminarRequest->assessment_rubric_snapshot
                                                        ?: data_get($seminarRubricsByPeriod ?? [], $seminarRequest->enrollment?->internship_period_id, $seminarRubric);
                                                @endphp
                                                <form method="POST" action="{{ route('management.seminar-requests.score', $seminarRequest) }}" class="space-y-3 rounded-lg border border-gray-200 p-3">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="assessment_method" value="system">
                                                    <x-input-label value="Penilaian Seminar via Sistem" />

                                                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                                                        <table class="min-w-[680px] w-full text-xs">
                                                            <thead class="bg-gray-50 text-left uppercase text-gray-500">
                                                                <tr>
                                                                    <th class="px-3 py-2">Aspek yang dinilai</th>
                                                                    <th class="px-3 py-2 w-28">Nilai</th>
                                                                    <th class="px-3 py-2 w-24">Persentase</th>
                                                                    <th class="px-3 py-2 w-24">NA</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100">
                                                                @php
                                                                    $currentGroup = null;
                                                                @endphp
                                                                @foreach ($rowSeminarRubric as $key => $item)
                                                                    @if ($currentGroup !== $item['group'])
                                                                        @php
                                                                            $currentGroup = $item['group'];
                                                                        @endphp
                                                                        <tr class="bg-white">
                                                                            <td class="px-3 py-2 font-semibold text-gray-900">{{ $loop->iteration <= 3 ? '1.' : '2.' }} {{ $currentGroup }}</td>
                                                                            <td class="px-3 py-2"></td>
                                                                            <td class="px-3 py-2"></td>
                                                                            <td class="px-3 py-2"></td>
                                                                        </tr>
                                                                    @endif
                                                                    @php
                                                                        $scoreValue = old('assessment_scores.'.$key, data_get($storedScores, $key.'.score'));
                                                                        $weightedValue = $scoreValue !== null && $scoreValue !== '' ? ((float) $scoreValue * $item['weight'] / 100) : null;
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="px-3 py-2 text-gray-700">{{ chr(96 + (($loop->iteration - 1) % 3) + 1) }}. {{ $item['label'] }}</td>
                                                                        <td class="px-3 py-2">
                                                                            <x-text-input name="assessment_scores[{{ $key }}]" type="number" min="0" max="100" step="0.01" class="block w-24 text-xs" :value="$scoreValue" required />
                                                                        </td>
                                                                        <td class="px-3 py-2">{{ $item['weight'] }}%</td>
                                                                        <td class="px-3 py-2">{{ $weightedValue !== null ? number_format($weightedValue, 2, ',', '.') : '-' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                                <tr class="bg-gray-50 font-semibold text-gray-900">
                                                                    <td class="px-3 py-2">Nilai Total</td>
                                                                    <td class="px-3 py-2"></td>
                                                                    <td class="px-3 py-2">100%</td>
                                                                    <td class="px-3 py-2">{{ $seminarRequest->assessment_method === 'system' && $seminarRequest->seminar_score !== null ? number_format((float) $seminarRequest->seminar_score, 2, ',', '.') : '-' }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <x-textarea-input name="seminar_score_note" rows="2" class="block w-full text-sm" placeholder="Catatan seminar/revisi">{{ $seminarRequest->seminar_score_note }}</x-textarea-input>
                                                    <x-primary-button>Simpan Nilai</x-primary-button>
                                                </form>
                                            @endif

                                            @if (! (
                                                ($canLecturerAct && ($seminarRequest->status === 'waiting_lecturer_approval' || (! $isFinalized && ($seminarRequest->status === 'scheduled' || ($seminarRequest->status === 'completed' && $seminarRequest->assessment_method === 'system')))))
                                                || ($canSchedule && in_array($seminarRequest->status, ['waiting_manual_acc_validation', 'lecturer_approved', 'manual_acc_approved', 'scheduled'], true))
                                                || (! $isFinalized && $canSchedule && $seminarRequest->status === 'waiting_assessment_validation')
                                                || $isFinalized
                                            ))
                                                <span class="text-xs text-gray-500">Tidak ada aksi untuk status ini.</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="silat-table-cell"><x-empty-state title="Belum ada pengajuan seminar" icon="fa-person-chalkboard" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 p-4">{{ $seminarRequests->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
