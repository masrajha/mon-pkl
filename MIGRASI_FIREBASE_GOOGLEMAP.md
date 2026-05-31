# Rencana Migrasi Mon PKL dari Firebase dan Google Maps

## Ringkasan Kondisi Saat Ini

Project ini saat ini berbentuk aplikasi web statis di folder `public`, di-host melalui Firebase Hosting, dan memakai Firebase sebagai backend langsung dari browser.

Komponen utama yang ditemukan:

- Halaman HTML statis: `index.html`, `input.html`, `check-in.html`, `monitoringmap.html`, `monitoring.html`, `laporan.html`, `profile.html`, `catatan-harian.html`.
- Script utama di `public/js`, dengan banyak logika bisnis langsung di JavaScript browser.
- Firebase Realtime Database dipakai untuk data:
  - `pkl`: lokasi/tempat PKL.
  - `mon_pkl`: seluruh data check-in/monitoring.
  - `mon_user/{npm}`: data check-in per mahasiswa.
  - `users/{uid}`: profil mahasiswa.
  - `master/kota`: daftar kota.
- Firebase Auth dipakai untuk login Google.
- Firebase Storage dipakai untuk upload foto dari kamera.
- Google Maps dipakai untuk:
  - render peta.
  - marker dan info window.
  - marker clustering.
  - polyline antara lokasi mahasiswa dan instansi.
  - hitung jarak dengan `google.maps.geometry.spherical.computeDistanceBetween`.
  - link navigasi ke Google Maps.

File konfigurasi Firebase yang relevan:

- `firebase.json`
- `.firebaserc`
- `database.rules.json`

## Masalah Utama pada Arsitektur Lama

1. Frontend langsung mengakses database.
   Validasi, otorisasi, dan struktur data bergantung pada aturan Firebase dan kode JavaScript client. Ini sulit dikontrol jika aplikasi makin besar.

2. Logika bisnis tersebar di banyak file.
   Contoh: perhitungan jarak, format laporan, akses user, dan struktur GeoJSON berulang di `monitoringmap.js`, `monitoring.js`, `laporan.js`, `catatan-harian.js`, dan `index-checkin.js`.

3. Struktur data Firebase masih hierarkis dan banyak duplikasi.
   Data check-in disimpan di `mon_pkl` dan juga `mon_user/{npm}`. Ini cepat untuk baca di Firebase, tetapi saat pindah ke database relasional sebaiknya dinormalisasi.

4. Ketergantungan Google Maps cukup dalam.
   Migrasi ke Leaflet bukan hanya mengganti script CDN. Perlu mengganti API marker, polyline, cluster, popup, distance calculation, dan link direction.

5. API key Firebase dan Google Maps berada di frontend.
   Pada arsitektur baru, secret dan akses database sebaiknya berada di server.

## Rekomendasi Arsitektur Baru

Rekomendasi utama:

**Laravel 11/12 + PostgreSQL/PostGIS + Leaflet.js + OpenStreetMap tile provider**

Alasan:

- Cocok dengan aplikasi akademik/administratif yang membutuhkan CRUD, auth, role, laporan, validasi form, upload foto, dan export/print.
- Laravel menyediakan auth, routing, middleware, validation, queue, storage, migration, seeder, dan testing yang matang.
- PostgreSQL cocok untuk data relasional seperti mahasiswa, dosen, instansi, prodi, periode PKL, check-in, dan laporan.
- PostGIS direkomendasikan jika ingin query geospasial yang serius, misalnya radius, jarak, bounding box, atau laporan berdasarkan lokasi.
- Leaflet.js cocok menggantikan Google Maps untuk peta interaktif berbasis OpenStreetMap.

Alternatif yang juga layak:

- **Next.js + NestJS + PostgreSQL/PostGIS** jika tim ingin full TypeScript dan frontend SPA/SSR modern.
- **Django + PostgreSQL/PostGIS** jika tim lebih nyaman Python dan butuh admin panel cepat.
- **Laravel + MySQL** jika infrastruktur kampus lebih siap MySQL, tetapi untuk fitur lokasi jangka panjang PostgreSQL/PostGIS lebih kuat.

Pilihan paling praktis untuk migrasi project ini tetap Laravel, karena aplikasi saat ini berorientasi form, tabel, laporan, login, dan upload.

## Target Arsitektur

```text
Browser
  |
  |-- Blade/Livewire atau Vue/Inertia
  |-- Leaflet.js + OpenStreetMap tiles
  |
Laravel App
  |
  |-- Auth, role, validation, API/controller
  |-- Service: check-in, laporan, jarak, geospatial
  |-- Storage foto lokal/S3-compatible
  |
PostgreSQL + PostGIS
```

Untuk fase awal, frontend dapat memakai Blade + sedikit JavaScript agar migrasi tidak terlalu besar. Setelah stabil, bagian peta/laporan bisa diperkaya dengan Vue atau React jika diperlukan.

## Status Implementasi

Terakhir diperbarui: 31 Mei 2026.

| Tahap | Status | Catatan |
| --- | --- | --- |
| Tahap 1 - Inventarisasi dan Freeze Data | Selesai | Artefak tersedia di `docs/migration/stage-1`. Sudah ada mapping field, manifest backup, runbook freeze, inventaris fitur, dan matriks awal periode/prodi. |
| Tahap 2 - Bangun Backend Baru | Selesai | Backend Laravel 12 dibuat di folder `backend`. PostgreSQL `monpkl` sudah dikonfigurasi, migration inti dan spatial PostGIS sudah berjalan, auth Breeze tersedia, SSO Google Unila sudah dikonfigurasi, SMTP Gmail sudah dikonfigurasi, model/relasi inti dan role middleware sudah dibuat. |
| Tahap 3 - Migrasi Data Firebase ke Database Baru | Selesai | Command `php artisan import:firebase-json` sudah dibuat dan data historis utama sudah diimport ke PostgreSQL. Report import tersedia di `backend/storage/app/private/import-reports`. |
| Tahap 4 - Fondasi Manajemen Data | Selesai sebagian | Menu manajemen admin sudah dibuat untuk User, Mahasiswa, Dosen, Prodi, Periode, Master Tempat PKL, dan Peserta Periode/Penempatan PKL. Pemisahan master tempat PKL dan data peserta periode sudah diterapkan lebih rapi, termasuk master dosen dengan NIP/NIDN/status, relasi dosen pembimbing ke master dosen, dan pembimbing lapangan sebagai data bebas per enrollment. |
| Tahap 5 - Konfigurasi Sistem Per Periode | Selesai sebagian | Konfigurasi operasional sudah tersedia per `Periode PKL`. Perlu dirapikan bersama menu manajemen periode. |
| Tahap 6 - Workflow Mahasiswa dan Pendaftaran PKL | Belum dimulai | Mahasiswa perlu dapat melengkapi profil, mendaftar periode, memilih/mengajukan tempat PKL, menunggu validasi admin, melihat laporan individu, dan mencetak laporan. Workflow harus membedakan pembimbing lapangan sebagai input bebas dari instansi dan dosen pembimbing sebagai pilihan dari daftar dosen di database. |
| Tahap 7 - Migrasi Peta ke Leaflet | Selesai sebagian | Leaflet sudah dipakai untuk peta tempat PKL, peta monitoring, picker lokasi, dan peta check-in. Perlu diselaraskan dengan pemisahan master tempat PKL dan penempatan periode. |
| Tahap 8 - Migrasi Fitur Check-In | Selesai sebagian | Form dan endpoint check-in backend sudah dibuat. Server menentukan status, menghitung jarak, menyimpan lokasi/foto/catatan, dan membatasi akses berdasarkan role mahasiswa. Export/foto historis Firebase Storage belum dimigrasikan. |
| Tahap 9 - Migrasi Laporan dan Rekap | Selesai sebagian | Halaman rekapitulasi monitoring PKL sudah dibuat dari data PostgreSQL dengan filter periode, prodi, tanggal, dan hak akses role. Export PDF/Excel dan tabel hari libur database belum dibuat. |
| Tahap 10 - Cutover dan Decommission Firebase | Belum dimulai | Dilakukan setelah aplikasi baru tervalidasi paralel. |

