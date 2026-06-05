<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Konfigurasi Program') }}: {{ $period->display_name }}
            </h2>
            <a class="text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('system-configurations.index') }}">
                {{ __('Kembali') }}
            </a>
        </div>
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

            <form method="POST" action="{{ route('system-configurations.update', $period) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @if ($period->is_locked)
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ __('Periode ini sudah terkunci. Konfigurasi hanya dapat diubah oleh admin khusus.') }}
                    </div>
                @endif

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Umum') }}</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="timezone" :value="__('Timezone')" />
                            <x-text-input id="timezone" name="timezone" class="mt-1 block w-full" :value="old('timezone', $settings['timezone'])" required />
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Pendaftaran dan Kuota') }}</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="min_place_quota" :value="__('Kuota minimal per mitra')" />
                            <x-text-input id="min_place_quota" name="enrollment[min_place_quota]" type="number" class="mt-1 block w-full" :value="old('enrollment.min_place_quota', $settings['enrollment']['min_place_quota'])" required />
                        </div>
                        <div>
                            <x-input-label for="max_place_quota" :value="__('Kuota maksimal per mitra')" />
                            <x-text-input id="max_place_quota" name="enrollment[max_place_quota]" type="number" class="mt-1 block w-full" :value="old('enrollment.max_place_quota', $settings['enrollment']['max_place_quota'])" required />
                        </div>
                        <div>
                            <x-input-label for="minimum_total_sks_s1" :value="__('Minimal SKS S1')" />
                            <x-text-input id="minimum_total_sks_s1" name="enrollment[minimum_total_sks_s1]" type="number" class="mt-1 block w-full" :value="old('enrollment.minimum_total_sks_s1', $settings['enrollment']['minimum_total_sks_s1'])" required />
                        </div>
                        <div>
                            <x-input-label for="minimum_total_sks_d3" :value="__('Minimal SKS D3')" />
                            <x-text-input id="minimum_total_sks_d3" name="enrollment[minimum_total_sks_d3]" type="number" class="mt-1 block w-full" :value="old('enrollment.minimum_total_sks_d3', $settings['enrollment']['minimum_total_sks_d3'])" required />
                        </div>
                        <div>
                            <x-input-label for="minimum_semester_s1" :value="__('Minimal semester S1')" />
                            <x-text-input id="minimum_semester_s1" name="enrollment[minimum_semester_s1]" type="number" class="mt-1 block w-full" :value="old('enrollment.minimum_semester_s1', $settings['enrollment']['minimum_semester_s1'])" required />
                        </div>
                        <div>
                            <x-input-label for="minimum_semester_d3" :value="__('Minimal semester D3')" />
                            <x-text-input id="minimum_semester_d3" name="enrollment[minimum_semester_d3]" type="number" class="mt-1 block w-full" :value="old('enrollment.minimum_semester_d3', $settings['enrollment']['minimum_semester_d3'])" required />
                        </div>
                        <div>
                            <x-input-label for="minimum_gpa" :value="__('Minimal IPK')" />
                            <x-text-input id="minimum_gpa" name="enrollment[minimum_gpa]" type="number" step="0.01" class="mt-1 block w-full" :value="old('enrollment.minimum_gpa', $settings['enrollment']['minimum_gpa'])" required />
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ __('Kontrol Layanan Mahasiswa') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Pendaftaran tetap mengikuti deadline Pendaftaran Dibuka dan Pendaftaran Ditutup. Toggle ini hanya membatasi layanan pengajuan oleh mahasiswa.') }}</p>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <label class="flex min-h-24 items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="checkbox" name="workflow[allow_place_proposal]" value="1" @checked(old('workflow.allow_place_proposal', data_get($settings, 'workflow.allow_place_proposal', true))) class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block font-semibold text-gray-900">{{ __('Usulan Mitra Baru') }}</span>
                                <span class="mt-1 block text-sm text-gray-500">{{ __('Mahasiswa dapat mengusulkan mitra baru untuk periode ini.') }}</span>
                            </span>
                        </label>
                        <label class="flex min-h-24 items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="checkbox" name="workflow[allow_relocation]" value="1" @checked(old('workflow.allow_relocation', data_get($settings, 'workflow.allow_relocation', true))) class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block font-semibold text-gray-900">{{ __('Pengajuan Pindah Mitra') }}</span>
                                <span class="mt-1 block text-sm text-gray-500">{{ __('Mahasiswa aktif dapat mengajukan pindah mitra.') }}</span>
                            </span>
                        </label>
                        <label class="flex min-h-24 items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="checkbox" name="workflow[allow_supervisor_change]" value="1" @checked(old('workflow.allow_supervisor_change', data_get($settings, 'workflow.allow_supervisor_change', true))) class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block font-semibold text-gray-900">{{ __('Pengajuan Perubahan Pembimbing') }}</span>
                                <span class="mt-1 block text-sm text-gray-500">{{ __('Mahasiswa aktif dapat mengajukan perubahan dosen atau pembimbing lapangan.') }}</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Deadline Periode') }}</h3>
                    <div class="silat-table-wrap mt-4 rounded-lg border border-gray-100">
                        <table class="silat-table">
                            <thead class="silat-table-head">
                                <tr>
                                    <th class="silat-table-cell">{{ __('Jenis') }}</th>
                                    <th class="silat-table-cell">{{ __('Tanggal') }}</th>
                                    <th class="silat-table-cell">{{ __('Poin Sanksi') }}</th>
                                    <th class="silat-table-cell">{{ __('Model Sanksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deadlineTypes as $type => $label)
                                    @php
                                        $deadline = $period->deadlines->firstWhere('deadline_type', $type);
                                    @endphp
                                    <tr>
                                        <td class="silat-table-cell">
                                            <input type="hidden" name="deadlines[{{ $type }}][deadline_type]" value="{{ $type }}">
                                            <span class="font-medium text-gray-900">{{ $label }}</span>
                                        </td>
                                        <td class="silat-table-cell">
                                            <x-text-input name="deadlines[{{ $type }}][deadline_date]" type="date" class="block w-full" :value="old('deadlines.'.$type.'.deadline_date', $deadline?->deadline_date?->toDateString())" />
                                        </td>
                                        <td class="silat-table-cell">
                                            <x-text-input name="deadlines[{{ $type }}][penalty_points]" type="number" class="block w-full" :value="old('deadlines.'.$type.'.penalty_points', $deadline?->penalty_points ?? 0)" />
                                        </td>
                                        <td class="silat-table-cell">
                                            <x-select-input name="deadlines[{{ $type }}][is_fixed_penalty]" class="block w-full">
                                                <option value="0" @selected(! (bool) old('deadlines.'.$type.'.is_fixed_penalty', $deadline?->is_fixed_penalty))>
                                                    {{ __('Per hari keterlambatan') }}
                                                </option>
                                                <option value="1" @selected((bool) old('deadlines.'.$type.'.is_fixed_penalty', $deadline?->is_fixed_penalty))>
                                                    {{ __('Tetap / sekali dikenakan') }}
                                                </option>
                                            </x-select-input>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Check-In') }}</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="recent_limit" :value="__('Jumlah riwayat check-in ditampilkan')" />
                            <x-text-input id="recent_limit" name="check_in[recent_limit]" type="number" class="mt-1 block w-full" :value="old('check_in.recent_limit', $settings['check_in']['recent_limit'])" required />
                            <p class="mt-1 text-xs text-gray-500">{{ __('Membatasi jumlah data terbaru pada tabel Check-In Terakhir mahasiswa.') }}</p>
                        </div>
                        <div>
                            <x-input-label for="photo_max_kb" :value="__('Maks. foto KB')" />
                            <x-text-input id="photo_max_kb" name="check_in[photo_max_kb]" type="number" class="mt-1 block w-full" :value="old('check_in.photo_max_kb', $settings['check_in']['photo_max_kb'])" required />
                        </div>
                        <div>
                            <x-input-label for="max_distance_meters" :value="__('Radius maksimum meter')" />
                            <x-text-input id="max_distance_meters" name="check_in[max_distance_meters]" type="number" class="mt-1 block w-full" :value="old('check_in.max_distance_meters', $settings['check_in']['max_distance_meters'])" required />
                            <p class="mt-1 text-xs text-gray-500">{{ __('Isi 0 untuk menonaktifkan pembatasan radius.') }}</p>
                        </div>
                        <div>
                            <x-input-label for="min_daily_duration_minutes" :value="__('Durasi minimal harian menit')" />
                            <x-text-input id="min_daily_duration_minutes" name="check_in[min_daily_duration_minutes]" type="number" class="mt-1 block w-full" :value="old('check_in.min_daily_duration_minutes', $settings['check_in']['min_daily_duration_minutes'])" required />
                        </div>
                        <div>
                            <x-input-label for="insufficient_duration_penalty_per_hour" :value="__('Sanksi per jam kurang')" />
                            <x-text-input id="insufficient_duration_penalty_per_hour" name="check_in[insufficient_duration_penalty_per_hour]" type="number" class="mt-1 block w-full" :value="old('check_in.insufficient_duration_penalty_per_hour', $settings['check_in']['insufficient_duration_penalty_per_hour'])" required />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label for="inactive_message" :value="__('Pesan jam tidak aktif')" />
                            <x-text-input id="inactive_message" name="check_in[inactive_message]" class="mt-1 block w-full" :value="old('check_in.inactive_message', $settings['check_in']['inactive_message'])" required />
                        </div>
                    </div>

                    <div class="silat-table-wrap mt-6 rounded-lg border border-gray-100">
                        <table class="silat-table">
                            <thead class="silat-table-head">
                                <tr>
                                    <th class="silat-table-cell">{{ __('Status') }}</th>
                                    <th class="silat-table-cell">{{ __('Mulai') }}</th>
                                    <th class="silat-table-cell">{{ __('Selesai') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($settings['check_in']['schedule'] as $index => $slot)
                                    <tr>
                                        <td class="silat-table-cell">
                                            <x-text-input name="check_in[schedule][{{ $index }}][status]" class="block w-full" :value="old('check_in.schedule.'.$index.'.status', $slot['status'])" required />
                                        </td>
                                        <td class="silat-table-cell">
                                            <x-text-input name="check_in[schedule][{{ $index }}][start]" type="time" class="block w-full" :value="old('check_in.schedule.'.$index.'.start', $slot['start'])" required />
                                        </td>
                                        <td class="silat-table-cell">
                                            <x-text-input name="check_in[schedule][{{ $index }}][end]" type="time" class="block w-full" :value="old('check_in.schedule.'.$index.'.end', $slot['end'])" required />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Laporan dan Kalender') }}</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="single_check_in_cutoff" :value="__('Cutoff satu check-in')" />
                            <x-text-input id="single_check_in_cutoff" name="report[single_check_in_cutoff]" type="time" class="mt-1 block w-full" :value="old('report.single_check_in_cutoff', $settings['report']['single_check_in_cutoff'])" required />
                        </div>
                        <div>
                            <x-input-label for="morning_checkout" :value="__('Infer pulang pagi')" />
                            <x-text-input id="morning_checkout" name="report[single_morning_checkout_hour]" type="number" class="mt-1 block w-full" :value="old('report.single_morning_checkout_hour', $settings['report']['single_morning_checkout_hour'])" required />
                        </div>
                        <div>
                            <x-input-label for="afternoon_checkin" :value="__('Infer masuk siang')" />
                            <x-text-input id="afternoon_checkin" name="report[single_afternoon_checkin_hour]" type="number" class="mt-1 block w-full" :value="old('report.single_afternoon_checkin_hour', $settings['report']['single_afternoon_checkin_hour'])" required />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label for="holidays_text" :value="__('Tanggal libur')" />
                            <textarea id="holidays_text" name="calendar[holidays_text]" rows="8" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('calendar.holidays_text', implode("\n", $settings['calendar']['holidays'] ?? [])) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Peta dan Lokasi') }}</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="earth_radius" :value="__('Radius bumi meter')" />
                            <x-text-input id="earth_radius" name="distance[earth_radius_meters]" type="number" class="mt-1 block w-full" :value="old('distance.earth_radius_meters', $settings['distance']['earth_radius_meters'])" required />
                        </div>
                        <div>
                            <x-input-label for="center_lat" :value="__('Latitude tengah')" />
                            <x-text-input id="center_lat" name="map[center][lat]" class="mt-1 block w-full" :value="old('map.center.lat', $settings['map']['center']['lat'])" required />
                        </div>
                        <div>
                            <x-input-label for="center_lng" :value="__('Longitude tengah')" />
                            <x-text-input id="center_lng" name="map[center][lng]" class="mt-1 block w-full" :value="old('map.center.lng', $settings['map']['center']['lng'])" required />
                        </div>
                        <div>
                            <x-input-label for="zoom" :value="__('Zoom default')" />
                            <x-text-input id="zoom" name="map[zoom]" type="number" class="mt-1 block w-full" :value="old('map.zoom', $settings['map']['zoom'])" required />
                        </div>
                        <div>
                            <x-input-label for="max_zoom" :value="__('Max zoom')" />
                            <x-text-input id="max_zoom" name="map[max_zoom]" type="number" class="mt-1 block w-full" :value="old('map.max_zoom', $settings['map']['max_zoom'])" required />
                        </div>
                        <div>
                            <x-input-label for="fit_max_zoom" :value="__('Fit max zoom')" />
                            <x-text-input id="fit_max_zoom" name="map[fit_max_zoom]" type="number" class="mt-1 block w-full" :value="old('map.fit_max_zoom', $settings['map']['fit_max_zoom'])" required />
                        </div>
                        <div>
                            <x-input-label for="office_zoom" :value="__('Zoom lokasi mitra')" />
                            <x-text-input id="office_zoom" name="map[office_zoom]" type="number" class="mt-1 block w-full" :value="old('map.office_zoom', $settings['map']['office_zoom'])" required />
                        </div>
                        <div>
                            <x-input-label for="current_location_zoom" :value="__('Zoom lokasi saat ini')" />
                            <x-text-input id="current_location_zoom" name="map[current_location_zoom]" type="number" class="mt-1 block w-full" :value="old('map.current_location_zoom', $settings['map']['current_location_zoom'])" required />
                        </div>
                        <div>
                            <x-input-label for="limit_default" :value="__('Limit monitoring default')" />
                            <x-text-input id="limit_default" name="map[monitoring_limit_default]" type="number" class="mt-1 block w-full" :value="old('map.monitoring_limit_default', $settings['map']['monitoring_limit_default'])" required />
                        </div>
                        <div>
                            <x-input-label for="limit_max" :value="__('Limit monitoring max')" />
                            <x-text-input id="limit_max" name="map[monitoring_limit_max]" type="number" class="mt-1 block w-full" :value="old('map.monitoring_limit_max', $settings['map']['monitoring_limit_max'])" required />
                        </div>
                        <div>
                            <x-input-label for="timeout_ms" :value="__('Geolocation timeout ms')" />
                            <x-text-input id="timeout_ms" name="map[geolocation][timeout_ms]" type="number" class="mt-1 block w-full" :value="old('map.geolocation.timeout_ms', $settings['map']['geolocation']['timeout_ms'])" required />
                        </div>
                        <div>
                            <x-input-label for="maximum_age_ms" :value="__('Geolocation cache ms')" />
                            <x-text-input id="maximum_age_ms" name="map[geolocation][maximum_age_ms]" type="number" class="mt-1 block w-full" :value="old('map.geolocation.maximum_age_ms', $settings['map']['geolocation']['maximum_age_ms'])" required />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="map[geolocation][enable_high_accuracy]" value="1" @checked(old('map.geolocation.enable_high_accuracy', $settings['map']['geolocation']['enable_high_accuracy'])) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span>{{ __('High accuracy GPS') }}</span>
                        </label>
                        <div class="sm:col-span-3">
                            <x-input-label for="tile_url" :value="__('Tile URL')" />
                            <x-text-input id="tile_url" name="map[tile_url]" class="mt-1 block w-full" :value="old('map.tile_url', $settings['map']['tile_url'])" required />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label for="tile_attribution" :value="__('Tile attribution')" />
                            <x-text-input id="tile_attribution" name="map[tile_attribution]" class="mt-1 block w-full" :value="old('map.tile_attribution', $settings['map']['tile_attribution'])" required />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>{{ __('Simpan Konfigurasi') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
