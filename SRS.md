# **Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SRS) - Revisi 2.0**

## **Sistem Monitoring PKL (MonPKL) – Universitas Lampung**

> Berdasarkan dokumen:
> - `MIGRASI_FIREBASE_GOOGLEMAP.md` (arsitektur target)
> - `Buku Panduan Kerja Praktik Periode II 2025` (peraturan operasional)

---

## 1. Pendahuluan

### 1.1 Tujuan
Dokumen ini mendefinisikan kebutuhan fungsional dan non‑fungsional untuk pengembangan **MonPKL**, sistem informasi manajemen Praktik Kerja Lapangan (PKL) Fakultas MIPA Universitas Lampung. Sistem menggantikan arsitektur Firebase/Google Maps dengan backend Laravel, database PostgreSQL/PostGIS, dan peta Leaflet.

### 1.2 Ruang Lingkup
- Autentikasi (Google SSO domain unila.ac.id, email/password)
- Manajemen master data: prodi, mahasiswa, dosen, tempat PKL, periode
- Konfigurasi operasional per periode (jam kerja, deadline, kuota, sanksi)
- Pendaftaran PKL mahasiswa, usulan tempat baru, validasi admin
- **Check‑in ganda (masuk & pulang)** dengan perhitungan durasi harian
- **Manajemen progres laporan** (upload Bab, deadline, sanksi keterlambatan)
- **Penilaian numerik** dari pembimbing lapangan dan dosen pembimbing
- **Catatan harian** dan dukungan paraf (cetak form)
- Peta (Leaflet) untuk lokasi tempat PKL dan monitoring
- Laporan rekapitulasi, pelanggaran, dan nilai akhir
- Ekspor laporan (PDF/Excel)

### 1.3 Definisi dan Istilah

| Istilah | Definisi |
|---------|-----------|
| PKL | Praktik Kerja Lapangan, mata kuliah wajib (3 SKS). |
| Periode PKL | Rentang waktu pelaksanaan PKL dengan jadwal deadline dan aturan sendiri. |
| Enrollment | Penempatan resmi mahasiswa pada suatu periode PKL. |
| Check‑in Masuk | Presensi pagi (status *Masuk* atau *Datang Terlambat*). |
| Check‑in Pulang | Presensi sore (status *Pulang* atau *Pulang Cepat*). |
| Durasi Harian | Selisih waktu antara check‑in pulang dan check‑in masuk (minimal 6 jam). |
| Progres Laporan | Unggah dokumen sesuai tahapan (Bab I, II, dst) dengan deadline. |
| Sanksi | Pengurangan nilai otomatis akibat keterlambatan atau durasi kurang. |
| Konfigurasi | Parameter yang dapat diubah oleh admin per periode atau global. |

---

## 2. Kebutuhan Fungsional (Lengkap dengan Penambahan)

> Notasi: **[KONFIG]** = data yang dapat diatur melalui halaman konfigurasi (per periode atau global).

### 2.1 Manajemen Master Data

| ID | Kebutuhan |
|----|-----------|
| MF-01 | CRUD Program Studi (kode, nama, fakultas, is_active). |
| MF-02 | CRUD Mahasiswa (NPM, nama, email, no HP, prodi, user_id). |
| MF-03 | CRUD Dosen (nama, email, NIP, NIDN, prodi, status aktif, user_id). |
| MF-04 | CRUD Periode PKL (nama, tahun akademik, semester, batch, starts_at, ends_at, is_active, is_locked). |
| MF-05 | CRUD Master Tempat PKL (nama, alamat, kota, provinsi, koordinat, kontak umum, is_active). |
| MF-06 | Bulk action pada Master Tempat PKL: hapus (jika tidak ada enrollment), merge ke tujuan. |
| MF-07 | CRUD User (akun login dengan role `admin`, `dosen`, `mahasiswa`). |
| MF-08 | Manajemen Koordinator PKL (dosen, periode, prodi, status aktif). Satu periode‑prodi hanya boleh satu koordinator. |
| MF-09 | **[KONFIG]** Atur **kuota minimal & maksimal mahasiswa per tempat PKL** (default min=2, max=3). Sistem menolak enrollment jika melebihi max. |

### 2.2 Konfigurasi Sistem (Per Periode & Global)