Catatan Tahap 2:

- Backend berjalan di `backend`.
- Database utama memakai PostgreSQL dengan `.env`: `DB_DATABASE=monpkl`, `DB_USERNAME=monpkl`, `DB_PASSWORD=123`.
- `php artisan migrate:fresh --seed` berhasil untuk migration inti.
- `php artisan test` berhasil: 25 test passed.
- Dev server pernah diverifikasi berjalan di `http://127.0.0.1:8000`.
- Migration tambahan spatial sudah dibuat untuk PostgreSQL/PostGIS dan MariaDB/MySQL:
  - PostgreSQL: `location geography(Point, 4326)` plus GiST index.
  - MariaDB/MySQL: `location POINT NULL`.
- PostGIS sudah aktif di database `monpkl`, migration spatial sudah `Ran`, dan kolom `internship_places.location` bertipe `geography`.
- SMTP Gmail sudah dikonfigurasi di `.env` dengan `MAIL_MAILER=smtp`, host `smtp.gmail.com`, port `587`, dan pengirim `Family Tree <didikunila@gmail.com>`.
- SSO Google sudah aktif dengan callback `http://localhost:8001/auth/google/callback` dan pembatasan domain `unila.ac.id` serta `*.unila.ac.id`.

Catatan Tahap 3:

- Command import dibuat di `backend/app/Console/Commands/ImportFirebaseJson.php`.
- Command utama: `php artisan import:firebase-json`.
- Command mendukung `--dry-run` untuk validasi tanpa menyimpan permanen.
- Sumber import utama: `ilkomunila-export (20220619-Periode Jan 2022).json`.
- Sumber master kota: `ilkomunila-master-export.json`.
- Metadata import default:
  - prodi: `ILKOM` / `Ilmu Komputer`.
  - periode: `Periode Jan 2022`.
  - tahun akademik: `2021/2022`.
  - semester: `Genap`.
- Report import aktual terakhir: `backend/storage/app/private/import-reports/firebase-import-20260530-145609.json`.
- Hasil import aktual di database:
  - `cities`: 27.
  - `internship_places`: 58.
  - `users`: 115.
  - `students`: 122.
  - `internship_enrollments`: 121.
  - `check_ins`: 3524.
- Validasi silang `mon_user` menemukan 2 mismatch yang perlu dicatat untuk audit:
  - NPM `1917051024`: `mon_pkl` 69, `mon_user` 67.
  - NPM `1917051059`: `mon_pkl` 68, `mon_user` 67.
- Tidak ada failed row, warning, atau koordinat invalid pada report import terakhir.

Catatan Tahap 7:

- Paket frontend `leaflet` dan `leaflet.markercluster` sudah dipasang melalui npm.
- Modul peta dibuat di `backend/resources/js/maps/leafletMaps.js`.
- Style peta dibuat di `backend/resources/css/app.css`.
- Route halaman:
  - `/maps/places`
  - `/maps/monitoring`
- Route data JSON:
  - `/maps/places/data`
  - `/maps/monitoring/data`
- Controller peta dibuat di `backend/app/Http/Controllers/MapController.php`.
- Hak akses data peta:
  - `admin`: melihat semua data.
  - `dosen`: melihat enrollment dengan `lecturer_supervisor` sesuai nama/email user dosen.
  - `mahasiswa`: melihat enrollment miliknya sendiri.
- Peta tempat PKL memakai marker cluster dan OpenStreetMap tile.
- Peta monitoring memakai marker cluster, marker instansi, dan polyline dari instansi ke lokasi mahasiswa.
- Peta picker input lokasi tempat PKL sudah dibuat pada route `/internship-places/create` dan `/internship-places/{internshipPlace}/edit`.
- Peta check-in mahasiswa sudah dibuat pada route `/check-ins/create`.
- Filter periode dan prodi tersedia untuk `admin` dan `dosen`.
- Test akses peta dibuat di `backend/tests/Feature/MapAccessTest.php`.
- Build asset berhasil dengan `npm.cmd run build`.
- Test backend berhasil: 41 tests passed.

Catatan Tahap 8:

- Modul konfigurasi operasional dibuat di `backend/config/monpkl.php`.
- Konfigurasi operasional juga sudah dipindahkan ke database per `Periode PKL` melalui tabel `internship_period_settings`.
- Menu `Konfigurasi Sistem` ditambahkan untuk role `admin`.
- Halaman konfigurasi tersedia di `/system-configurations`.
- Seeder `PeriodConfigurationSeeder` dibuat untuk membuat konfigurasi default pada periode PKL yang sudah ada.
- Nilai yang sudah dipindah ke konfigurasi:
  - timezone aplikasi monitoring.
  - jadwal/status check-in: `Masuk`, `Datang Terlambat`, `Pulang Cepat`, `Pulang`.
  - pesan jam tidak aktif.
  - ukuran dan lokasi upload foto check-in.
  - jumlah check-in terakhir yang ditampilkan.
  - radius bumi untuk hitung jarak Haversine.
  - titik tengah peta, zoom, tile URL, attribution, dan opsi geolocation.
  - batas jumlah data peta monitoring.
  - tanggal libur laporan.
  - aturan inferensi jam masuk/pulang pada laporan jika hanya ada satu check-in dalam satu hari.
- Controller check-in dibuat di `backend/app/Http/Controllers/CheckInController.php`.
- Service perhitungan jarak dibuat di `backend/app/Services/DistanceService.php`.
- Service status check-in dibuat di `backend/app/Services/CheckInStatusService.php`.
- Service konfigurasi periode dibuat di `backend/app/Services/PeriodConfigurationService.php`.
- Form check-in dibuat di `backend/resources/views/check-ins/create.blade.php`.
- Route check-in:
  - `GET /check-ins/create`
  - `POST /check-ins`
- Route check-in hanya dapat diakses role `mahasiswa`.
- Mahasiswa tidak mengirim NPM atau status secara manual. Server mengambil enrollment aktif dari akun login.
- Server menentukan status berdasarkan jam server zona `Asia/Jakarta`:
  - 07.00-07.59: `Masuk`.
  - 08.00-11.59: `Datang Terlambat`.
  - 12.00-15.59: `Pulang Cepat`.
  - 16.00-18.59: `Pulang`.
  - di luar jam tersebut: ditolak sebagai jam tidak aktif.
