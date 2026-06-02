<?php

return [
    'timezone' => env('MONPKL_TIMEZONE', 'Asia/Jakarta'),

    'check_in' => [
        'recent_limit' => (int) env('MONPKL_CHECKIN_RECENT_LIMIT', 5),
        'photo_disk' => env('MONPKL_CHECKIN_PHOTO_DISK', 'public'),
        'photo_directory' => env('MONPKL_CHECKIN_PHOTO_DIRECTORY', 'check-in-photos'),
        'photo_max_kb' => (int) env('MONPKL_CHECKIN_PHOTO_MAX_KB', 4096),
        'max_distance_meters' => (int) env('MONPKL_CHECKIN_MAX_DISTANCE_METERS', 5000),
        'min_daily_duration_minutes' => (int) env('MONPKL_CHECKIN_MIN_DAILY_DURATION_MINUTES', 360),
        'insufficient_duration_penalty_per_hour' => (int) env('MONPKL_CHECKIN_INSUFFICIENT_DURATION_PENALTY_PER_HOUR', 1),
        'inactive_message' => env('MONPKL_CHECKIN_INACTIVE_MESSAGE', 'Check-in hanya dapat dilakukan pada jam kerja 07.00 sampai 19.00.'),
        'schedule' => [
            ['status' => 'Masuk', 'start' => '07:00', 'end' => '08:00'],
            ['status' => 'Datang Terlambat', 'start' => '08:00', 'end' => '12:00'],
            ['status' => 'Pulang Cepat', 'start' => '12:00', 'end' => '16:00'],
            ['status' => 'Pulang', 'start' => '16:00', 'end' => '19:00'],
        ],
    ],

    'enrollment' => [
        'min_place_quota' => (int) env('MONPKL_PLACE_MIN_QUOTA', 2),
        'max_place_quota' => (int) env('MONPKL_PLACE_MAX_QUOTA', 3),
        'minimum_total_sks' => (int) env('MONPKL_MINIMUM_TOTAL_SKS', 100),
        'minimum_semester_s1' => (int) env('MONPKL_MINIMUM_SEMESTER_S1', 6),
        'minimum_semester_d3' => (int) env('MONPKL_MINIMUM_SEMESTER_D3', 4),
        'minimum_gpa' => (float) env('MONPKL_MINIMUM_GPA', 2.00),
    ],

    'super_admin_emails' => collect(explode(',', env('MONPKL_SUPER_ADMIN_EMAILS', '')))
        ->map(fn (string $email) => trim($email))
        ->filter()
        ->values()
        ->all(),

    'report' => [
        'single_check_in_cutoff' => env('MONPKL_REPORT_SINGLE_CHECKIN_CUTOFF', '12:00'),
        'single_morning_checkout_hour' => (int) env('MONPKL_REPORT_SINGLE_MORNING_CHECKOUT_HOUR', 13),
        'single_afternoon_checkin_hour' => (int) env('MONPKL_REPORT_SINGLE_AFTERNOON_CHECKIN_HOUR', 11),
    ],

    'calendar' => [
        'holidays' => [
            '2024-02-01', '2024-02-08', '2024-02-10', '2024-03-11', '2024-03-29',
            '2024-05-01', '2024-05-23', '2024-06-01', '2024-06-17', '2024-07-07',
            '2024-08-17', '2024-09-16', '2024-12-25',
            '2025-01-01', '2025-01-27', '2025-01-28', '2025-01-29', '2025-03-28',
            '2025-03-29', '2025-03-31', '2025-04-01', '2025-04-02', '2025-04-03',
            '2025-04-04', '2025-04-07', '2025-04-18', '2025-04-20', '2025-05-01',
            '2025-05-12', '2025-05-13', '2025-05-29', '2025-05-30', '2025-06-01',
            '2025-06-06', '2025-06-09', '2025-06-27', '2025-08-17', '2025-09-05',
            '2025-12-25', '2025-12-26',
        ],
    ],

    'distance' => [
        'earth_radius_meters' => (int) env('MONPKL_EARTH_RADIUS_METERS', 6371000),
    ],

    'map' => [
        'center' => [
            'lat' => (float) env('MONPKL_MAP_CENTER_LAT', -5.3971),
            'lng' => (float) env('MONPKL_MAP_CENTER_LNG', 105.2668),
        ],
        'zoom' => (int) env('MONPKL_MAP_ZOOM', 11),
        'max_zoom' => (int) env('MONPKL_MAP_MAX_ZOOM', 19),
        'fit_max_zoom' => (int) env('MONPKL_MAP_FIT_MAX_ZOOM', 15),
        'office_zoom' => (int) env('MONPKL_MAP_OFFICE_ZOOM', 15),
        'current_location_zoom' => (int) env('MONPKL_MAP_CURRENT_LOCATION_ZOOM', 16),
        'tile_url' => env('MONPKL_MAP_TILE_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'tile_attribution' => env('MONPKL_MAP_TILE_ATTRIBUTION', '&copy; OpenStreetMap contributors'),
        'geolocation' => [
            'timeout_ms' => (int) env('MONPKL_GEOLOCATION_TIMEOUT_MS', 12000),
            'maximum_age_ms' => (int) env('MONPKL_GEOLOCATION_MAXIMUM_AGE_MS', 30000),
            'enable_high_accuracy' => filter_var(env('MONPKL_GEOLOCATION_HIGH_ACCURACY', true), FILTER_VALIDATE_BOOL),
        ],
        'monitoring_limit_default' => (int) env('MONPKL_MAP_MONITORING_LIMIT_DEFAULT', 500),
        'monitoring_limit_max' => (int) env('MONPKL_MAP_MONITORING_LIMIT_MAX', 2000),
    ],

    'region' => [
        'provinces_url' => env('MONPKL_REGION_PROVINCES_URL', 'https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json'),
        'regencies_url' => env('MONPKL_REGION_REGENCIES_URL', 'https://www.emsifa.com/api-wilayah-indonesia/api/regencies/{province_id}.json'),
        'reverse_geocode_url' => env('MONPKL_REGION_REVERSE_GEOCODE_URL', 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={lat}&lon={lng}&accept-language=id'),
    ],
];