| ID | Kebutuhan |
|----|-----------|
| KS-01 | Setiap Periode PKL memiliki **konfigurasi operasional** (`internship_period_settings`), mencakup: <br> - **Jam kerja & status**: rentang waktu untuk *Masuk*, *Datang Terlambat*, *Pulang Cepat*, *Pulang* **[KONFIG]** <br> - **Durasi minimal harian** (default 6 jam) dan satuan sanksi per jam kurang **[KONFIG]** <br> - **Batas maksimum jarak** (meter) untuk validasi check‑in (opsional) **[KONFIG]** <br> - **Radius bumi** untuk Haversine (default 6371 km) **[KONFIG]** <br> - **Daftar hari libur** (dapat dikelola per periode atau nasional) **[KONFIG]** <br> - **Aturan laporan**: apakah hari Sabtu/Minggu dihitung? **[KONFIG]** <br> - **Parameter peta**: center, zoom, tile URL **[KONFIG]** <br> - **Batas upload foto**: ukuran (MB), dimensi (px) **[KONFIG]**,  <br> - Atur kuota minimal & maksimal mahasiswa per tempat PKL **[KONFIG]**|
| KS-02 | **Deadline per periode** dikelola dalam tabel `period_deadlines` dengan jenis: <br> - Pendaftaran dibuka/ditutup <br> - Batas Proposal Rencana Kerja <br> - Batas Bab I, II, III, IV, V <br> - Batas Laporan Lengkap <br> - Batas Seminar <br> - Batas penyerahan hardcover <br> Setiap deadline dapat memiliki bobot sanksi poin **[KONFIG]** |
| KS-03 | Hanya admin yang dapat mengubah konfigurasi periode. Periode terkunci (`is_locked=true`) tidak dapat diubah kecuali oleh admin super. |

### 2.3 Workflow Mahasiswa (Pendaftaran & Pelaksanaan)

| ID | Kebutuhan |
|----|-----------|
| WM-01 | Mahasiswa melengkapi **Profil Saya** (NPM, nama, email, no HP, prodi). Wajib diisi sebelum pendaftaran PKL. |
| WM-02 | **Pendaftaran PKL**: pilih periode aktif, pilih tempat PKL (atau usulkan baru), isi kontak mahasiswa, isi pembimbing lapangan (opsional). Sistem memeriksa kelayakan: <br> - Telah mengambil mata kuliah KP/PKL di KRS semester ini (data input admin) <br> - Total SKS ≥ 100 (tidak termasuk KP) <br> - Semester ≥ 6 (S1) atau ≥ 4 (D3) <br> - IPK ≥ 2,00 <br> Jika tidak memenuhi, pendaftaran ditolak dengan pesan. |
| WM-03 | **Usulan Tempat PKL Baru** (nama instansi, alamat, kota, koordinat, kontak pembimbing lapangan). Disimpan ke `internship_place_proposals` status `pending`. |
| WM-04 | Admin/Koordinator **memvalidasi pendaftaran**: memilih dosen pembimbing (dari master dosen), mengisi/mengoreksi pembimbing lapangan, mengubah status enrollment (`pending_verification` → `active`/`revision_required`/`rejected`), memberi catatan. |
| WM-05 | Status enrollment: `draft`, `pending_verification`, `revision_required`, `active`, `completed`, `cancelled`, `rejected`. |
| WM-06 | **Pindah tempat PKL**: mahasiswa dapat mengajukan permohonan pindah instansi (alasan, bukti). Admin/Kajur menyetujui/menolak. Setelah disetujui, enrollment diperbarui. |
| WM-07 | Hanya enrollment `active` yang dapat melakukan check‑in. |
| WM-08 | **Laporan Saya**: mahasiswa melihat ringkasan check‑in, progres bimbingan, sanksi, dan nilai. |
| WM-09 | **Cetak Laporan** (PDF) hanya diizinkan jika: dosen pembimbing terpilih DAN pembimbing lapangan terisi. |

### 2.4 Check‑In Ganda, Durasi & Sanksi

| ID | Kebutuhan |
|----|-----------|
| CI-01 | Mahasiswa melakukan **check‑in masuk** (pagi) dan **check‑in pulang** (sore) pada hari yang sama. Sistem mengenali pasangan berdasarkan tanggal dan enrollment. |
| CI-02 | Setiap request check‑in mengirim: koordinat GPS, catatan (opsional), foto (opsional). Server menentukan status (Masuk/Datang Terlambat/Pulang Cepat/Pulang) berdasarkan jam dan konfigurasi periode. |
| CI-03 | Setelah check‑in pulang, sistem menghitung **durasi harian** = waktu_pulang - waktu_masuk. Jika durasi < [durasi_minimal] (default 6 jam), maka catat **sanksi** dengan poin = (durasi_minimal - durasi_aktual) dalam jam. |
| CI-04 | Jika dalam satu hari hanya terdapat satu check‑in (misal hanya masuk), hari itu **tidak dihitung sebagai hari hadir** dan tidak dipasangkan. |
| CI-05 | Server menyimpan device_info (user agent, IP), jarak (Haversine), dan path foto. |
| CI-06 | **Anti‑spoofing**: batas radius maksimum (opsional, lihat KS-01), rate limit (10 request/menit). |
| CI-07 | **Cek kuota harian** : tidak ada pembatasan jumlah check‑in per hari selain satu pasang. |