- Server menghitung `distance_meters` memakai Haversine dari lokasi mahasiswa ke lokasi instansi.
- Server menyimpan `device_info` berisi user agent dan IP.
- Upload foto bukti tersedia melalui Laravel Storage pada disk `public`.
- Test fitur check-in dibuat di `backend/tests/Feature/CheckInFeatureTest.php`.
- Test input lokasi tempat PKL dibuat di `backend/tests/Feature/InternshipPlaceFeatureTest.php`.

Catatan Tahap 9:

- Logika awal laporan dipelajari dari `public/finalreport.html` dan `public/js/finalreport.js`.
- Halaman rekap monitoring dibuat di backend baru melalui route `/reports/monitoring`.
- Controller laporan dibuat di `backend/app/Http/Controllers/ReportController.php`.
- View laporan dibuat di `backend/resources/views/reports/monitoring.blade.php`.
- Menu `Rekap` ditambahkan pada navigasi dashboard.
- Dashboard ditambah pintasan `Rekap Monitoring`.
- Rekap menampilkan ringkasan per mahasiswa: foto, nama, NPM, email, prodi, periode, tempat PKL, jumlah hari hadir, jumlah check-in, rata-rata jarak, total durasi, rentang jam masuk, dan rentang jam pulang.
- Filter laporan tersedia untuk tanggal awal/akhir, periode, prodi, Sabtu, Minggu, dan hari libur.
- Default tanggal laporan memakai rentang data check-in historis bila user belum memilih tanggal.
- Hak akses data laporan:
  - `admin`: melihat semua data.
  - `dosen`: melihat enrollment dengan `lecturer_supervisor` sesuai nama/email user dosen.
  - `mahasiswa`: melihat enrollment miliknya sendiri.
- Test fitur laporan dibuat di `backend/tests/Feature/MonitoringReportTest.php`.
- Build asset berhasil dengan `npm.cmd run build`.
- Test backend berhasil: 36 tests passed.

## Pengganti Firebase

### Firebase Realtime Database

Ganti dengan PostgreSQL.

Mapping data awal:

| Firebase Path | Tabel Baru | Catatan |
| --- | --- | --- |
| `users/{uid}` | `users`, `students`, `internship_enrollments` | Simpan akun dan identitas mahasiswa. Data dosen, pembimbing, periode, prodi, dan penempatan PKL disimpan sebagai enrollment per periode |
| `pkl` | `internship_places`, `internship_enrollments` | Lokasi instansi/tempat PKL dan daftar mahasiswa yang ditempatkan pada periode tertentu |
| `mon_pkl` | `check_ins` | Satu sumber utama data check-in, direlasikan ke `internship_enrollments` |
| `mon_user/{npm}` | tidak perlu tabel duplikat | Diganti query `check_ins` berdasarkan enrollment, NPM, periode, dan prodi |
| `master/kota` | `cities` | Master kota |

### Firebase Auth

Opsi pengganti:

1. Laravel Breeze/Fortify untuk login email/password.
2. Laravel Socialite untuk login Google.
3. Integrasi SSO kampus jika tersedia.

Rekomendasi:

- Untuk mempertahankan pengalaman lama, gunakan Laravel Socialite Google login.
- Tambahkan role: `admin`, `dosen`, `mahasiswa`.
- Jangan bergantung pada UID Firebase. Buat `users.id` internal, lalu simpan `google_id` sebagai identitas eksternal.

### Firebase Storage

Ganti dengan Laravel Storage.

Opsi:

- Local storage di server: cukup untuk fase awal.
- S3-compatible storage seperti MinIO, AWS S3, IDCloudHost Object Storage, atau Cloudflare R2 untuk produksi.

File foto check-in sebaiknya disimpan di tabel `check_in_photos` atau kolom `photo_path` pada `check_ins`.

## Pengganti Google Maps dengan Leaflet dan OpenStreetMap

Library yang disarankan:

- `leaflet`: peta utama.
- `leaflet.markercluster`: pengganti `MarkerClusterer`.
- `leaflet-control-geocoder` atau Nominatim jika butuh geocoding.
- `@turf/distance` atau fungsi Haversine server-side untuk hitung jarak.

Mapping API:

| Google Maps | Leaflet |
| --- | --- |
| `new google.maps.Map(...)` | `L.map(...)` |
| `new google.maps.Marker(...)` | `L.marker(...)` |
| `InfoWindow` | `marker.bindPopup(...)` |
| `Polyline` | `L.polyline(...)` |
| `MarkerClusterer` | `L.markerClusterGroup()` |
| `google.maps.geometry.spherical.computeDistanceBetween` | PostGIS `ST_DistanceSphere`, Turf.js, atau Haversine |
| Google Maps direction link | OpenStreetMap/OSRM link atau geo URI |

Contoh tile OpenStreetMap:

```js
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);
```

Catatan penting: untuk produksi dengan trafik besar, jangan memakai tile publik OpenStreetMap secara berat tanpa memperhatikan kebijakan tile usage. Gunakan provider tile seperti OpenMapTiles, MapTiler, Stadia Maps, atau self-host tile server jika diperlukan.

## Desain Database Awal

Tabel inti:

```text
users
- id
- name
- email
- password nullable
- google_id nullable
- avatar_url nullable
- role
- created_at
- updated_at

students
- id
- user_id
- npm
- full_name
- study_program_id optional/default prodi asal
- created_at
- updated_at

lecturers
- id
- user_id nullable
- study_program_id nullable
- name
- email nullable
- nip nullable
- nidn nullable
- status active/inactive
- created_at
- updated_at

study_programs
- id
- code
- name
- faculty nullable
- is_active
- created_at
- updated_at

internship_periods
- id
- name
- academic_year
- semester
- batch nullable
- starts_at
- ends_at
- is_active
- is_locked
- created_at
- updated_at

internship_enrollments
- id
- student_id
- study_program_id
- internship_period_id
- internship_place_id
- lecturer_supervisor_id nullable
- lecturer_supervisor_user_id nullable
- lecturer_supervisor_name nullable legacy/denormalized
- field_supervisor_name nullable
- field_supervisor_phone nullable
- status
- legacy_source_file nullable
- legacy_period_label nullable
- created_at
- updated_at

internship_places
- id
- name
- address
- city_id
- contact_name nullable
- contact_phone nullable
- latitude
- longitude
- location geography(Point, 4326) optional PostGIS
- visited
- created_at
- updated_at

cities
- id
- name

check_ins
- id
- internship_enrollment_id
- type
- note
- checked_at
- student_latitude
- student_longitude
- office_latitude
- office_longitude
- distance_meters
- device_info jsonb
- source_url
- photo_path nullable
- created_at
- updated_at
```

Tabel pendukung yang mungkin diperlukan:

- `daily_notes`: catatan harian jika dipisah dari check-in.
- `holidays`: daftar libur nasional/cuti bersama, menggantikan array hardcoded di `catatan-harian.js`.
- `audit_logs`: log perubahan data penting.

### Model Periodik Multi-Prodi

Sistem monitoring PKL sebaiknya memperlakukan PKL sebagai aktivitas periodik, bukan atribut permanen pada profil mahasiswa. Mahasiswa tetap berada di tabel `students`, sedangkan keikutsertaan PKL per semester/periode berada di `internship_enrollments`.

Implikasinya:

