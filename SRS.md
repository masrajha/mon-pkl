# **Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SRS) - Revisi 2.0**

> Nama sistem hasil rebranding: **SiLAT (Sistem Laporan Aktivitas Terpadu MBKM & Kerja Praktik)**.

## **SiLAT - Universitas Lampung**

> Berdasarkan dokumen:
> - `MIGRASI_FIREBASE_GOOGLEMAP.md` (arsitektur target)
> - `Buku Panduan Kerja Praktik Periode II 2025` (peraturan operasional)

---

## 1. Pendahuluan

### 1.1 Tujuan
Catatan rebranding: nama aplikasi target adalah **SiLAT (Sistem Laporan Aktivitas Terpadu MBKM & Kerja Praktik)**. Istilah PKL/Kerja Praktik tetap muncul pada bagian aturan karena rule operasional saat ini masih mengikuti Kerja Praktik.

Dokumen ini mendefinisikan kebutuhan fungsional dan non‑fungsional untuk pengembangan **SiLAT**, sistem informasi manajemen aktivitas MBKM dan Kerja Praktik Fakultas MIPA Universitas Lampung. Sistem menggantikan arsitektur Firebase/Google Maps dengan backend Laravel, database PostgreSQL/PostGIS, dan peta Leaflet.

### 1.2 Ruang Lingkup
- Autentikasi (Google SSO domain unila.ac.id, email/password)
- Manajemen master data: program kegiatan, prodi, mahasiswa, dosen, tempat PKL/mitra, periode
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
| Program Kegiatan | Master nama program pelaksanaan seperti Kerja Praktik, Magang, Riset, Studi Independen, atau program MBKM lain. |
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
| MF-03A | CRUD Program Kegiatan (kode, nama, deskripsi, rule_key, is_active). Contoh program: Kerja Praktik, Magang, Riset, Studi Independen. |
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
| KS-04 | Setiap program kegiatan memiliki `rule_key`. Pada implementasi awal, semua program selain Kerja Praktik boleh memakai `rule_key='kerja_praktik'` agar workflow saat ini tetap berjalan. Desain konfigurasi harus memungkinkan rule per program didefinisikan ulang di masa datang tanpa mengubah data historis. |

### 2.3 Workflow Mahasiswa (Pendaftaran & Pelaksanaan)

