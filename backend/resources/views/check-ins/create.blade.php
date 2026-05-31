<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Check-In PKL') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div
                            class="monpkl-map monpkl-form-map"
                            data-map-type="check-in"
                            data-lat-input="student_latitude"
                            data-lng-input="student_longitude"
                            data-office-lat="{{ $enrollment->internshipPlace?->latitude }}"
                            data-office-lng="{{ $enrollment->internshipPlace?->longitude }}"
                            data-office-name="{{ $enrollment->internshipPlace?->name }}"
                            data-map-config='@json($mapConfig)'
                        ></div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <form method="POST" action="{{ route('check-ins.store') }}" enctype="multipart/form-data" class="p-6 space-y-5">
                        @csrf

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Mahasiswa') }}</p>
                            <p class="mt-1 font-medium text-gray-900">{{ $enrollment->student?->full_name }}</p>
                            <p class="text-sm text-gray-600">{{ $enrollment->student?->npm }} · {{ $enrollment->studyProgram?->name }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Tempat PKL') }}</p>
                            <p class="mt-1 font-medium text-gray-900">{{ $enrollment->internshipPlace?->name ?? '-' }}</p>
                            <p class="text-sm text-gray-600">{{ $enrollment->internshipPeriod?->name }}</p>
                        </div>

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                            <p class="text-sm text-gray-600">{{ __('Status berdasarkan jam server saat ini') }}</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $statusPreview }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="student_latitude" :value="__('Latitude Anda')" />
                                <x-text-input id="student_latitude" name="student_latitude" type="text" class="mt-1 block w-full" readonly required />
                            </div>
                            <div>
                                <x-input-label for="student_longitude" :value="__('Longitude Anda')" />
                                <x-text-input id="student_longitude" name="student_longitude" type="text" class="mt-1 block w-full" readonly required />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="photo" :value="__('Foto Bukti')" />
                            <input id="photo" name="photo" type="file" accept="image/*" capture="environment" class="mt-1 block w-full text-sm text-gray-700" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('Opsional, maksimal 4 MB.') }}</p>
                        </div>

                        <div>
                            <x-input-label for="note" :value="__('Catatan Harian')" />
                            <textarea id="note" name="note" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('note') }}</textarea>
                        </div>

                        <x-primary-button>
                            {{ __('Simpan Check-In') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Check-In Terakhir') }}</h3>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Waktu') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Jarak') }}</th>
                                    <th class="px-4 py-3">{{ __('Catatan') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($recentCheckIns as $checkIn)
                                    <tr>
                                        <td class="px-4 py-3">{{ $checkIn->checked_at?->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3">{{ $checkIn->type }}</td>
                                        <td class="px-4 py-3">{{ $checkIn->distance_meters === null ? '-' : number_format($checkIn->distance_meters, 0, ',', '.').' m' }}</td>
                                        <td class="px-4 py-3">{{ $checkIn->note ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('Belum ada check-in.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