- Satu mahasiswa dapat memiliki lebih dari satu enrollment jika ikut periode berbeda, mengulang PKL, atau berpindah tempat.
- Semua check-in, catatan harian, monitoring, dan laporan mengacu ke `internship_enrollment_id`, bukan hanya `student_id`.
- Filter utama aplikasi adalah `internship_period_id` dan `study_program_id`, lalu dapat dipersempit lagi berdasarkan dosen, instansi, mahasiswa, tanggal, atau status check-in.
- Admin dapat menetapkan satu periode aktif sebagai default agar penggunaan harian tetap sederhana.
- Data lama yang belum punya prodi/periode eksplisit harus diberi metadata saat import, misalnya `legacy_period_label`, `legacy_source_file`, dan prodi default.

Contoh alur filter:

```text
Periode PKL
  -> Prodi
     -> Dosen pembimbing / instansi / mahasiswa
        -> Check-in, monitoring, catatan harian, laporan
```

### Pemisahan Master Data dan Data Periode

Agar sistem dapat dipakai berulang untuk banyak semester dan banyak prodi, data harus dipisah menjadi dua kelompok besar:

1. **Master data**, yaitu data yang relatif stabil dan dapat dipakai ulang lintas periode.
2. **Data periode**, yaitu data operasional yang melekat pada satu periode PKL tertentu.

Pembagian yang disarankan:

| Area | Isi Data | Catatan |
| --- | --- | --- |
| Master User | akun admin, dosen, mahasiswa, role, email, SSO Google | User adalah identitas login, bukan peserta periode. |
| Master Mahasiswa | NPM, nama, prodi asal, relasi ke user | Mahasiswa dapat ikut lebih dari satu periode. |
| Master Dosen | nama, email, NIP, NIDN, prodi, akun login, status aktif | Dibuat eksplisit pada tabel `lecturers`; `users` hanya untuk akun login/role. |
| Master Prodi | kode, nama, fakultas, status aktif | Dipakai untuk filter dan penempatan. |
| Master Tempat PKL | nama instansi, alamat, provinsi, kab/kota, koordinat, kontak umum, status aktif | Tidak menyimpan mahasiswa atau dosen pembimbing periode. |
| Periode PKL | nama periode, tahun akademik, semester, tanggal mulai/akhir, status aktif/terkunci | Menjadi konteks utama semua operasi. |
| Konfigurasi Periode | jam check-in, libur, aturan laporan, peta, batas upload, opsi geolocation | Satu konfigurasi per periode agar aturan dapat berubah tiap semester. |
| Peserta Periode | mahasiswa, periode, prodi, tempat PKL, dosen pembimbing, pembimbing lapangan, kontak mahasiswa, status | Inilah data penempatan PKL yang berubah tiap periode. |
| Monitoring Periode | check-in, catatan, foto, jarak, device info | Selalu mengacu ke peserta periode/enrollment. |

Implikasi penting untuk form:

- Form **Master Tempat PKL** hanya mengelola informasi umum instansi.
- Form **Peserta/ Penempatan Periode** mengelola mahasiswa, dosen pembimbing, pembimbing lapangan, kontak mahasiswa, dan status peserta.
- Nama mahasiswa dan pembimbing tidak boleh melekat permanen pada `internship_places`, karena instansi yang sama dapat dipakai ulang pada periode berbeda dengan peserta dan pembimbing berbeda.
- Jika struktur `internship_enrollments` sudah cukup, tabel ini dapat menjadi tabel penempatan periode. Jika kebutuhan berkembang, dapat diturunkan menjadi modul khusus bernama `Penempatan PKL`.

### Workflow Pembimbing PKL

Pembimbing mahasiswa harus dipisah menjadi dua jenis karena sumber datanya berbeda:

1. **Pembimbing lapangan**
   - Berasal dari instansi/tempat PKL.
   - Diinput bebas karena sering belum tersedia di master kampus.
   - Melekat pada `internship_enrollments`, bukan pada master mahasiswa dan bukan wajib permanen pada master tempat PKL.
   - Dapat diisi oleh mahasiswa saat pendaftaran jika sudah diketahui, atau dilengkapi admin/dosen sebelum laporan dicetak.
   - Data minimal yang disarankan: nama pembimbing lapangan, nomor HP/WA, jabatan/email jika tersedia.

2. **Dosen pembimbing**
   - Berasal dari prodi/kampus.
   - Harus dipilih dari daftar dosen di database, bukan diketik bebas.
   - Disimpan pada tabel `lecturers` yang dapat terhubung ke `users` dengan role `dosen`.
   - Wajib menyimpan identitas akademik dosen seperti NIP, NIDN, prodi, dan status aktif/nonaktif bila tersedia.
   - Melekat pada `internship_enrollments`, karena dosen pembimbing dapat berbeda untuk mahasiswa yang sama pada periode lain.
   - Ditentukan oleh admin/prodi saat validasi pendaftaran atau saat plotting pembimbing.

Rekomendasi aturan workflow:

1. Mahasiswa boleh mendaftar PKL tanpa dosen pembimbing jika pembagian dosen belum dilakukan.
2. Mahasiswa boleh mengisi pembimbing lapangan sendiri jika data sudah diketahui.
3. Admin/prodi memvalidasi pendaftaran, memilih dosen pembimbing dari database, dan dapat mengoreksi pembimbing lapangan.
4. Enrollment dapat berstatus `pending_verification` selama data belum lengkap.
5. Enrollment dapat diaktifkan untuk presensi setelah periode, prodi, dan tempat PKL valid.
6. Cetak laporan hanya boleh dilakukan jika:
   - enrollment sudah valid/aktif/selesai.
   - dosen pembimbing sudah dipilih dari database.
   - pembimbing lapangan sudah terisi.
   - tempat PKL memiliki alamat dan koordinat yang valid.
   - periode PKL sudah jelas.
7. Jika data pembimbing belum lengkap saat mahasiswa membuka laporan, sistem menampilkan status data kurang lengkap dan mengarahkan mahasiswa untuk menghubungi admin/prodi atau melengkapi pembimbing lapangan.

### Rancangan Menu Manajemen

Menu manajemen sebaiknya dibuat sebelum fitur operasional disempurnakan, karena check-in, peta, dan laporan bergantung pada data master yang benar.

Usulan menu:

```text
Dashboard

Manajemen
  - User
  - Mahasiswa
  - Dosen
  - Prodi
  - Tempat PKL
  - Periode PKL
  - Konfigurasi Sistem
  - Peserta Periode / Penempatan PKL

Monitoring
  - Check-In
  - Peta Tempat PKL
  - Peta Monitoring
  - Validasi Kehadiran

Laporan
  - Rekap Monitoring
  - Rekap Mahasiswa
  - Rekap Dosen
  - Rekap Tempat PKL
```

Urutan prioritas menu manajemen:

1. **Periode PKL** dan **Konfigurasi Sistem**, karena menjadi konteks semua fitur periodik.
2. **Master Prodi**, **Mahasiswa**, **Dosen**, dan **User**, karena menentukan hak akses dan filter.
3. **Master Tempat PKL**, karena menjadi referensi penempatan.
4. **Peserta Periode / Penempatan PKL**, karena menghubungkan mahasiswa, periode, prodi, dosen, dan tempat PKL.
5. Baru setelah itu fitur peta, check-in, monitoring, dan laporan disempurnakan.