### 2.5 Manajemen Progres Bimbingan & Unggah Laporan

| ID | Kebutuhan |
|----|-----------|
| PG-01 | Mahasiswa dapat **mengunggah dokumen** progres laporan sesuai jenis yang ditentukan (Proposal, Bab I, Bab II, Bab III, Bab IV, Bab V, Laporan Lengkap, Draft Seminar). |
| PG-02 | Setiap jenis memiliki **deadline** yang ditentukan dalam `period_deadlines`. Sistem mencatat tanggal unggah. |
| PG-03 | **Penilaian otomatis keterlambatan**: <br> - Jika unggah > deadline, hitung jumlah hari terlambat. <br> - Terapkan sanksi poin = hari_lambat × poin_per_hari (dapat dikonfigurasi) atau sanksi tetap 5 poin per bab (default). <br> - Keterlambatan Laporan Lengkap atau Seminar dikenakan sanksi tetap 50 poin. |
| PG-04 | Dosen pembimbing dapat memberi **status** (disetujui / revisi / ditolak) dan catatan pada setiap unggahan. |
| PG-05 | Koordinator PKL dapat melihat **rekap progres** semua mahasiswa di periode/prodi masing‑masing. |
| PG-06 | Setelah seminar dan revisi, mahasiswa mengunggah **Laporan Final** (PDF). Dosen pembimbing menyetujui → status enrollment menjadi `completed`. Laporan final disimpan sebagai arsip. |

### 2.6 Penilaian Numerik

| ID | Kebutuhan |
|----|-----------|
| PN-01 | **Form nilai pembimbing lapangan** (skala 0–100) dengan komponen: <br> - Kedisiplinan (A1: Jumlah Kehadiran, A2: Taat Tata Tertib) → A = (A1+A2)/2 <br> - Kerjasama (B1: dengan kelompok, B2: kelompok lain, B3: pembimbing) → B = (B1+B2+B3)/3 <br> - Prestasi kerja (C1: Inovasi, C2: Kemampuan tugas, C3: Keseriusan) → C = (C1+C2+C3)/3 <br> - **Nilai lapangan** = (A+B+C)/3 |
| PN-02 | **Form nilai dosen pembimbing** : nilai laporan (0–100) dan nilai seminar (0–100). Bobot dapat dikonfigurasi (default laporan 60%, seminar 40%). |
| PN-03 | **Nilai akhir KP/PKL** = (nilai lapangan × bobot_lapangan) + (nilai dosen × bobot_dosen). Nilai akhir dikonversi ke huruf mutu sesuai aturan Unila. |
| PN-04 | Hanya dosen pembimbing yang dapat mengisi nilai laporan & seminar. Hanya admin yang dapat mengesahkan nilai akhir. |

### 2.7 Catatan Harian & Dukungan Paraf

| ID | Kebutuhan |
|----|-----------|
| CH-01 | Mahasiswa dapat mengisi **log harian** (tanggal, uraian kegiatan) selama periode PKL berlangsung. |
| CH-02 | Sistem menyediakan **cetak form bimbingan harian** (tabel 50 baris) yang dapat ditandatangani pembimbing lapangan secara offline. |
| CH-03 | (Opsional) Jika diimplementasikan role **Pembimbing Lapangan** , ia dapat memverifikasi log harian secara online. Pada fase awal, fitur cetak PDF sudah cukup. |

### 2.8 Peta (Leaflet) – Sama dengan SRS awal

| ID | Kebutuhan (ringkasan) |
|----|----------------------|
| PM-01 | Peta Tempat PKL dengan cluster marker. |
| PM-02 | Peta Monitoring (lokasi mahasiswa + instansi + polyline) dengan filter periode, prodi, dosen. |
| PM-03 | Peta picker pada input/edit tempat PKL. |
| PM-04 | Peta check‑in menampilkan geolocation, marker tempat PKL, polyline jarak. |

### 2.9 Laporan & Rekapitulasi

