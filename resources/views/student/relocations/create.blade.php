<x-app-layout>
    @php
        $selectedPlaceId = old('new_internship_place_id');
        $selectedPlaceLabel = ((string) $selectedPlace?->id === (string) $selectedPlaceId) ? $selectedPlace?->name : '';
    @endphp
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('Permohonan Pindah Mitra') }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
        @if ($errors->any())<div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        @if ($hasPendingRequest)
            <x-alert variant="warning" class="mb-4">Masih ada permohonan pindah mitra berstatus Menunggu. Batalkan permohonan tersebut atau tunggu keputusan admin/koordinator sebelum mengajukan yang baru.</x-alert>
        @endif
        <form method="POST" action="{{ route('student.relocations.store') }}" class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf
            <div>
                <x-input-label for="internship_enrollment_id" value="Enrollment Aktif" />
                <select id="internship_enrollment_id" name="internship_enrollment_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="">Pilih enrollment</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}" @selected((string) old('internship_enrollment_id', $selectedEnrollmentId) === (string) $enrollment->id)>
                            {{ $enrollment->internshipPeriod?->display_name }} - {{ $enrollment->studyProgram?->name }} - {{ $enrollment->internshipPlace?->name ?: 'Mitra belum ditentukan' }}
                        </option>
                    @endforeach
                </select>
                @if ($enrollments->isEmpty())
                    <p class="mt-2 text-sm text-amber-700">Belum ada enrollment aktif yang dapat diajukan pindah mitra.</p>
                @endif
            </div>
            <div data-place-search data-search-url="{{ route('student.places.search') }}">
                <x-input-label for="new_internship_place_search" value="Mitra Tujuan" />
                <input id="new_internship_place_id" type="hidden" name="new_internship_place_id" value="{{ $selectedPlaceId }}" required>
                <div class="relative mt-1">
                    <x-text-input id="new_internship_place_search" type="search" class="block w-full" value="{{ $selectedPlaceLabel }}" autocomplete="off" placeholder="Ketik minimal 2 huruf nama, alamat, atau kota mitra" required />
                    <div data-place-suggestions class="absolute z-20 mt-1 hidden max-h-72 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"></div>
                </div>
            </div>
            <div>
                <x-input-label for="reason" value="Alasan Pindah" />
                <textarea id="reason" name="reason" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('reason') }}</textarea>
            </div>
            <div class="flex items-center justify-between gap-3">
                <a class="silat-secondary-link" href="{{ route('student.relocations.index') }}">Lihat histori</a>
                <x-primary-button :disabled="$hasPendingRequest || $enrollments->isEmpty()">Kirim Permohonan</x-primary-button>
            </div>
        </form>
    </div></div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-place-search]');
            if (! root) return;

            const input = document.getElementById('new_internship_place_search');
            const hidden = document.getElementById('new_internship_place_id');
            const suggestions = root.querySelector('[data-place-suggestions]');
            let timeout;

            const render = (places) => {
                suggestions.innerHTML = '';

                if (! places.length) {
                    suggestions.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Mitra tidak ditemukan.</div>';
                    suggestions.classList.remove('hidden');
                    return;
                }

                places.forEach((place) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-gray-50 focus:bg-gray-50';
                    button.innerHTML = `<span class="font-medium text-gray-900"></span><div class="text-xs text-gray-500"></div>`;
                    button.querySelector('span').textContent = place.name;
                    button.querySelector('div').textContent = [place.city, place.address].filter(Boolean).join(' · ') || 'Alamat belum diisi';
                    button.addEventListener('click', () => {
                        hidden.value = place.id;
                        input.value = place.label;
                        suggestions.classList.add('hidden');
                    });
                    suggestions.appendChild(button);
                });

                suggestions.classList.remove('hidden');
            };

            input.addEventListener('input', () => {
                clearTimeout(timeout);
                hidden.value = '';

                const query = input.value.trim();
                if (query.length < 2) {
                    suggestions.classList.add('hidden');
                    return;
                }

                timeout = setTimeout(async () => {
                    const response = await fetch(`${root.dataset.searchUrl}?q=${encodeURIComponent(query)}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    render(response.ok ? await response.json() : []);
                }, 250);
            });

            document.addEventListener('click', (event) => {
                if (! root.contains(event.target)) suggestions.classList.add('hidden');
            });
        });
    </script>
</x-app-layout>