### Workflow Mahasiswa

Role `mahasiswa` sebaiknya memiliki alur mandiri, tetapi setiap data yang berdampak pada laporan resmi tetap harus divalidasi.

Menu mahasiswa yang disarankan:

```text
Dashboard
Profil Saya
Pendaftaran PKL
Tempat PKL Saya
Presensi
Laporan Saya
Cetak Laporan
```

Alur utama:

1. Mahasiswa mendaftar sebagai user sistem, idealnya melalui Google Unila.
2. Mahasiswa melengkapi profil wajib:
   - NPM.
   - nama lengkap.
   - email student.
   - nomor HP.
   - prodi.
3. Mahasiswa mendaftar sebagai peserta PKL pada periode tertentu.
4. Sistem mengizinkan mahasiswa ikut lebih dari satu periode, misalnya jika mengulang PKL pada semester berikutnya.
5. Pada satu periode dan prodi yang sama, mahasiswa hanya boleh memiliki satu enrollment aktif.
6. Mahasiswa memilih tempat PKL dari master tempat PKL.
7. Jika tempat belum ada, mahasiswa mengajukan tempat PKL baru.
8. Admin memvalidasi pendaftaran, tempat PKL, pembimbing lapangan, dosen pembimbing, dan status peserta.
9. Setelah valid, enrollment menjadi `active` dan mahasiswa dapat melakukan presensi.
10. Mahasiswa melihat laporan individu.
11. Mahasiswa dapat mencetak laporan setelah dosen pembimbing dan pembimbing lapangan lengkap.

Status enrollment yang disarankan:

- `draft`: mahasiswa baru mengisi data pendaftaran.
- `pending_verification`: pendaftaran menunggu validasi admin.
- `revision_required`: admin meminta perbaikan data.
- `active`: peserta resmi dan boleh presensi.
- `completed`: PKL selesai.
- `cancelled`: dibatalkan.
- `rejected`: pendaftaran ditolak.

Usulan tempat PKL baru dari mahasiswa sebaiknya disimpan terpisah dari master tempat PKL sampai disetujui admin.

Tabel yang disarankan:

```text
internship_place_proposals
- id
- internship_period_id
- student_id
- proposed_by user_id nullable
- name
- address
- city_id nullable
- province_name nullable
- city_name nullable
- latitude nullable
- longitude nullable
- field_supervisor_name nullable
- field_supervisor_phone nullable
- status
- admin_note nullable
- approved_internship_place_id nullable
- reviewed_by nullable
- reviewed_at nullable
- created_at
- updated_at
```

Status usulan tempat PKL:

- `pending`: menunggu validasi admin.
- `approved`: disetujui dan sudah menjadi master tempat PKL.
- `rejected`: ditolak.
- `merged`: digabungkan ke master tempat PKL yang sudah ada.

Presensi mahasiswa sebaiknya dipisah menjadi aksi yang eksplisit:

- `check_in`: presensi masuk/pagi.
- `check_out`: presensi pulang.

Status hasil validasi jam dapat dihitung server-side dari konfigurasi periode:

- `on_time`.
- `late`.
- `normal_checkout`.
- `early_leave`.
- `inactive_time`.

## Tahapan Migrasi

### Tahap 1 - Inventarisasi dan Freeze Data

Status: selesai.

Artefak:

- `docs/migration/stage-1/README.md`
- `docs/migration/stage-1/field-mapping.md`
- `docs/migration/stage-1/freeze-runbook.md`
- `docs/migration/stage-1/feature-inventory.md`
- `docs/migration/stage-1/backup-manifest.json`
- `docs/migration/stage-1/inventory-summary.json`

1. Tentukan file export Firebase terakhir yang menjadi sumber migrasi utama.
2. Dokumentasikan struktur final dari path `pkl`, `mon_pkl`, `mon_user`, `users`, dan `master/kota`.
3. Tetapkan metadata import untuk setiap export: periode PKL, tahun akademik, semester/gelombang, prodi, dan status data latihan/produksi.
4. Bekukan sementara perubahan data saat migrasi final, atau buat mekanisme delta export.
5. Simpan backup semua file JSON export di lokasi aman.

Output tahap ini:

- Dokumen mapping field.
- Data JSON final.
- Matriks export ke periode dan prodi.
- Daftar fitur aktif dan fitur lama yang boleh dihentikan.

### Tahap 2 - Bangun Backend Baru

Status: selesai.

Hasil implementasi:

- Project Laravel 12 dibuat di folder `backend`.
- PostgreSQL dikonfigurasi untuk database `monpkl`.
- Migration inti dibuat untuk `users`, `students`, `study_programs`, `internship_periods`, `internship_enrollments`, `internship_places`, `cities`, dan `check_ins`.
- Model dan relasi Eloquent dibuat untuk tabel inti.
- Auth dasar dipasang dengan Laravel Breeze.
- Login Google disiapkan dengan Laravel Socialite melalui route `/auth/google` dan `/auth/google/callback`.
- SSO Google dibatasi untuk domain `unila.ac.id` dan `*.unila.ac.id`.
- Role dasar dibuat melalui middleware `role`.
- Seeder awal dibuat untuk admin, prodi default, dan periode default.
- Migration spatial tambahan dibuat agar tetap kompatibel dengan PostgreSQL/PostGIS dan MariaDB/MySQL.
- PostGIS aktif di database `monpkl`; kolom `location geography(Point, 4326)` dan index GiST sudah tersedia.
- SMTP Gmail dikonfigurasi untuk pengiriman email aplikasi.

Catatan tersisa:

- Google OAuth harus memiliki authorized redirect URI yang sama dengan `.env`, yaitu `http://localhost:8001/auth/google/callback` untuk development lokal.

1. Buat project Laravel.
2. Konfigurasi PostgreSQL dan PostGIS.
3. Buat migration tabel inti.
4. Buat model dan relasi:
   - `User`
   - `Student`
   - `StudyProgram`
   - `InternshipPeriod`
   - `InternshipEnrollment`
   - `InternshipPlace`
   - `City`
   - `CheckIn`
5. Tambahkan auth dengan Laravel Breeze/Fortify.
6. Tambahkan Google login dengan Laravel Socialite jika login Google tetap dipakai.
7. Buat role dan middleware.

Output tahap ini:

- Backend bisa login.
- Database schema siap.
- Role dasar berjalan.

### Tahap 3 - Migrasi Data Firebase ke Database Baru

Status: selesai.

Hasil implementasi:

- Command `php artisan import:firebase-json` dibuat.
- Import `master/kota` ke `cities` berjalan.
- `study_programs` dan `internship_periods` dibuat/dipilih dari metadata import.
- `pkl` diimport ke `internship_places`.
- `users` diimport ke `users` dan `students`.
- `internship_enrollments` dibentuk dari relasi mahasiswa, prodi, periode, instansi, dan data legacy.
- `mon_pkl` diimport ke `check_ins` melalui `internship_enrollment_id`.
- `mon_user` digunakan sebagai validasi silang, bukan sumber utama.
- `distance_meters` dihitung ulang saat import memakai Haversine server-side.
- Report hasil import dibuat otomatis di `storage/app/private/import-reports`.

Catatan hasil import:

