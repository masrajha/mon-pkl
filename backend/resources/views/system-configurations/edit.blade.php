<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Konfigurasi Sistem') }}: {{ $period->name }}
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
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Check-In') }}</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="recent_limit" :value="__('Check-in terakhir')" />
                            <x-text-input id="recent_limit" name="check_in[recent_limit]" type="number" class="mt-1 block w-full" :value="old('check_in.recent_limit', $settings['check_in']['recent_limit'])" required />
                        </div>
                        <div>
                            <x-input-label for="photo_max_kb" :value="__('Maks. foto KB')" />
                            <x-text-input id="photo_max_kb" name="check_in[photo_max_kb]" type="number" class="mt-1 block w-full" :value="old('check_in.photo_max_kb', $settings['check_in']['photo_max_kb'])" required />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label for="inactive_message" :value="__('Pesan jam tidak aktif')" />
                            <x-text-input id="inactive_message" name="check_in[inactive_message]" class="mt-1 block w-full" :value="old('check_in.inactive_message', $settings['check_in']['inactive_message'])" required />
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Mulai') }}</th>
                                    <th class="px-4 py-3">{{ __('Selesai') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($settings['check_in']['schedule'] as $index => $slot)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <x-text-input name="check_in[schedule][{{ $index }}][status]" class="block w-full" :value="old('check_in.schedule.'.$index.'.status', $slot['status'])" required />
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-text-input name="check_in[schedule][{{ $index }}][start]" type="time" class="block w-full" :value="old('check_in.schedule.'.$index.'.start', $slot['start'])" required />
                                        </td>
                                        <td class="px-4 py-3">
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
                            <x-input-label for="office_zoom" :value="__('Zoom instansi')" />
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