| ID | Kebutuhan |
|----|-----------|
| LR-01 | **Rekap Monitoring**: per mahasiswa (NPM, nama, prodi, tempat PKL, jumlah hari hadir, total durasi, rata‑rata jarak, status laporan, sanksi). Filter periode, prodi, tanggal, hari libur. |
| LR-02 | **Rekap Pelanggaran & Sanksi**: daftar mahasiswa dengan keterlambatan unggah, durasi harian kurang, ketidakhadiran. |
| LR-03 | **Rekap Nilai Akhir**: nilai lapangan, nilai dosen, nilai akhir, huruf mutu. |
| LR-04 | Ekspor ke PDF/Excel untuk semua laporan. |

### 2.10 Autentikasi & Otorisasi (tidak berubah)

| ID | Kebutuhan |
|----|-----------|
| AU-01 | Laravel Breeze + Socialite (Google domain unila.ac.id). |
| AU-02 | Role middleware (`admin`, `dosen`, `mahasiswa`). Dosen dengan penugasan koordinator mendapat akses tambahan. |

---

## 3. Kebutuhan Non-Fungsional (Tambahan)

| ID | Kebutuhan |
|----|-----------|
| NF-01 | Performa peta 500 marker < 3 detik. |
| NF-02 | Import data historis < 5 menit (10.000 check‑in). |
| NF-03 | Respons check‑in < 2 detik. |
| NF-04 | Semua input divalidasi server. |
| NF-05 | Password bcrypt. |
| NF-06 | Foto disimpan dengan nama acak, akses terbatas. |
| NF-07 | API endpoint dilindungi auth & role. |
| NF-08 | Perhitungan durasi dan sanksi menggunakan waktu server Asia/Jakarta. |
| NF-09 | **Notifikasi otomatis** (email) untuk pengingat deadline (H‑3, H‑1, hari H). |
| NF-10 | **Audit log** untuk perubahan konfigurasi, sanksi, dan nilai akhir. |
| NF-11 | Backup database harian. |
| NF-12 | Responsif untuk desktop/tablet. |
| NF-13 | Pesan validasi dalam Bahasa Indonesia. |
| NF-14 | Tile OpenStreetMap dapat diganti melalui konfigurasi tanpa mengubah kode. |

---

## 4. Model Data (Penambahan & Penyesuaian)

### 4.1 Tabel Baru / Kolom Tambahan