- Data utama berhasil masuk ke PostgreSQL.
- Import command aman dijalankan ulang karena memakai legacy key Firebase pada tempat PKL dan check-in.
- Ada 2 mismatch antara `mon_pkl` dan `mon_user`; `mon_pkl` tetap menjadi sumber utama.

1. Buat command Laravel, misalnya `php artisan import:firebase-json`.
2. Import `master/kota` ke tabel `cities`.
3. Buat atau pilih `study_programs` dan `internship_periods` berdasarkan matriks export Tahap 1.
4. Import `pkl` ke `internship_places`.
5. Import `users` ke `users` dan `students`.
6. Bentuk `internship_enrollments` dari relasi mahasiswa, prodi, periode, dosen, pembimbing, dan instansi.
7. Import `mon_pkl` ke `check_ins` melalui `internship_enrollment_id`.
8. Abaikan duplikasi `mon_user` sebagai sumber utama, tetapi gunakan untuk validasi silang jumlah check-in per NPM/periode/prodi.
9. Hitung dan simpan `distance_meters` saat import.
10. Buat laporan hasil import:
   - jumlah lokasi PKL.
   - jumlah user.
   - jumlah mahasiswa.
   - jumlah enrollment per periode/prodi.
   - jumlah check-in.
   - data gagal import.
   - data koordinat tidak valid.

Output tahap ini:

- Database baru berisi data historis.
- Ada log import yang bisa diaudit.

### Tahap 4 - Fondasi Manajemen Data

Status: selesai sebagian.

Tujuan tahap ini adalah menyiapkan menu manajemen sebelum fitur operasional diperluas. Tahap ini penting agar peta, check-in, dan laporan tidak dibangun di atas struktur data yang masih bercampur antara master dan data periode.

Hasil implementasi:

- Menu `Manajemen` ditambahkan untuk role `admin`.
- Dashboard manajemen dibuat di `/management`.
- Manajemen `User` dibuat untuk akun `admin`, `dosen`, dan `mahasiswa`.
- Manajemen `Mahasiswa` dibuat untuk NPM, nama, prodi asal, dan relasi akun login.
- Manajemen `Prodi` dibuat untuk kode, nama, fakultas, dan status aktif.
- Manajemen `Periode PKL` dibuat untuk nama periode, tahun akademik, semester/gelombang, tanggal mulai/akhir, status aktif, dan status terkunci.
- Manajemen `Master Tempat PKL` dibuat sebagai daftar master instansi dan terhubung ke form input/edit tempat PKL yang sudah memakai Leaflet.
- Bulk action `Master Tempat PKL` dibuat:
  - `hapus`: hanya menghapus tempat PKL dengan jumlah peserta 0.
  - `merge`: menggabungkan beberapa tempat PKL ke satu tempat tujuan, memindahkan semua peserta/enrollment ke tujuan, lalu menghapus data sumber.
- Manajemen `Peserta Periode / Penempatan PKL` dibuat untuk menghubungkan mahasiswa, periode, prodi, tempat PKL, dosen pembimbing, pembimbing lapangan, kontak mahasiswa, dan status peserta.
- Pembimbing lapangan adalah data bebas per penempatan/enrollment karena berasal dari instansi tempat PKL.
- Master Dosen dibuat pada tabel `lecturers` untuk menyimpan nama, email, NIP, NIDN, prodi, akun login, dan status aktif/nonaktif.
- Seeder `LecturerSeeder` dibuat untuk mengisi data dosen awal ILKOM/FMIPA, menormalisasi NIP tanpa spasi, membuat/menautkan akun user role `dosen`, dan menandai status dosen sebagai `active`.
- Dosen pembimbing harus berasal dari master dosen. Relasi `lecturer_supervisor_id` sudah ditambahkan ke enrollment dengan fallback ke `lecturer_supervisor_user_id` dan field legacy `lecturer_supervisor`.
- Form penempatan peserta sudah memilih dosen pembimbing dari master dosen aktif.
- Filter peta dan laporan untuk role dosen sudah memakai relasi dosen pembimbing baru, dengan fallback ke data legacy.
- Master Tempat PKL dirapikan agar pembimbing lapangan tidak diposisikan sebagai data master permanen; field kontak pada master diperlakukan sebagai kontak umum instansi.
- Test fitur manajemen dibuat di `backend/tests/Feature/ManagementFeatureTest.php`.

Prioritas implementasi:

1. Buat menu **Manajemen** sebagai kelompok menu admin. **Selesai sebagian.**
2. Buat CRUD master. **Selesai sebagian:**
   - User.
   - Mahasiswa.
   - Dosen melalui tabel `lecturers`.
   - Prodi.
   - Tempat PKL.
3. Rapikan **Master Tempat PKL** agar hanya berisi data umum instansi:
   - nama instansi.
   - alamat.
   - provinsi.
   - kab/kota.
   - koordinat.
   - kontak umum.
   - status aktif.
4. Buat CRUD **Periode PKL**. **Selesai sebagian:**
   - nama periode.
   - tahun akademik.
   - semester/gelombang.
   - tanggal mulai/akhir.
   - status aktif.
   - status terkunci.
5. Buat menu **Peserta Periode / Penempatan PKL**. **Selesai sebagian:**
   - pilih periode.
   - pilih prodi.
   - pilih mahasiswa.
   - pilih tempat PKL dari master.
   - tentukan dosen pembimbing dari daftar dosen di database.
   - tentukan pembimbing lapangan untuk periode tersebut sebagai input bebas.
   - simpan kontak mahasiswa untuk periode tersebut.
   - status peserta.
6. Tambahkan import Excel/CSV untuk peserta periode bila diperlukan. **Belum dimulai.**

Output tahap ini:

- Menu manajemen tersedia untuk admin.
- Master tempat PKL mulai dipisah dari mahasiswa/pembimbing periode.
- Duplikasi master tempat PKL dapat dibersihkan lewat bulk merge tanpa kehilangan peserta.
- Penempatan peserta PKL per periode tersedia sebagai sumber utama untuk check-in, monitoring, dan laporan.

Catatan tersisa:

- Form `Input Lokasi` lama masih dapat diakses sebagai pintasan admin/dosen, tetapi secara konsep harus dianggap bagian dari `Master Tempat PKL`.
- Master Dosen sudah dibuat terpisah dari `users`; akun login dosen tetap dapat direlasikan bila tersedia.
- Validasi wajib pembimbing lapangan dan dosen pembimbing sebelum cetak laporan belum dibuat.
- Import Excel/CSV peserta periode belum dibuat.
- Audit log perubahan master data belum dibuat.

### Tahap 5 - Konfigurasi Sistem Per Periode

Status: selesai sebagian.

Tujuan tahap ini adalah memastikan aturan operasional tidak hardcode dan dapat berbeda antar periode PKL.

Hasil implementasi awal:

- Konfigurasi default tersedia di `backend/config/monpkl.php`.
- Konfigurasi database per periode tersedia melalui tabel `internship_period_settings`.
- Menu `Konfigurasi Sistem` tersedia untuk role `admin`.
- Seeder `PeriodConfigurationSeeder` dibuat untuk mengisi konfigurasi pada periode yang sudah ada.

Prioritas lanjutan:

