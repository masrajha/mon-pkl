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
        'minimum_total_sks_s1' => (int) env('MONPKL_MINIMUM_TOTAL_SKS_S1', env('MONPKL_MINIMUM_TOTAL_SKS', 100)),
        'minimum_total_sks_d3' => (int) env('MONPKL_MINIMUM_TOTAL_SKS_D3', 80),
        'minimum_semester_s1' => (int) env('MONPKL_MINIMUM_SEMESTER_S1', 6),
        'minimum_semester_d3' => (int) env('MONPKL_MINIMUM_SEMESTER_D3', 4),
        'minimum_gpa' => (float) env('MONPKL_MINIMUM_GPA', 2.00),
    ],

    'workflow' => [
        'allow_place_proposal' => filter_var(env('MONPKL_ALLOW_PLACE_PROPOSAL', true), FILTER_VALIDATE_BOOL),
        'allow_relocation' => filter_var(env('MONPKL_ALLOW_RELOCATION', true), FILTER_VALIDATE_BOOL),
        'allow_supervisor_change' => filter_var(env('MONPKL_ALLOW_SUPERVISOR_CHANGE', true), FILTER_VALIDATE_BOOL),
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
        'max_forgotten_attendance_requests' => (int) env('MONPKL_REPORT_MAX_FORGOTTEN_ATTENDANCE_REQUESTS', 3),
    ],

    'final_assessment_document' => [
        'logo_url' => env('MONPKL_FINAL_DOC_LOGO_URL', ''),
        'ministry' => env('MONPKL_FINAL_DOC_MINISTRY', 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS DAN TEKNOLOGI'),
        'university' => env('MONPKL_FINAL_DOC_UNIVERSITY', 'UNIVERSITAS LAMPUNG'),
        'faculty' => env('MONPKL_FINAL_DOC_FACULTY', 'FAKULTAS MATEMATIKA DAN ILMU PENGETAHUAN ALAM'),
        'department' => env('MONPKL_FINAL_DOC_DEPARTMENT', 'JURUSAN ILMU KOMPUTER'),
        'address' => env('MONPKL_FINAL_DOC_ADDRESS', 'Jalan Prof. Dr. Sumantri Brojonegoro No. 1 Rajabasa Bandar Lampung 35145'),
        'phone' => env('MONPKL_FINAL_DOC_PHONE', 'Telepon (0721) 704625'),
        'fax' => env('MONPKL_FINAL_DOC_FAX', 'Faksimile (0721) 706625'),
        'website' => env('MONPKL_FINAL_DOC_WEBSITE', 'https://ilkom.unila.ac.id'),
        'email' => env('MONPKL_FINAL_DOC_EMAIL', 'ilmu.komputer@fmipa.unila.ac.id'),
        'document_number_format' => env('MONPKL_FINAL_DOC_NUMBER_FORMAT', '{enrollment}/UN.26.7.6/KP/PKL/{year}'),
        'city' => env('MONPKL_FINAL_DOC_CITY', 'Bandar Lampung'),
        'chair_name' => env('MONPKL_FINAL_DOC_CHAIR_NAME', ''),
        'chair_identifier' => env('MONPKL_FINAL_DOC_CHAIR_IDENTIFIER', ''),
    ],

    'deadline_types' => [
        'registration_start' => 'Pendaftaran Dibuka',
        'registration_end' => 'Pendaftaran Ditutup',
        'proposal' => 'Proposal Rencana Kerja',
        'bab1' => 'Pelaporan Tahap 1: Bab 1',
        'bab2' => 'Pelaporan Tahap 2: Bab 1 dan 2.',
        'bab3' => 'Pelaporan Tahap 3: Bab 1, 2 dan 3.',
        'full_report' => 'Pelaporan Tahap 4 (Laporan Lengkap): Bab 1 s.d 5',
        'seminar' => 'Seminar',
        'hardcopy' => 'Hardcopy',
    ],

    'report_submission_types' => [
        'proposal' => 'Proposal Rencana Kerja',
        'bab1' => 'Pelaporan Tahap 1: Bab 1',
        'bab2' => 'Pelaporan Tahap 2: Bab 1 dan 2.',
        'bab3' => 'Pelaporan Tahap 3: Bab 1, 2 dan 3.',
        'full_report' => 'Pelaporan Tahap 4 (Laporan Lengkap): Bab 1 s.d 5',
        'hardcopy' => 'Hardcopy',
    ],

    'report_submission_notes' => [
        'proposal' => 'Cantumkan data mahasiswa (nama, NPM, kontak, email), data instansi (pimpinan, kontak), minimal 4 rencana jenis pekerjaan, jadwal mulai-selesai, serta rencana kegiatan singkat yang spesifik.',
        'bab1' => 'Fokus pada latar belakang pemilihan instansi, tujuan proyek (bukan tujuan umum PKL), manfaat bagi mahasiswa dan instansi, serta lingkup waktu, tempat, dan substansi. Hindari mencampur isi bab lain.',
        'bab2' => 'Bab 1 harus sudah lengkap dan direvisi. Bab 2 wajib memuat gambaran umum perusahaan (sejarah, struktur, produk, peralatan, mitra), landasan teori relevan, serta analisis proses bisnis berjalan (permasalahan & kebutuhan informasi).',
        'bab3' => 'Bab 3 (Rencana Kegiatan) harus berisi deskripsi kegiatan solusi alternatif, sumber data, metode pengumpulan data, dan metode penyelesaian masalah bertahap. Pastikan konsistensi alur ketiga bab.',
        'full_report' => 'Lengkapi dengan Bab IV (Pembahasan) yang berisi analisis kelemahan & keunggulan serta pengajuan solusi alternatif (minimal rancangan, lebih baik implementasi). Bab V (Kesimpulan & Rekomendasi) harus sesuai analisis. Perhatikan format: A4, bahasa Indonesia, sampul buffalo.',
        'hardcopy' => 'Upload tanda terima hardcopy dari jurusan sebagai bukti penyerahan laporan final dalam bentuk cetak.',
    ],

    'seminar_assessment_rubric' => [
        'material_mastery' => [
            'group' => 'Seminar',
            'label' => 'Penguasaan materi / metode',
            'weight' => 20,
        ],
        'scientific_attitude' => [
            'group' => 'Seminar',
            'label' => 'Sikap ilmiah dan argumentasi',
            'weight' => 10,
        ],
        'presentation_technique' => [
            'group' => 'Seminar',
            'label' => 'Teknik penyajian dan kebahasaan',
            'weight' => 10,
        ],
        'originality' => [
            'group' => 'Laporan',
            'label' => 'Originalitas',
            'weight' => 30,
        ],
        'relevance_cohesion' => [
            'group' => 'Laporan',
            'label' => 'Relevansi dan Keterpaduan',
            'weight' => 15,
        ],
        'writing_format' => [
            'group' => 'Laporan',
            'label' => 'Penulisan (Format dan Bahasa)',
            'weight' => 15,
        ],
    ],

    'field_supervisor_assessment_rubric' => [
        'attendance' => [
            'group' => 'A. Disiplin dan Kepatuhan',
            'label' => 'Kehadiran',
        ],
        'rules_compliance' => [
            'group' => 'A. Disiplin dan Kepatuhan',
            'label' => 'Kepatuhan terhadap Tata Tertib',
        ],
        'group_teamwork' => [
            'group' => 'B. Kerja Sama',
            'label' => 'Kerja Sama dengan Anggota Kelompok',
        ],
        'other_teamwork' => [
            'group' => 'B. Kerja Sama',
            'label' => 'Kolaborasi dengan Tim/Unit Lain',
        ],
        'supervisor_teamwork' => [
            'group' => 'B. Kerja Sama',
            'label' => 'Komunikasi dan Respons terhadap Pembimbing',
        ],
        'innovation' => [
            'group' => 'C. Prestasi Kerja',
            'label' => 'Inisiatif dan Inovasi Kerja',
        ],
        'task_ability' => [
            'group' => 'C. Prestasi Kerja',
            'label' => 'Kemampuan Menyelesaikan Tugas',
        ],
        'seriousness' => [
            'group' => 'C. Prestasi Kerja',
            'label' => 'Tanggung Jawab dan Kesungguhan Kerja',
        ],
    ],

    'field_supervisor_institution_feedback_survey' => [
        'student_preparation' => [
            'label' => 'Kesiapan mahasiswa sebelum turun lapang',
            'options' => [
                'very_good' => 'Sangat baik',
                'good' => 'Baik',
                'fair' => 'Cukup',
                'needs_improvement' => 'Perlu ditingkatkan',
            ],
        ],
        'competency_fit' => [
            'label' => 'Kesesuaian kompetensi mahasiswa dengan kebutuhan mitra',
            'options' => [
                'very_suitable' => 'Sangat sesuai',
                'suitable' => 'Sesuai',
                'fair' => 'Cukup sesuai',
                'not_suitable' => 'Belum sesuai',
            ],
        ],
        'campus_communication' => [
            'label' => 'Komunikasi kampus/prodi dengan mitra',
            'options' => [
                'very_good' => 'Sangat baik',
                'good' => 'Baik',
                'fair' => 'Cukup',
                'needs_improvement' => 'Perlu ditingkatkan',
            ],
        ],
        'supervision_support' => [
            'label' => 'Dukungan dosen/prodi selama pelaksanaan',
            'options' => [
                'very_helpful' => 'Sangat membantu',
                'helpful' => 'Membantu',
                'fair' => 'Cukup',
                'needs_improvement' => 'Perlu ditingkatkan',
            ],
        ],
        'future_acceptance' => [
            'label' => 'Kesediaan menerima mahasiswa lagi',
            'options' => [
                'yes' => 'Bersedia',
                'conditional' => 'Bersedia dengan catatan',
                'no' => 'Belum bersedia',
            ],
        ],
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

    'routing' => [
        'max_places' => (int) env('MONPKL_ROUTE_MAX_PLACES', 25),
        'cache_minutes' => (int) env('MONPKL_ROUTE_CACHE_MINUTES', 60),
        'osrm' => [
            'enabled' => filter_var(env('MONPKL_OSRM_ENABLED', true), FILTER_VALIDATE_BOOL),
            'base_url' => env('MONPKL_OSRM_BASE_URL', 'https://router.project-osrm.org'),
            'profile' => env('MONPKL_OSRM_PROFILE', 'driving'),
            'timeout_seconds' => (int) env('MONPKL_OSRM_TIMEOUT_SECONDS', 5),
        ],
    ],

    'region' => [
        'provinces_url' => env('MONPKL_REGION_PROVINCES_URL', 'https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json'),
        'regencies_url' => env('MONPKL_REGION_REGENCIES_URL', 'https://www.emsifa.com/api-wilayah-indonesia/api/regencies/{province_id}.json'),
        'reverse_geocode_url' => env('MONPKL_REGION_REVERSE_GEOCODE_URL', 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={lat}&lon={lng}&accept-language=id'),
    ],
];
