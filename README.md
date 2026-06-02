# SiLAT

**SiLAT (Sistem Laporan Aktivitas Terpadu MBKM & Kerja Praktik)** adalah sistem informasi untuk mengelola pendaftaran, penempatan, presensi, monitoring lokasi, progres laporan, dan rekap aktivitas mahasiswa pada program MBKM dan Kerja Praktik.

Sistem ini merupakan rebranding dan pengembangan dari Mon PKL. Rule operasional awal masih mengikuti Kerja Praktik, tetapi struktur data sudah menyiapkan master **Program Kegiatan** agar program seperti Kerja Praktik, Magang, Riset, dan program MBKM lain dapat memakai aturan yang berbeda di masa datang.

## Fitur Utama

- Autentikasi email/password dan Google SSO dengan pembatasan domain Unila.
- Role `admin`, `dosen`, `mahasiswa`, serta akses `koordinator` berdasarkan penugasan aktif.
- Master data Program Kegiatan, Periode Program, Prodi, Mahasiswa, Dosen, User, dan Mitra.
- Workflow pendaftaran program, validasi admin/koordinator, revisi pendaftaran, pindah mitra, dan perubahan pembimbing.
- Presensi masuk/pulang berbasis lokasi, kamera realtime, jarak Haversine, durasi harian, dan sanksi durasi kurang.
- Catatan harian berbasis pasangan presensi: catatan masuk sebagai rencana aktivitas, catatan pulang sebagai realisasi.
- Progres laporan mahasiswa, deadline periode, sanksi keterlambatan unggahan, dan review laporan oleh dosen/admin/koordinator.
- Peta Leaflet untuk peta mitra, peta monitoring, picker lokasi, dan peta check-in.
- Rekap monitoring dan halaman print laporan dengan grafik kehadiran, durasi, jarak, dan tabel rekapitulasi.
- Import data historis dari Firebase JSON.

## Stack Teknologi

- Laravel 12
- PHP 8.2+
- PostgreSQL dengan dukungan PostGIS
- Laravel Breeze dan Socialite
- Leaflet dan Leaflet MarkerCluster
- Tailwind CSS, Alpine.js, dan Vite
- PHPUnit untuk test otomatis

## Struktur Repo

Repo ini adalah aplikasi Laravel langsung di root project.

```text
app/                 Controller, model, service, middleware
database/            Migration, seeder, factory
resources/views/     Blade template
resources/js/        JavaScript frontend
resources/css/       CSS aplikasi
routes/              Route web dan console
public/              Asset publik dan build Vite
storage/             File runtime, upload, report import
tests/               Unit dan feature test
docs/                Catatan audit dan tahapan desain UI
SRS.md               Spesifikasi kebutuhan sistem
README_MONPKL.md     Catatan teknis lama Mon PKL/PostGIS
```

## Kebutuhan Lokal

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan npm
- PostgreSQL
- PostGIS, direkomendasikan untuk fitur spatial

Default database lokal pada `.env.example`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=monpkl
DB_USERNAME=monpkl
DB_PASSWORD=123
```

## Setup Lokal

1. Install dependency PHP.

```bash
composer install
```

2. Siapkan environment.

```bash
cp .env.example .env
php artisan key:generate
```

3. Sesuaikan konfigurasi database, mail, Google SSO, dan parameter `MONPKL_*` pada `.env`.

4. Aktifkan PostGIS sekali pada database PostgreSQL, jika memakai PostgreSQL/PostGIS.

```sql
CREATE EXTENSION IF NOT EXISTS postgis;
```

5. Jalankan migration dan seeder.

```bash
php artisan migrate
php artisan db:seed
```

6. Buat symbolic link storage untuk akses file upload publik.

```bash
php artisan storage:link
```

7. Install dependency frontend dan build asset.

```bash
npm install
npm run build
```

8. Jalankan server lokal.

```bash
php artisan serve
```

Untuk mode pengembangan frontend:

```bash
npm run dev
```

Composer script juga menyediakan mode gabungan:

```bash
composer run dev
```

## Konfigurasi Penting

### Google SSO

```env
SSO_GOOGLE_ENABLED=false
SSO_GOOGLE_ALLOWED_DOMAINS=unila.ac.id,*.unila.ac.id
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
SSO_GOOGLE_CAFILE=
SSO_GOOGLE_VERIFY_SSL=true
```

Jika terjadi masalah sertifikat lokal pada Windows atau hosting, isi `SSO_GOOGLE_CAFILE` dengan CA bundle. Nilai relatif akan otomatis dibaca dari `storage/app`.

```env
SSO_GOOGLE_CAFILE=certs/cacert.pem
```

Dengan konfigurasi tersebut, file CA diletakkan di:

```text
storage/app/certs/cacert.pem
```

Path absolut tetap didukung jika benar-benar diperlukan, tetapi path relatif lebih portabel untuk local dan hosting.

### Presensi dan Peta

Parameter utama presensi dan peta berada pada `.env` dengan prefix `MONPKL_*`, antara lain:

```env
MONPKL_TIMEZONE=Asia/Jakarta
MONPKL_CHECKIN_PHOTO_MAX_KB=4096
MONPKL_CHECKIN_MAX_DISTANCE_METERS=5000
MONPKL_CHECKIN_INACTIVE_MESSAGE="Check-in hanya dapat dilakukan pada jam kerja 07.00 sampai 19.00."
MONPKL_MAP_CENTER_LAT=-5.3971
MONPKL_MAP_CENTER_LNG=105.2668
MONPKL_MAP_TILE_URL="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
```

Konfigurasi per periode dapat diubah melalui menu **Konfigurasi Sistem**, termasuk jadwal presensi, deadline, kuota, peta, dan aturan laporan.

## Import Data Historis

Command import data Firebase JSON tersedia untuk migrasi data lama.

```bash
php artisan import:firebase-json
```

Laporan hasil import disimpan di storage aplikasi. Detail migrasi dapat dilihat pada `MIGRASI_FIREBASE_GOOGLEMAP.md`.

## Test dan Build

Jalankan seluruh test:

```bash
php artisan test
```

Jalankan build frontend:

```bash
npm run build
```

Perintah yang sering dipakai saat pengembangan:

```bash
php artisan route:list
php artisan migrate
php artisan db:seed
php artisan config:clear
```

## Dokumen Terkait

- `SRS.md`: spesifikasi kebutuhan sistem SiLAT.
- `ui-design.md`: rancangan dan tahapan redesain UI.
- `ui-table-design.md`: pedoman komponen tabel.
- `MIGRASI_FIREBASE_GOOGLEMAP.md`: catatan migrasi Firebase/Google Maps ke Laravel/PostgreSQL/Leaflet.
- `README_MONPKL.md`: catatan teknis lama terkait database, PostGIS, mail, dan MariaDB.

## Catatan Produksi

- Simpan kredensial asli hanya di `.env`, bukan di repository.
- Pastikan storage upload tidak dibuka publik tanpa kontrol akses jika kebijakan produksi mensyaratkan file privat.
- Siapkan backup database harian dan prosedur restore.
- Untuk data lokasi skala besar, gunakan PostgreSQL/PostGIS dan index spatial.