1. Satukan pengelolaan konfigurasi dengan menu **Periode PKL**.
2. Pastikan konfigurasi yang berubah per periode meliputi:
   - jam/status check-in.
   - batas upload foto.
   - hari libur.
   - aturan inferensi laporan.
   - center/zoom/tile peta.
   - opsi geolocation.
   - batas data peta monitoring.
3. Tambahkan validasi konfigurasi agar jam tidak tumpang tindih.
4. Tambahkan audit log perubahan konfigurasi.
5. Saat periode dikunci, konfigurasi juga sebaiknya tidak dapat diubah kecuali oleh admin khusus.

Output tahap ini:

- Semua aturan operasional penting dapat dikonfigurasi per periode.
- Perubahan aturan semester depan tidak perlu mengubah kode.

### Tahap 6 - Workflow Mahasiswa dan Pendaftaran PKL

Status: belum dimulai.

Tujuan tahap ini adalah membuat mahasiswa dapat mendaftar, melengkapi profil, memilih/mengajukan tempat PKL, menjalankan PKL, dan melihat laporan individunya sendiri.

Prioritas implementasi:

1. Buat dashboard mahasiswa yang menampilkan:
   - status profil.
   - status pendaftaran periode aktif.
   - tempat PKL.
   - tombol presensi hari ini.
   - ringkasan hadir/terlambat/pulang cepat.
   - notifikasi revisi dari admin.
2. Buat form **Profil Saya**:
   - NPM.
   - nama lengkap.
   - email student.
   - nomor HP.
   - prodi.
3. Tambahkan validasi agar mahasiswa harus melengkapi profil sebelum mendaftar PKL.
4. Buat form **Pendaftaran PKL**:
   - pilih periode aktif.
   - pilih prodi.
   - pilih tempat PKL dari master.
   - isi kontak mahasiswa.
   - isi pembimbing lapangan jika sudah diketahui.
5. Tambahkan status enrollment:
   - `draft`.
   - `pending_verification`.
   - `revision_required`.
   - `active`.
   - `completed`.
   - `cancelled`.
   - `rejected`.
6. Buat fitur **Usulan Tempat PKL Baru**:
   - mahasiswa mengisi nama instansi, alamat, kota, koordinat, dan kontak pembimbing lapangan.
   - data masuk sebagai proposal, bukan langsung master.
   - admin dapat menyetujui, menolak, atau merge dengan master tempat PKL yang sudah ada.
7. Buat halaman admin untuk validasi pendaftaran mahasiswa:
   - set dosen pembimbing dari daftar user/dosen di database.
   - lengkapi atau koreksi pembimbing lapangan sebagai input bebas.
   - set status enrollment.
   - beri catatan revisi/penolakan.
8. Ubah akses presensi agar hanya enrollment `active` yang dapat melakukan check-in/check-out resmi.
9. Buat halaman **Laporan Saya** untuk mahasiswa.
10. Buat halaman **Cetak Laporan** berbasis backend/Blade print view dengan validasi kelengkapan dosen pembimbing dan pembimbing lapangan.

Output tahap ini:

- Mahasiswa dapat menjalani workflow PKL secara mandiri.
- Admin tetap memvalidasi data resmi sebelum dipakai untuk presensi dan laporan.
- Usulan tempat PKL baru tidak mencemari master sebelum disetujui.
- Dosen pembimbing tersimpan sebagai referensi ke data dosen, sedangkan pembimbing lapangan tersimpan sebagai data bebas per enrollment.
- Cetak laporan memiliki prasyarat data pembimbing yang jelas sehingga laporan resmi tidak kosong atau salah pembimbing.

### Tahap 7 - Migrasi Peta ke Leaflet

Status: selesai.

Hasil implementasi:

- Peta tempat PKL baru dibuat dengan Leaflet dan OpenStreetMap tile.
- Peta monitoring baru dibuat dengan Leaflet, markercluster, marker lokasi mahasiswa, marker instansi, dan polyline.
- Peta input lokasi instansi baru dibuat dengan Leaflet picker.
- Peta check-in baru dibuat dengan Leaflet, geolocation browser, marker lokasi mahasiswa, marker instansi, dan polyline.
- Endpoint data peta dibuat dari database PostgreSQL hasil import Tahap 3.
- Hak akses data peta disesuaikan per role.
- Navigasi aplikasi ditambah menu `Tempat PKL`, `Monitoring`, `Input Lokasi`, dan `Check-In` sesuai role.
- Dashboard ditambah pintasan ke peta.

Prioritas halaman:

1. `tempat-pkl.html` / peta lokasi PKL.
2. `input.html` / input lokasi instansi.
3. `check-in.html` / rekam kehadiran.
4. `monitoringmap.html` / peta monitoring mahasiswa.

Langkah teknis:

1. Buat komponen peta bersama, misalnya `resources/js/maps/leafletMap.js`.
2. Ganti script Google Maps dengan Leaflet CSS/JS.
3. Ganti marker Google Maps dengan `L.marker`.
4. Ganti `InfoWindow` dengan `bindPopup`.
5. Ganti `MarkerClusterer` dengan `leaflet.markercluster`.
6. Ganti `Polyline` dengan `L.polyline`.
7. Ganti perhitungan jarak:
   - untuk tampilan cepat: Haversine/Turf.js di browser.
   - untuk data resmi: hitung di server dan simpan ke database.
8. Ganti link arah:
   - `https://www.openstreetmap.org/directions?from={lat},{lng}&to={lat},{lng}`
   - atau integrasi OSRM/GraphHopper jika butuh routing.

Output tahap ini:

- Semua peta berjalan tanpa Google Maps API.
- Tidak ada script `maps.googleapis.com`.
- Tidak ada `google.maps.*` di kode baru.

### Tahap 8 - Migrasi Fitur Check-In

Status: selesai sebagian.

Hasil implementasi:

- Form check-in baru dibuat di backend Laravel.
- Check-in hanya dapat dilakukan oleh role `mahasiswa`.
- Endpoint check-in mengambil enrollment aktif dari user login, sehingga mahasiswa tidak bisa memalsukan NPM/user milik orang lain.
- Browser mengirim lokasi GPS, catatan, dan foto opsional.
- Server menentukan status check-in berdasarkan jam server.
- Server menghitung jarak dari lokasi mahasiswa ke lokasi instansi.
- Server menyimpan data perangkat sebagai JSON.
- Data check-in baru disimpan di tabel `check_ins`.

Catatan tersisa:

- Validasi anti-spoofing lanjutan seperti batas radius maksimum, deteksi mock location, dan rate limit per hari belum diterapkan.
- Migrasi file foto lama dari Firebase Storage belum dilakukan.
- Jika butuh aturan jadwal berbeda per prodi/periode/instansi, jam kerja sebaiknya dipindah ke tabel konfigurasi.

1. Pindahkan validasi jam masuk/pulang ke server.
2. Browser hanya mengirim:
   - lokasi GPS.
   - catatan.
   - tipe aksi yang diminta jika diperlukan.
   - foto jika ada.
3. Server menentukan status:
   - `Masuk`
   - `Datang Terlambat`
   - `Pulang Cepat`
   - `Pulang`
   - `Tidak Aktif`
4. Server menghitung jarak dari lokasi mahasiswa ke instansi.
5. Server menyimpan device info sebagai JSON.
6. Tambahkan proteksi agar mahasiswa tidak bisa memalsukan NPM/user milik orang lain.

Output tahap ini:

