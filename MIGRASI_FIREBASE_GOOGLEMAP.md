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

Terakhir diperbarui: 30 Mei 2026.

| Tahap | Status | Catatan |
| --- | --- | --- |
| Tahap 1 - Inventarisasi dan Freeze Data | Selesai | Artefak tersedia di `docs/migration/stage-1`. Sudah ada mapping field, manifest backup, runbook freeze, inventaris fitur, dan matriks awal periode/prodi. |
| Tahap 2 - Bangun Backend Baru | Selesai | Backend Laravel 12 dibuat di folder `backend`. PostgreSQL `monpkl` sudah dikonfigurasi, migration inti dan spatial PostGIS sudah berjalan, auth Breeze tersedia, SSO Google Unila sudah dikonfigurasi, SMTP Gmail sudah dikonfigurasi, model/relasi inti dan role middleware sudah dibuat. |
| Tahap 3 - Migrasi Data Firebase ke Database Baru | Belum dimulai | Tahap berikutnya: buat command import Firebase JSON dan laporan hasil import. |
| Tahap 4 - Migrasi Peta ke Leaflet | Belum dimulai | Menunggu data/API backend hasil Tahap 3. |
| Tahap 5 - Migrasi Fitur Check-In | Belum dimulai | Menunggu endpoint dan service check-in backend. |
| Tahap 6 - Migrasi Laporan dan Rekap | Belum dimulai | Menunggu data historis berhasil diimport. |
| Tahap 7 - Cutover dan Decommission Firebase | Belum dimulai | Dilakukan setelah aplikasi baru tervalidasi paralel. |

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
- lecturer_supervisor
- field_supervisor
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
- field_supervisor_name
- field_supervisor_phone
- contact_student_phone
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

### Tahap 4 - Migrasi Peta ke Leaflet

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

### Tahap 5 - Migrasi Fitur Check-In

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

### Tahap 6 - Migrasi Laporan dan Rekap

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

### Tahap 7 - Cutover dan Decommission Firebase

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
3. Buat API kompatibel yang mengeluarkan data GeoJSON mirip struktur lama.
4. Migrasi halaman peta ke Leaflet.
5. Migrasi check-in ke backend.
6. Migrasi laporan.
7. Matikan Firebase.

Dengan strategi ini, bentuk data lama masih bisa dimanfaatkan sementara, tetapi arah akhirnya tetap bersih dan terstruktur.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Data Firebase tidak konsisten antara `mon_pkl` dan `mon_user` | Jadikan `mon_pkl` sumber utama, gunakan `mon_user` untuk validasi |
| Data historis tidak punya periode/prodi eksplisit | Tetapkan metadata import per file export dan simpan `legacy_source_file` serta `legacy_period_label` |
| Mahasiswa ikut lebih dari satu periode atau pindah instansi | Simpan penempatan di `internship_enrollments`, bukan langsung di `students` |
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
- Filter dasar periode dan prodi.
- Peta lokasi PKL dengan Leaflet.

Prioritas 2:

- Check-in baru.
- Upload foto.
- Monitoring harian.

Prioritas 3:

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