| ID | Kebutuhan |
|----|-----------|
| WM-01 | Mahasiswa melengkapi **Profil Saya** (NPM, nama, email, no HP, prodi). Wajib diisi sebelum pendaftaran PKL. |
| WM-02 | **Pendaftaran PKL/Program**: pilih program kegiatan, periode aktif, tempat PKL/mitra (atau usulkan baru), isi kontak mahasiswa, isi pembimbing lapangan (opsional). Sistem memeriksa kelayakan berdasarkan rule program. Pada implementasi awal, rule yang dipakai adalah rule Kerja Praktik: <br> - Telah mengambil mata kuliah KP/PKL di KRS semester ini (data input admin) <br> - Total SKS ≥ 100 (tidak termasuk KP) <br> - Semester ≥ 6 (S1) atau ≥ 4 (D3) <br> - IPK ≥ 2,00 <br> Jika tidak memenuhi, pendaftaran ditolak dengan pesan. |
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
| CH-01 | Catatan harian disusun dari pasangan presensi harian: catatan presensi masuk menjadi **Rencana Aktivitas**, sedangkan catatan presensi pulang menjadi **Realisasi**. |
| CH-02 | Sistem menyediakan **cetak form bimbingan harian** berisi tanggal, jam masuk/pulang, durasi, jarak, rencana, realisasi, dan kolom paraf pembimbing lapangan. |
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
-- Tabel master program kegiatan
CREATE TABLE programs (
    id SERIAL PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    rule_key VARCHAR(50) NOT NULL DEFAULT 'kerja_praktik',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tambahan kolom pada internship_periods
ALTER TABLE internship_periods ADD COLUMN program_id INTEGER NULL REFERENCES programs(id);

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

### 4.2 Tabel `programs`

Tabel `programs` menjadi master nama program kegiatan. Data awal minimal:

| Kode | Nama Program | Rule Default |
|------|--------------|--------------|
| KP | Kerja Praktik | `kerja_praktik` |
| MAGANG | Magang | `kerja_praktik` |
| RISET | Riset | `kerja_praktik` |

Kolom `rule_key` disiapkan agar setiap program dapat memakai rule berbeda di masa datang. Untuk fase implementasi saat ini, program selain Kerja Praktik dapat memakai rule Kerja Praktik sebagai fallback sementara.

### 4.3 Perubahan pada tabel `internship_periods`

- Tambahkan `program_id INTEGER REFERENCES programs(id)`
- Setiap periode wajib terhubung ke satu program kegiatan setelah migrasi data selesai.
- Periode lama dapat dimigrasikan ke program default `Kerja Praktik`.

### 4.4 Perubahan pada tabel `internship_enrollments`

- Tambahkan `final_report_path TEXT`
- Tambahkan `total_sanctions_points INTEGER DEFAULT 0`

### 4.5 Perubahan pada tabel `check_ins`

- Tambahkan `pair_id INTEGER NULL` (referensi ke check‑in pasangan, atau gunakan logika query grouping per tanggal)

---

## 5. Identifikasi Data yang Dapat Dikonfigurasi

Semua nilai yang bersifat **aturan operasional** dan dapat berbeda antar periode dimasukkan ke dalam tabel `internship_period_settings` atau `period_deadlines`. Berikut ringkasan **parameter konfigurasi**:

| Kelompok | Parameter | Lokasi | Default |
|----------|-----------|--------|---------|
| **Program kegiatan** | Nama program, kode, status aktif, rule aktif | Tabel `programs` | Kerja Praktik dengan `rule_key=kerja_praktik` |
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

> **Cara akses konfigurasi**: Semua parameter dapat diubah melalui halaman admin **Konfigurasi Sistem** (per periode). Master program kegiatan dikelola melalui menu admin tersendiri. Nilai global default disimpan di `config/monpkl.php` dan diwariskan saat periode baru dibuat.

> **Catatan rule program**: Rule operasional saat ini mengikuti Kerja Praktik. Program seperti Magang, Riset, atau program MBKM lain dapat memakai rule tersebut sementara, tetapi struktur `programs.rule_key` harus dipertahankan agar rule berbeda dapat didefinisikan ulang tanpa migrasi besar di masa datang.

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

Bagian ini mencatat kebutuhan SRS yang sudah tersedia pada implementasi backend Laravel saat ini. Status ini bersifat implementasi awal dan dapat diperluas pada iterasi berikutnya. Identifikasi state terakhir dilakukan berdasarkan kode aplikasi saat ini di workspace.

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
| MF-01 | Sudah diimplementasikan | CRUD Prodi tersedia di menu Manajemen. Prodi memiliki `degree_level` seperti D3, S1, S2, dan dipakai untuk membedakan aturan akademik. |
| MF-02 | Sudah diimplementasikan sebagian | CRUD Mahasiswa tersedia; profil mahasiswa juga dapat dilengkapi oleh mahasiswa sendiri. |
| MF-03 | Sudah diimplementasikan | Tabel dan CRUD Dosen tersedia, termasuk NIP, NIDN, prodi, status, dan relasi user. |
| MF-03A | Sudah diimplementasikan | Master Program Kegiatan tersedia dengan `code`, `name`, `description`, `rule_key`, dan `is_active`. Seed awal mencakup Kerja Praktik, Magang, dan Riset dengan fallback rule `kerja_praktik`. |
| MF-04 | Sudah diimplementasikan | CRUD Periode PKL tersedia dan periode terhubung ke Program Kegiatan. |
| MF-05 | Sudah diimplementasikan | Master Tempat PKL tersedia dengan input/edit lokasi Leaflet. Field `is_active` sudah tersedia dan tempat aktif dipakai pada pendaftaran serta permohonan pindah tempat. |
| MF-06 | Sudah diimplementasikan | Bulk hapus dan merge Master Tempat PKL tersedia. |
| MF-07 | Sudah diimplementasikan | CRUD User tersedia untuk role `admin`, `dosen`, `mahasiswa`. |
| MF-08 | Sudah diimplementasikan | Penugasan Koordinator PKL per periode/prodi tersedia dan dibatasi satu koordinator per periode-prodi. Form tambah koordinator mendukung multi-select prodi untuk membuat beberapa penugasan sekaligus pada periode yang sama. |
| MF-09 | Sudah diimplementasikan sebagian | Kuota minimal dan maksimal tersedia di konfigurasi periode. Kuota maksimal sudah divalidasi pada pendaftaran mahasiswa dan input peserta admin; kuota minimal ditampilkan sebagai indikator peringatan pada validasi pendaftaran. |

### 8.3 Konfigurasi Periode

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| KS-01 | Sudah diimplementasikan sebagian | Konfigurasi per periode tersedia melalui `internship_period_settings`, mencakup jam check-in, peta, upload foto, hari libur laporan, aturan laporan dasar, kuota, dan syarat akademik. Syarat minimal SKS sudah dibedakan per jenjang, misalnya D3=80 dan S1=100. |
| KS-02 | Sudah diimplementasikan | Tabel/model `period_deadlines` dan UI konfigurasi deadline per periode sudah tersedia, termasuk tanggal, poin penalti, dan flag penalti tetap. Deadline dipakai untuk menghitung sanksi unggahan progres laporan. |
| KS-03 | Sudah diimplementasikan | Konfigurasi hanya dapat diakses admin. Periode terkunci tidak dapat diubah melalui konfigurasi kecuali oleh super admin yang tercantum pada konfigurasi. |
| KS-04 | Sudah diimplementasikan | Program terhubung ke periode dan memiliki `rule_key`. Implementasi saat ini memakai rule `kerja_praktik` sebagai fallback terstruktur, sehingga rule program lain dapat ditambahkan tanpa mengubah data historis. |

### 8.4 Workflow Mahasiswa

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| WM-01 | Sudah diimplementasikan | Mahasiswa dapat melengkapi profil: NPM, nama, email student, no HP, prodi. |
| WM-02 | Sudah diimplementasikan | Mahasiswa dapat mendaftar program melalui workflow 3 halaman: Program dan Periode, Mitra dan Pembimbing Lapangan, lalu Rule Program/Kelayakan Akademik/Dokumen. Mahasiswa memilih mitra aktif atau mengajukan mitra baru, mengisi kontak dan pembimbing lapangan, serta mengunggah satu PDF bukti akademik berisi Transkrip Sementara + KRS semester saat ini. Validasi kelayakan membaca `programs.rule_key`, `degree_level` prodi, dan konfigurasi akademik jenjang. |
| WM-03 | Sudah diimplementasikan | Mahasiswa dapat mengajukan tempat PKL baru melalui `internship_place_proposals`. |
| WM-04 | Sudah diimplementasikan | Admin/koordinator memiliki halaman Validasi Pendaftaran khusus untuk menyetujui, meminta revisi, atau menolak pendaftaran, termasuk menetapkan dosen pembimbing, pembimbing lapangan, email pembimbing lapangan, catatan verifikasi, dan review dokumen bukti akademik. Mahasiswa dapat memperbaiki pendaftaran saat status `revision_required`. |
| WM-05 | Sudah diimplementasikan | Status enrollment sudah mendukung `draft`, `pending_verification`, `revision_required`, `active`, `inactive`, `completed`, `cancelled`, dan `rejected`, termasuk catatan admin. |
| WM-06 | Sudah diimplementasikan | Mahasiswa dapat mengajukan pindah tempat PKL, admin dapat menyetujui/menolak, dan tempat enrollment diperbarui saat disetujui. |
| WM-07 | Sudah diimplementasikan | Check-in hanya memakai enrollment `active`. |
| WM-08 | Sudah diimplementasikan sebagian | Mahasiswa memiliki halaman Laporan Saya berisi presensi, data pembimbing, unggahan progres laporan, catatan harian, ringkasan progres bimbingan, total sanksi, dan status nilai. Modul penilaian numerik penuh masih berada pada backlog PN-01 s.d. PN-04. |
| WM-09 | Sudah diimplementasikan sebagian | Cetak laporan mahasiswa memvalidasi dosen pembimbing dan pembimbing lapangan. Output PDF resmi belum dibuat. |

### 8.5 Check-In, Peta, dan Laporan

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| CI-01 | Sudah diimplementasikan | Check-in memakai aksi eksplisit `check_in` dan `check_out`, lalu dipasangkan melalui `pair_id` pada hari/enrollment yang sama. |
| CI-02 | Sudah diimplementasikan sebagian | Server menentukan status berdasarkan jam konfigurasi dan menyimpan lokasi, catatan, foto opsional. |
| CI-03 | Sudah diimplementasikan | Saat check-out, sistem menghitung durasi harian, menyimpan `duration_minutes`, menghitung poin sanksi jika durasi kurang, dan menambahkan poin ke total sanksi enrollment. |
| CI-04 | Sudah diimplementasikan | Laporan monitoring dan ringkasan dashboard hanya menghitung hari hadir jika terdapat pasangan check-in masuk dan pulang. |
| CI-05 | Sudah diimplementasikan | Device info, jarak Haversine, lokasi kantor, lokasi mahasiswa, dan foto disimpan. |
| CI-06 | Sudah diimplementasikan sebagian | Radius maksimum check-in dapat dikonfigurasi dan route submit check-in dibatasi rate limit 10 request/menit. Validasi anti-spoofing lanjutan masih dapat diperkuat pada fase produksi. |
| CI-07 | Sudah diimplementasikan | Sistem membatasi satu `check_in` dan satu `check_out` resmi per enrollment per hari. |
| PM-01 | Sudah diimplementasikan | Peta Tempat PKL memakai Leaflet dan marker cluster. |
| PM-02 | Sudah diimplementasikan sebagian | Peta Monitoring tersedia dengan filter periode/prodi dan scope role. |
| PM-03 | Sudah diimplementasikan | Peta picker tersedia pada input/edit tempat PKL. |
| PM-04 | Sudah diimplementasikan | Peta check-in menampilkan geolocation, marker instansi, dan polyline. |
| PM-05 | Sudah diimplementasikan | Input lokasi pada event pembekalan, master mitra, dan pengajuan mitra mahasiswa memiliki sugest lokasi dari riwayat internal, lalu fallback eksternal jika tidak ditemukan. |
| LR-01 | Sudah diimplementasikan sebagian | Rekap Monitoring tersedia dengan filter tanggal, periode, prodi, hari libur, Sabtu, dan Minggu. Status laporan dan sanksi belum tersedia. |

### 8.6 Progres Laporan, Catatan Harian, dan Sanksi

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| PG-01 | Sudah diimplementasikan | Mahasiswa dapat mengunggah dokumen progres laporan pada halaman Laporan Saya untuk jenis Proposal, Bab I-V, Laporan Lengkap, Seminar, dan Hardcopy. |
| PG-02 | Sudah diimplementasikan | Unggahan terhubung ke `period_deadlines` berdasarkan periode enrollment dan mencatat waktu unggah. |
| PG-03 | Sudah diimplementasikan | Sistem menghitung sanksi keterlambatan unggahan berdasarkan deadline, poin penalti, dan mode penalti tetap/per hari, lalu menambahkan poin ke total sanksi enrollment. |
| PG-04 | Sudah diimplementasikan | Dosen pembimbing, koordinator sesuai scope, dan admin dapat memberi status `approved`, `revision`, atau `rejected` beserta catatan review. |
| PG-05 | Sudah diimplementasikan sebagian | Halaman Review Laporan menampilkan rekap unggahan progres untuk admin, dosen pembimbing, dan koordinator sesuai scope periode/prodi. |
| PG-06 | Sudah diimplementasikan sebagian | Unggahan `full_report` yang disetujui disimpan sebagai `final_report_path`. Perubahan otomatis status enrollment menjadi `completed` belum diaktifkan agar tetap menunggu modul penilaian akhir. |
| CH-01 | Sudah diimplementasikan | Catatan harian diambil dari pasangan presensi: catatan masuk sebagai rencana aktivitas dan catatan pulang sebagai realisasi. Tidak ada input/tabel log harian terpisah. |
| CH-02 | Sudah diimplementasikan | Sistem menyediakan cetak form catatan harian dari pasangan presensi dengan kolom tanggal, jam, jarak, rencana, realisasi, dan paraf pembimbing lapangan. |

### 8.7 Pembekalan Program

| Area | Status Implementasi | Catatan |
|------|---------------------|---------|
| Event Pembekalan | Sudah diimplementasikan | Admin/koordinator dapat membuat event pembekalan per periode program dan prodi. Nama kegiatan otomatis mengikuti program dan periode, lokasi dapat dipilih dari peta, dan radius presensi dapat memakai konfigurasi periode. |
| Presensi Pembekalan Mahasiswa | Sudah diimplementasikan | Mahasiswa melihat link presensi pembekalan pada dashboard dan melakukan presensi satu kali dengan komponen presensi yang sama, termasuk kamera, geolocation, peta, jarak Haversine, dan validasi radius lokasi pembekalan. Pada role mahasiswa, marker dan field koordinat lokasi pembekalan bersifat readonly. |
| Rekap Pembekalan | Sudah diimplementasikan | Admin/koordinator dapat melihat rekap peserta yang hadir dan belum hadir, termasuk jarak mahasiswa dari lokasi pembekalan. |

### 8.8 Migrasi Data Historis

| Area | Status Implementasi | Catatan |
|------|---------------------|---------|
| Import Firebase JSON | Sudah diimplementasikan | Command `php artisan import:firebase-json` tersedia. |
| Data historis utama | Sudah diimport | Data `cities`, `internship_places`, `users`, `students`, `internship_enrollments`, dan `check_ins` sudah masuk database. |
| Laporan import | Sudah diimplementasikan | Report import tersedia di storage aplikasi. |

### 8.9 Email Notifikasi

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| EN-07 | Sudah diimplementasikan | Email perubahan pembimbing dikirim kepada mahasiswa saat permohonan dikirim, disetujui, atau ditolak. Admin dan koordinator sesuai scope periode/prodi menerima email saat ada permohonan baru serta reminder untuk permohonan pending minimal 48 jam. Saat permohonan disetujui dan dosen berubah, dosen pembimbing baru menerima notifikasi penugasan, sedangkan dosen pembimbing lama menerima notifikasi bahwa mahasiswa tidak lagi menjadi bimbingannya. |
| EN-08 | Sudah diimplementasikan | Email pindah tempat dikirim kepada mahasiswa saat permohonan dikirim, disetujui, atau ditolak. Admin dan koordinator sesuai scope periode/prodi menerima email saat ada permohonan baru serta reminder untuk permohonan pending minimal 48 jam. Saat permohonan disetujui, dosen pembimbing mahasiswa menerima notifikasi bahwa mahasiswa bimbingannya pindah mitra/tempat kegiatan. Modul pindah tempat juga dapat diakses koordinator dengan pembatasan data sesuai penugasan aktif. |

---

## 9. Identifikasi Fitur yang Akan Diimplementasikan

Bagian ini mencatat kebutuhan SRS yang belum tersedia atau masih perlu disempurnakan pada iterasi berikutnya.

### 9.1 Penyempurnaan Manajemen dan Workflow

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| - | Seluruh item prioritas pada 9.1 sudah dipindahkan ke Bagian 8 sebagai fitur yang sudah/sudah sebagian diimplementasikan. Penyempurnaan lanjutan terkait penilaian numerik penuh tetap berada pada backlog 9.4. | - |

### 9.2 Check-In Ganda, Durasi, dan Anti-Spoofing

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| - | Item utama CI-01, CI-03, CI-04, CI-06, dan CI-07 sudah dipindahkan ke Bagian 8 sebagai fitur yang sudah/sudah sebagian diimplementasikan. Penguatan anti-spoofing lanjutan tetap dapat ditambahkan pada fase produksi. | - |

### 9.3 Progres Laporan, Catatan Harian, dan Sanksi

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| - | Item utama PG-01 s.d. PG-06 dan CH-01 s.d. CH-02 sudah dipindahkan ke Bagian 8 sebagai fitur yang sudah/sudah sebagian diimplementasikan. Pengembangan CH-03 dipindahkan ke backlog role Pembimbing Lapangan pada 9.6. | - |

### 9.4 Penilaian dan Nilai Akhir

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| PN-01 | Buat form nilai pembimbing lapangan dan perhitungan komponen A, B, C. | Tinggi |
| PN-02 | Buat form nilai laporan dan seminar oleh dosen pembimbing. | Tinggi |
| PN-03 | Hitung nilai akhir dan konversi huruf mutu berdasarkan aturan Unila. | Tinggi |
| PN-04 | Buat proses pengesahan nilai akhir oleh admin. | Tinggi |

### 9.5 Email Notifikasi

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| EN-01 | Buat sistem notifikasi email berbasis event untuk proses yang membutuhkan aksi, keputusan akademik, reminder deadline, atau akses pihak luar. Setiap email minimal memuat subjek jelas, nama mahasiswa/NPM bila relevan, program, periode, prodi, status terbaru, catatan reviewer/admin jika ada, tenggat waktu jika ada, serta tombol/link aksi. | Tinggi |
| EN-02 | Kirim email pendaftaran program kepada mahasiswa saat pendaftaran dikirim, disetujui, diminta revisi, ditolak, atau enrollment diubah admin. Kirim email kepada admin/koordinator saat ada pendaftaran baru, revisi pendaftaran dikirim ulang, atau pendaftaran belum diproses mendekati batas pendaftaran. | Tinggi |
| EN-03 | Kirim email usulan mitra kepada mahasiswa saat usulan dikirim, disetujui sebagai master baru, digabung ke master mitra, atau ditolak. Kirim email kepada admin saat ada usulan mitra baru atau usulan pending belum diproses dalam batas waktu tertentu. | Menengah |
| EN-04 | Kirim email pembekalan kepada mahasiswa saat event dibuka, reminder H-1 atau beberapa jam sebelum kegiatan, presensi berhasil dicatat, dan belum presensi mendekati waktu tutup. Kirim email kepada admin/koordinator berisi rekap setelah event ditutup, termasuk jumlah hadir dan tidak hadir. | Tinggi |
| EN-05 | Kirim digest presensi, bukan email untuk setiap check-in/check-out. Digest dikirim mingguan kepada mahasiswa berisi ringkasan presensi, durasi, jarak, dan sanksi. Digest kepada dosen pembimbing/koordinator berisi mahasiswa dengan pola bermasalah seperti durasi kurang, sering terlambat, atau jarak presensi tidak wajar. | Menengah |
| EN-06 | Kirim email laporan dan deadline kepada mahasiswa untuk reminder H-7, H-3, H-1, dan hari H; upload berhasil; laporan disetujui; laporan diminta revisi; laporan ditolak; dan sanksi keterlambatan. Kirim email kepada dosen pembimbing saat ada laporan baru menunggu review atau laporan pending review melewati batas waktu. Kirim email rekap kepada admin/koordinator untuk laporan belum diunggah, pending review, dan sanksi tertinggi. | Tinggi |
| EN-09 | Kirim email kepada pembimbing lapangan untuk akses URL + token, token baru jika token lama kedaluwarsa, reminder validasi catatan harian, reminder pemberian nilai kegiatan, dan konfirmasi nilai berhasil dikirim. Kirim email kepada admin/koordinator jika pembimbing lapangan belum mengisi nilai mendekati deadline atau token gagal/expired berulang. | Tinggi |
| EN-10 | Kirim email penilaian kepada dosen pembimbing saat mahasiswa sudah memenuhi syarat untuk dinilai, reminder pengisian nilai laporan/seminar, dan konfirmasi nilai tersimpan. Kirim email kepada admin saat semua komponen nilai sudah lengkap dan siap disahkan atau ada nilai belum lengkap mendekati penutupan periode. | Tinggi |
| EN-11 | Kirim email operasional kepada admin/super admin saat periode baru dibuat, periode dikunci/diselesaikan, konfigurasi penting berubah, import data selesai/gagal, atau terjadi error penting pada pengiriman email, storage, dan integrasi. | Menengah |
| EN-12 | Implementasi bertahap diprioritaskan berurutan: pendaftaran; deadline dan review laporan; pembekalan; perubahan pembimbing dan pindah tempat; token akses pembimbing lapangan; reminder validasi catatan harian dan nilai; lalu digest mingguan presensi/sanksi. | Tinggi |

### 9.6 Role Pembimbing Lapangan

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| PL-01 | Membuat halaman khusus role Pembimbing Lapangan. Akses dapat dilakukan melalui link URL + token yang dikirim ke email pembimbing lapangan. Token harus unik, memiliki masa berlaku, dapat dicabut, dan hanya membuka data mahasiswa/enrollment yang terkait dengan email pembimbing tersebut. | Tinggi |
| PL-02 | Menyediakan opsi login dengan email untuk Pembimbing Lapangan. Jika domain `gmail.com` diizinkan, pembimbing dapat menggunakan login email/Google sesuai kebijakan autentikasi yang ditetapkan. Sistem tetap membatasi akses berdasarkan email pembimbing lapangan yang tersimpan pada enrollment. | Menengah |
| PL-03 | Pembimbing Lapangan dapat memvalidasi catatan harian mahasiswa secara online, termasuk melihat tanggal, jam masuk/pulang, durasi, rencana aktivitas, realisasi, jarak presensi, dan foto presensi bila tersedia. | Tinggi |
| PL-04 | Pembimbing Lapangan dapat memberikan nilai kegiatan melalui form nilai lapangan PN-01. Nilai tersimpan sebagai bagian dari komponen penilaian akhir dan dapat direview/dikunci oleh admin sesuai alur pengesahan nilai. | Tinggi |

### 9.7 Laporan, Export, dan Operasional Produksi

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

### 9.8 Cutover dan Penghapusan Ketergantungan Lama

| Area | Rencana Implementasi | Prioritas |
|------|----------------------|-----------|
| Migrasi foto lama | Download/migrasikan foto lama dari Firebase Storage atau simpan URL lama secara terkendali. | Menengah |
| Uji paralel | Bandingkan output laporan lama dan baru dengan data historis. | Tinggi |
| Delta import | Buat prosedur import delta dari Firebase sebelum cutover final. | Tinggi |
| Decommission Firebase | Nonaktifkan write Firebase, arsipkan rules/config, dan lepas ketergantungan script lama. | Tinggi |
| Decommission Google Maps | Pastikan tidak ada script/API `google.maps.*` pada kode produksi baru. | Tinggi |