- Endpoint check-in aman.
- Data check-in hanya ditulis melalui server.

### Tahap 9 - Migrasi Laporan dan Rekap

1. Pindahkan logika laporan dari JavaScript besar ke service Laravel.
2. Pindahkan daftar hari libur dari hardcoded array ke tabel `holidays`.
3. Buat endpoint/filter:
   - per mahasiswa.
   - per dosen.
   - per periode.
   - per prodi.
   - per instansi.
   - per tanggal.
4. Render laporan dengan Blade.
5. Jika butuh PDF, gunakan Dompdf, Browsershot, atau wkhtmltopdf.

Output tahap ini:

- Laporan konsisten antara tampilan, print, dan export.
- Tidak perlu mengambil semua data ke browser untuk dihitung.

### Tahap 10 - Cutover dan Decommission Firebase

1. Jalankan aplikasi baru dalam mode paralel untuk uji data.
2. Bandingkan laporan lama vs laporan baru.
3. Perbaiki perbedaan hasil import/perhitungan.
4. Tentukan tanggal cutover.
5. Export Firebase terakhir.
6. Import delta data.
7. Arahkan domain ke aplikasi baru.
8. Nonaktifkan write Firebase.
9. Arsipkan konfigurasi Firebase dan Google Maps API key.

Output tahap ini:

- Aplikasi produksi tidak lagi bergantung pada Firebase Database, Firebase Storage, atau Google Maps.

## Strategi Migrasi Bertahap yang Aman

Urutan paling aman:

1. Bangun backend dan database baru.
2. Import data historis.
3. Rapikan menu manajemen dan pisahkan master data dari data periode.
4. Siapkan konfigurasi sistem per periode.
5. Buat penempatan peserta PKL per periode sebagai sumber utama operasional.
6. Buat workflow mahasiswa untuk profil, pendaftaran PKL, usulan tempat, dan validasi admin.
7. Buat API data peta dari struktur periode yang sudah rapi.
8. Migrasi halaman peta ke Leaflet.
9. Migrasi check-in/check-out ke backend.
10. Migrasi laporan.
11. Matikan Firebase.

Dengan strategi ini, bentuk data lama masih bisa dimanfaatkan sementara, tetapi arah akhirnya tetap bersih dan terstruktur. Urutan ini juga mencegah revisi besar di belakang, karena fitur operasional selalu membaca data dari master dan penempatan periode yang sudah jelas.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Data Firebase tidak konsisten antara `mon_pkl` dan `mon_user` | Jadikan `mon_pkl` sumber utama, gunakan `mon_user` untuk validasi |
| Data historis tidak punya periode/prodi eksplisit | Tetapkan metadata import per file export dan simpan `legacy_source_file` serta `legacy_period_label` |
| Mahasiswa ikut lebih dari satu periode atau pindah instansi | Simpan penempatan di `internship_enrollments`, bukan langsung di `students` |
| Dosen pembimbing diketik bebas dan tidak konsisten | Wajib pilih dari daftar dosen di database, simpan referensi user/dosen pada `internship_enrollments` |
| Pembimbing lapangan belum diketahui saat pendaftaran | Izinkan kosong sementara, tetapi jadikan wajib sebelum cetak laporan |
| Koordinat kosong/salah format | Buat import validator dan laporan data gagal |
| Hasil jarak berbeda dari Google Maps | Tetapkan metode resmi: Haversine atau PostGIS `ST_DistanceSphere` |
| Tile OpenStreetMap dibatasi | Gunakan provider tile resmi/berbayar atau self-host |
| Login Google berubah dari Firebase ke Socialite | Simpan mapping email dan `google_id`; lakukan uji login semua role |
| Foto lama di Firebase Storage | Download dan migrasikan ke storage baru, atau simpan URL lama sementara |
| Laporan berubah karena logika dipindah | Buat fixture data dan bandingkan output lama vs baru |

## Checklist Teknis Penghapusan Ketergantungan Lama

Firebase:

- Hapus script `https://www.gstatic.com/firebasejs/...`.
- Hapus `firebase.initializeApp`.
- Hapus akses `firebase.database()`.
- Hapus akses `firebase.auth()`.
- Hapus akses `firebase.storage()`.
- Pindahkan rules/otorisasi ke middleware dan policy Laravel.
- Arsipkan `firebase.json`, `.firebaserc`, dan `database.rules.json`.

Google Maps:

- Hapus script `https://maps.googleapis.com/maps/api/js?...`.
- Hapus `markerclusterer.js` lama.
- Ganti semua `google.maps.Map`.
- Ganti semua `google.maps.Marker`.
- Ganti semua `google.maps.InfoWindow`.
- Ganti semua `google.maps.Polyline`.
- Ganti semua `google.maps.LatLng`.
- Ganti semua `google.maps.geometry.spherical.computeDistanceBetween`.
- Ganti link direction Google Maps.

## Rekomendasi Struktur Project Baru

```text
app/
  Http/Controllers/
    CheckInController.php
    InternshipPlaceController.php
    MonitoringController.php
    ReportController.php
  Models/
    User.php
    Student.php
    StudyProgram.php
    InternshipPeriod.php
    InternshipEnrollment.php
    InternshipPlace.php
    CheckIn.php
    City.php
    Holiday.php
  Services/
    DistanceService.php
    CheckInService.php
    ReportService.php
  Console/Commands/
    ImportFirebaseJson.php

database/
  migrations/
  seeders/

resources/
  views/
    dashboard.blade.php
    check-ins/
    internship-places/
    monitoring/
    reports/
  js/
    maps/
      leafletMap.js
      checkInMap.js
      monitoringMap.js

storage/
  app/public/check-in-photos/
```

## Prioritas Implementasi

Prioritas 1:

- Database schema.
- Import data.
- Auth dan role.
- Menu manajemen dasar.
- Master periode, prodi, mahasiswa, dosen, user, dan tempat PKL.
- Konfigurasi sistem per periode.

Prioritas 2:

- Peserta periode / penempatan PKL.
- Workflow mahasiswa: profil, pendaftaran PKL, usulan tempat, dan validasi admin.
- Relasi dosen pembimbing dari enrollment ke data dosen, serta validasi pembimbing lapangan sebelum cetak laporan.
- Filter dasar periode dan prodi.
- Peta lokasi PKL dengan Leaflet.
- Check-in baru.
- Upload foto.

Prioritas 3:

- Monitoring harian.
- Laporan lengkap.
- Export PDF/Excel.
- Dashboard admin/dosen.

Prioritas 4:

- Optimasi PostGIS.
- Audit log.
- Notifikasi.
- Integrasi SSO kampus.

## Kesimpulan

Migrasi terbaik bukan menambal project statis lama, tetapi membangun backend yang jelas lalu memindahkan fitur satu per satu. Laravel + PostgreSQL/PostGIS + Leaflet memberi jalur migrasi yang realistis: tetap sederhana untuk tim kampus, cukup kuat untuk data lokasi, dan tidak lagi bergantung pada Firebase Database maupun Google Maps.

Tahapan paling penting adalah import data historis dan migrasi check-in, karena dua bagian ini menyangkut integritas data. Migrasi Leaflet relatif jelas secara teknis, tetapi tetap harus dilakukan bersama perubahan perhitungan jarak dan clustering marker.