```sql
-- Tambahan kolom pada internship_enrollments
ALTER TABLE internship_enrollments ADD COLUMN final_report_path TEXT NULL;
ALTER TABLE internship_enrollments ADD COLUMN total_sanctions_points INTEGER DEFAULT 0;

-- Tabel untuk deadline per periode
CREATE TABLE period_deadlines (
    id SERIAL PRIMARY KEY,
    internship_period_id INTEGER NOT NULL REFERENCES internship_periods(id) ON DELETE CASCADE,
    deadline_type VARCHAR(50) NOT NULL, -- registration_start, registration_end, proposal, bab1, bab2, bab3, bab4, bab5, full_report, seminar, hardcopy
    deadline_date DATE NOT NULL,
    penalty_points INTEGER NOT NULL DEFAULT 5, -- poin sanksi per hari terlambat atau tetap
    is_fixed_penalty BOOLEAN DEFAULT false, -- true=penalty_points dikenakan sekali, false=per hari
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tabel untuk unggahan progres mahasiswa
CREATE TABLE submission_progress (
    id SERIAL PRIMARY KEY,
    internship_enrollment_id INTEGER NOT NULL REFERENCES internship_enrollments(id) ON DELETE CASCADE,
    deadline_type VARCHAR(50) NOT NULL,
    file_path TEXT NOT NULL,
    uploaded_at TIMESTAMP NOT NULL,
    status VARCHAR(20) DEFAULT 'pending', -- pending, approved, revision, rejected
    lecturer_note TEXT,
    reviewed_by INTEGER REFERENCES users(id),
    reviewed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tabel untuk sanksi otomatis
CREATE TABLE sanctions (
    id SERIAL PRIMARY KEY,
    internship_enrollment_id INTEGER NOT NULL REFERENCES internship_enrollments(id) ON DELETE CASCADE,
    sanction_type VARCHAR(50) NOT NULL, -- late_submission, insufficient_daily_duration, absence
    points_deducted INTEGER NOT NULL,
    reason TEXT,
    date DATE NOT NULL,
    created_at TIMESTAMP
);

-- Tabel untuk nilai
CREATE TABLE assessments (
    id SERIAL PRIMARY KEY,
    internship_enrollment_id INTEGER NOT NULL REFERENCES internship_enrollments(id) ON DELETE CASCADE,
    field_supervisor_score DECIMAL(5,2), -- 0-100
    field_supervisor_components JSONB, -- simpan A1,A2,B1,B2,B3,C1,C2,C3
    lecturer_report_score DECIMAL(5,2),
    lecturer_seminar_score DECIMAL(5,2),
    final_score DECIMAL(5,2),
    letter_grade VARCHAR(2),
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tabel untuk catatan harian
CREATE TABLE daily_logs (
    id SERIAL PRIMARY KEY,
    internship_enrollment_id INTEGER NOT NULL REFERENCES internship_enrollments(id) ON DELETE CASCADE,
    log_date DATE NOT NULL,
    activity TEXT NOT NULL,
    field_supervisor_signature BOOLEAN DEFAULT false, -- jika verifikasi online
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tabel permohonan pindah tempat PKL
CREATE TABLE relocation_requests (
    id SERIAL PRIMARY KEY,
    internship_enrollment_id INTEGER NOT NULL REFERENCES internship_enrollments(id) ON DELETE CASCADE,
    new_internship_place_id INTEGER NOT NULL REFERENCES internship_places(id),
    reason TEXT NOT NULL,
    attachment_path TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    reviewed_by INTEGER REFERENCES users(id),
    reviewed_at TIMESTAMP,
    admin_note TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tabel holidays (libur nasional)
CREATE TABLE holidays (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    name VARCHAR(100),
    is_national BOOLEAN DEFAULT true
);

-- Tabel period_settings (konfigurasi per periode) - diperluas
CREATE TABLE internship_period_settings (
    id SERIAL PRIMARY KEY,
    internship_period_id INTEGER NOT NULL REFERENCES internship_periods(id) ON DELETE CASCADE,
    -- jam kerja
    checkin_time_ranges JSONB NOT NULL, -- contoh: [{"start":"07:00","end":"07:59","status":"Masuk"}, ...]
    min_daily_duration_minutes INTEGER DEFAULT 360, -- 6 jam
    max_distance_meters INTEGER DEFAULT 5000, -- opsional
    earth_radius_km DECIMAL(10,2) DEFAULT 6371,
    -- peta
    map_center_lat DECIMAL(10,8) DEFAULT -5.429167,
    map_center_lng DECIMAL(11,8) DEFAULT 105.261111,
    map_zoom INTEGER DEFAULT 12,
    tile_url VARCHAR(255) DEFAULT 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    -- upload
    max_photo_size_kb INTEGER DEFAULT 2048,
    max_photo_width INTEGER DEFAULT 2048,
    max_photo_height INTEGER DEFAULT 2048,
    -- sanksi
    late_submission_penalty_per_day INTEGER DEFAULT 5,
    late_full_report_penalty_fixed INTEGER DEFAULT 50,
    -- aturan laporan
    exclude_saturday BOOLEAN DEFAULT true,
    exclude_sunday BOOLEAN DEFAULT true,
    -- bobot nilai
    field_supervisor_weight DECIMAL(3,2) DEFAULT 0.50,
    lecturer_weight DECIMAL(3,2) DEFAULT 0.50,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 4.2 Perubahan pada tabel `internship_enrollments`

- Tambahkan `final_report_path TEXT`
- Tambahkan `total_sanctions_points INTEGER DEFAULT 0`

### 4.3 Perubahan pada tabel `check_ins`

- Tambahkan `pair_id INTEGER NULL` (referensi ke check‑in pasangan, atau gunakan logika query grouping per tanggal)

---

## 5. Identifikasi Data yang Dapat Dikonfigurasi

Semua nilai yang bersifat **aturan operasional** dan dapat berbeda antar periode dimasukkan ke dalam tabel `internship_period_settings` atau `period_deadlines`. Berikut ringkasan **parameter konfigurasi**:

| Kelompok | Parameter | Lokasi | Default |
|----------|-----------|--------|---------|
| **Jam kerja** | Rentang waktu untuk Masuk, Datang Terlambat, Pulang Cepat, Pulang | `period_settings.checkin_time_ranges` | 07:00-07:59, 08:00-11:59, 12:00-15:59, 16:00-18:59 |
| **Durasi** | Minimal durasi harian (menit) | `period_settings.min_daily_duration_minutes` | 360 |
| **Jarak** | Maksimum jarak valid (meter) | `period_settings.max_distance_meters` | 5000 |
| **Peta** | Center, zoom, tile URL | `period_settings.map_*` | Unila center, zoom 12, OSM |
| **Upload foto** | Ukuran maks (KB), dimensi | `period_settings.max_photo_*` | 2048 KB, 2048x2048 |
| **Sanksi** | Poin per hari terlambat (bab biasa) | `period_settings.late_submission_penalty_per_day` | 5 |
| **Sanksi** | Poin tetap keterlambatan laporan lengkap/seminar | `period_settings.late_full_report_penalty_fixed` | 50 |
| **Bobot nilai** | Bobot nilai lapangan dan dosen | `period_settings.field_supervisor_weight`, `lecturer_weight` | 0.5, 0.5 |
| **Hari libur** | Daftar tanggal libur | Tabel `holidays` | Dikelola admin |
| **Kuota tempat PKL** | Minimal & maksimal mahasiswa per tempat | Global config (atau per periode) | min=2, max=3 |
| **Deadline** | Tanggal dan poin sanksi per jenis | Tabel `period_deadlines` | Ditentukan admin per periode |

> **Cara akses konfigurasi**: Semua parameter dapat diubah melalui halaman admin **Konfigurasi Sistem** (per periode). Nilai global default disimpan di `config/monpkl.php` dan diwariskan saat periode baru dibuat.

---

## 7. Lampiran – Contoh Konfigurasi Periode (JSON)

Contoh isi kolom `checkin_time_ranges`:
```json
[
  {"start":"07:00","end":"07:59","status":"Masuk"},
  {"start":"08:00","end":"11:59","status":"Datang Terlambat"},
  {"start":"12:00","end":"15:59","status":"Pulang Cepat"},
  {"start":"16:00","end":"18:59","status":"Pulang"}
]
```

Contoh `period_deadlines` untuk Periode II 2025:
| deadline_type | deadline_date | penalty_points | is_fixed_penalty |
|---------------|---------------|----------------|------------------|
| registration_start | 2025-04-28 | 0 | false |
| registration_end | 2025-05-28 | 0 | false |
| proposal | 2025-06-23 | 5 | false |
| bab1 | 2025-06-30 | 5 | false |
| bab2 | 2025-07-07 | 5 | false |
| bab3 | 2025-07-14 | 5 | false |
| full_report | 2025-09-26 | 50 | true |
| seminar | 2025-10-03 | 50 | true |

---

## 8. Identifikasi Fitur yang Sudah Diimplementasikan

Bagian ini mencatat kebutuhan SRS yang sudah tersedia pada implementasi backend Laravel saat ini. Status ini bersifat implementasi awal dan dapat diperluas pada iterasi berikutnya.

### 8.1 Backend, Autentikasi, dan Infrastruktur

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| AU-01 | Sudah diimplementasikan sebagian | Laravel Breeze tersedia untuk email/password. Google Socialite sudah dikonfigurasi untuk domain Unila. |
| AU-02 | Sudah diimplementasikan sebagian | Role dasar `admin`, `dosen`, `mahasiswa` sudah ada. Akses tambahan `koordinator` dihitung dari penugasan aktif dosen pada periode/prodi. |
| NF-04 | Sudah diimplementasikan | Form utama menggunakan validasi server Laravel. |
| NF-05 | Sudah diimplementasikan | Password memakai hashing Laravel. |
| NF-07 | Sudah diimplementasikan sebagian | Route utama dilindungi auth dan role middleware. |
| NF-14 | Sudah diimplementasikan | Tile URL, center, dan zoom peta dapat berasal dari konfigurasi periode. |

### 8.2 Master Data dan Manajemen

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| MF-01 | Sudah diimplementasikan | CRUD Prodi tersedia di menu Manajemen. |
| MF-02 | Sudah diimplementasikan sebagian | CRUD Mahasiswa tersedia; profil mahasiswa juga dapat dilengkapi oleh mahasiswa sendiri. |
| MF-03 | Sudah diimplementasikan | Tabel dan CRUD Dosen tersedia, termasuk NIP, NIDN, prodi, status, dan relasi user. |
| MF-04 | Sudah diimplementasikan | CRUD Periode PKL tersedia. |
| MF-05 | Sudah diimplementasikan sebagian | Master Tempat PKL tersedia dengan input/edit lokasi Leaflet. Field `is_active` khusus belum dipisahkan. |
| MF-06 | Sudah diimplementasikan | Bulk hapus dan merge Master Tempat PKL tersedia. |
| MF-07 | Sudah diimplementasikan | CRUD User tersedia untuk role `admin`, `dosen`, `mahasiswa`. |
| MF-08 | Sudah diimplementasikan | Penugasan Koordinator PKL per periode/prodi tersedia dan dibatasi satu koordinator per periode-prodi. |

### 8.3 Konfigurasi Periode

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| KS-01 | Sudah diimplementasikan sebagian | Konfigurasi per periode tersedia melalui `internship_period_settings`, mencakup jam check-in, peta, upload foto, hari libur laporan, dan aturan laporan dasar. |
| KS-03 | Sudah diimplementasikan sebagian | Konfigurasi hanya dapat diakses admin. Perlakuan periode terkunci masih perlu diperketat. |

### 8.4 Workflow Mahasiswa

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| WM-01 | Sudah diimplementasikan | Mahasiswa dapat melengkapi profil: NPM, nama, email student, no HP, prodi. |
| WM-02 | Sudah diimplementasikan sebagian | Mahasiswa dapat mendaftar PKL. Validasi akademik seperti KRS, SKS, semester, dan IPK belum tersedia. |
| WM-03 | Sudah diimplementasikan | Mahasiswa dapat mengajukan tempat PKL baru melalui `internship_place_proposals`. |
| WM-04 | Sudah diimplementasikan sebagian | Admin dapat mengelola status enrollment melalui Peserta Periode dan memvalidasi usulan tempat. Halaman validasi pendaftaran khusus belum dibuat. |
| WM-05 | Sudah diimplementasikan sebagian | Status enrollment sudah mendukung beberapa status workflow. Catatan revisi khusus belum tersedia. |
| WM-07 | Sudah diimplementasikan | Check-in hanya memakai enrollment `active`. |
| WM-08 | Sudah diimplementasikan sebagian | Mahasiswa memiliki halaman Laporan Saya berisi presensi dan data pembimbing. Progres bimbingan, sanksi, dan nilai belum ada. |
| WM-09 | Sudah diimplementasikan sebagian | Cetak laporan mahasiswa memvalidasi dosen pembimbing dan pembimbing lapangan. Output PDF resmi belum dibuat. |

### 8.5 Check-In, Peta, dan Laporan

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| CI-02 | Sudah diimplementasikan sebagian | Server menentukan status berdasarkan jam konfigurasi dan menyimpan lokasi, catatan, foto opsional. |
| CI-05 | Sudah diimplementasikan | Device info, jarak Haversine, lokasi kantor, lokasi mahasiswa, dan foto disimpan. |
| PM-01 | Sudah diimplementasikan | Peta Tempat PKL memakai Leaflet dan marker cluster. |
| PM-02 | Sudah diimplementasikan sebagian | Peta Monitoring tersedia dengan filter periode/prodi dan scope role. |
| PM-03 | Sudah diimplementasikan | Peta picker tersedia pada input/edit tempat PKL. |
| PM-04 | Sudah diimplementasikan | Peta check-in menampilkan geolocation, marker instansi, dan polyline. |
| LR-01 | Sudah diimplementasikan sebagian | Rekap Monitoring tersedia dengan filter tanggal, periode, prodi, hari libur, Sabtu, dan Minggu. Status laporan dan sanksi belum tersedia. |

### 8.6 Migrasi Data Historis

| Area | Status Implementasi | Catatan |
|------|---------------------|---------|
| Import Firebase JSON | Sudah diimplementasikan | Command `php artisan import:firebase-json` tersedia. |
| Data historis utama | Sudah diimport | Data `cities`, `internship_places`, `users`, `students`, `internship_enrollments`, dan `check_ins` sudah masuk database. |
| Laporan import | Sudah diimplementasikan | Report import tersedia di storage aplikasi. |

---

## 9. Identifikasi Fitur yang Akan Diimplementasikan

Bagian ini mencatat kebutuhan SRS yang belum tersedia atau masih perlu disempurnakan pada iterasi berikutnya.

### 9.1 Penyempurnaan Manajemen dan Workflow

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| MF-05 | Tambahkan `is_active` eksplisit pada Master Tempat PKL dan filter hanya tempat aktif pada pendaftaran mahasiswa. | Menengah |
| MF-09 | Implementasi kuota minimal dan maksimal mahasiswa per tempat PKL, idealnya per periode/prodi. | Tinggi |
| KS-02 | Buat tabel dan UI `period_deadlines` untuk deadline pendaftaran, proposal, Bab I-V, laporan lengkap, seminar, dan hardcopy. | Tinggi |
| KS-03 | Kunci perubahan konfigurasi jika periode `is_locked=true`, kecuali admin khusus. | Menengah |
| WM-02 | Tambahkan data kelayakan akademik: KRS KP/PKL, total SKS, semester, dan IPK. | Tinggi |
| WM-04 | Buat halaman validasi pendaftaran khusus untuk admin/koordinator, termasuk catatan revisi dan penolakan. | Tinggi |
| WM-06 | Buat modul permohonan pindah tempat PKL. | Menengah |
| WM-08 | Lengkapi Laporan Saya dengan progres bimbingan, sanksi, dan nilai. | Tinggi |

### 9.2 Check-In Ganda, Durasi, dan Anti-Spoofing

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| CI-01 | Ubah model check-in menjadi aksi eksplisit `check_in` dan `check_out`, lalu pasangkan per tanggal dan enrollment. | Tinggi |
| CI-03 | Hitung durasi harian setelah check-out dan catat sanksi jika kurang dari durasi minimal. | Tinggi |
| CI-04 | Hari dengan satu check-in saja tidak dihitung sebagai hari hadir. | Tinggi |
| CI-06 | Terapkan radius maksimum, rate limit, dan validasi anti-spoofing tambahan. | Menengah |
| CI-07 | Batasi satu pasang check-in/check-out resmi per hari. | Menengah |

### 9.3 Progres Laporan, Catatan Harian, dan Sanksi

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| PG-01 | Buat modul unggah dokumen progres laporan. | Tinggi |
| PG-02 | Hubungkan unggahan dengan deadline periode. | Tinggi |
| PG-03 | Hitung sanksi otomatis untuk keterlambatan unggahan. | Tinggi |
| PG-04 | Buat review progres laporan oleh dosen pembimbing. | Tinggi |
| PG-05 | Buat rekap progres untuk koordinator PKL. | Menengah |
| PG-06 | Buat unggah laporan final dan approval dosen pembimbing. | Tinggi |
| CH-01 | Buat catatan/log harian mahasiswa. | Menengah |
| CH-02 | Buat cetak form bimbingan harian 50 baris. | Menengah |
| CH-03 | Evaluasi kebutuhan role pembimbing lapangan online pada fase lanjut. | Rendah |

### 9.4 Penilaian dan Nilai Akhir

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| PN-01 | Buat form nilai pembimbing lapangan dan perhitungan komponen A, B, C. | Tinggi |
| PN-02 | Buat form nilai laporan dan seminar oleh dosen pembimbing. | Tinggi |
| PN-03 | Hitung nilai akhir dan konversi huruf mutu berdasarkan aturan Unila. | Tinggi |
| PN-04 | Buat proses pengesahan nilai akhir oleh admin. | Tinggi |

### 9.5 Laporan, Export, dan Operasional Produksi

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| LR-01 | Lengkapi Rekap Monitoring dengan status laporan dan sanksi. | Menengah |
| LR-02 | Buat Rekap Pelanggaran dan Sanksi. | Tinggi |
| LR-03 | Buat Rekap Nilai Akhir. | Tinggi |
| LR-04 | Tambahkan export PDF/Excel untuk laporan utama. | Tinggi |
| NF-06 | Perketat akses file foto agar tidak terbuka publik tanpa otorisasi jika produksi membutuhkan. | Menengah |
| NF-09 | Buat notifikasi email otomatis untuk deadline. | Menengah |
| NF-10 | Buat audit log perubahan konfigurasi, sanksi, nilai, master data, dan enrollment. | Menengah |
| NF-11 | Siapkan strategi backup database harian. | Tinggi |
| NF-12 | Uji dan rapikan responsif untuk tablet/desktop pada semua halaman baru. | Menengah |
| NF-13 | Standarkan semua pesan validasi dalam Bahasa Indonesia. | Menengah |

### 9.6 Cutover dan Penghapusan Ketergantungan Lama

| Area | Rencana Implementasi | Prioritas |
|------|----------------------|-----------|
| Migrasi foto lama | Download/migrasikan foto lama dari Firebase Storage atau simpan URL lama secara terkendali. | Menengah |
| Uji paralel | Bandingkan output laporan lama dan baru dengan data historis. | Tinggi |
| Delta import | Buat prosedur import delta dari Firebase sebelum cutover final. | Tinggi |
| Decommission Firebase | Nonaktifkan write Firebase, arsipkan rules/config, dan lepas ketergantungan script lama. | Tinggi |
| Decommission Google Maps | Pastikan tidak ada script/API `google.maps.*` pada kode produksi baru. | Tinggi |
