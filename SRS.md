# **Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SRS) - Revisi 2.1**

> Nama sistem hasil rebranding: **SiLAT (Sistem Laporan Aktivitas Terpadu MBKM & Kerja Praktik)**.
> Pembaruan terakhir: **8 Juni 2026**.

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
- Manajemen master data: program kegiatan, organisasi akademik, prodi, mahasiswa, dosen, tempat PKL/mitra, periode
- Konfigurasi operasional per periode (jam kerja, deadline, kuota, sanksi)
- Pendaftaran PKL mahasiswa, usulan tempat baru, validasi admin
- **Check‑in ganda (masuk & pulang)** dengan perhitungan durasi harian
- **Lupa Presensi** dengan validasi pasangan presensi dan approval Pembimbing Lapangan/Admin/Koordinator
- **Manajemen progres laporan** (upload Bab, deadline, sanksi keterlambatan)
- **Penilaian numerik** dari pembimbing lapangan dan dosen pembimbing
- **Catatan harian** dan dukungan paraf (cetak form)
- **Finalisasi nilai akhir** serta cetak berita acara nilai dengan QR verifikasi
- Peta (Leaflet) untuk lokasi tempat PKL dan monitoring
- Laporan rekapitulasi, pelanggaran, dan nilai akhir dengan scope admin, koordinator, dan viewer laporan
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
| Snapshot GPS | Sampel lokasi browser yang disimpan server sebelum submit presensi. Snapshot menjadi sumber koordinat final presensi realtime. |
| Lupa Presensi | Pengajuan koreksi presensi oleh mahasiswa jika lupa melakukan presensi realtime. Data baru masuk ke `check_ins` setelah disetujui. |
| Durasi Harian | Selisih waktu antara check‑in pulang dan check‑in masuk (minimal 6 jam). |
| Progres Laporan | Unggah dokumen sesuai tahapan (Bab I, II, dst) dengan deadline. |
| Sanksi | Pengurangan nilai otomatis akibat keterlambatan atau durasi kurang. |
| Nilai Final | Nilai akhir yang sudah disahkan admin/koordinator setelah nilai dosen, nilai pembimbing lapangan, dan pengurangan sanksi diperhitungkan. |
| Konfigurasi | Parameter yang dapat diubah oleh admin per periode atau global. |
| Organisasi | Hirarki akademik untuk scope laporan, misalnya Universitas → Fakultas → Jurusan → Prodi. Prodi tetap menjadi tabel utama yang dipakai workflow operasional. |
| Viewer Laporan | Dosen yang diberi akses baca ke menu Analisis & Laporan sesuai scope organisasi/prodi tanpa hak aksi workflow operasional. |

---

## 2. Kebutuhan Fungsional (Lengkap dengan Penambahan)

> Notasi: **[KONFIG]** = data yang dapat diatur melalui halaman konfigurasi (per periode atau global).

### 2.1 Manajemen Master Data

| ID | Kebutuhan |
|----|-----------|
| MF-01 | CRUD Program Studi (kode, nama, fakultas, is_active). |
| MF-02 | CRUD Mahasiswa (NPM, nama, email, no HP, prodi, user_id) dengan opsi membuat/menautkan akun login otomatis dari email agar input tidak perlu dilakukan dua kali. |
| MF-03 | CRUD Dosen (nama, email, NIP, NIDN, prodi, status aktif, user_id) dengan opsi membuat/menautkan akun login otomatis dari email agar input tidak perlu dilakukan dua kali. |
| MF-03A | CRUD Program Kegiatan (kode, nama, deskripsi, rule_key, is_active). Contoh program: Kerja Praktik, Magang, Riset, Studi Independen. |
| MF-04 | CRUD Periode PKL (program, nama, tahun akademik, semester, batch, starts_at, ends_at, is_active, is_locked). Kombinasi nama/tahun akademik/semester/batch boleh sama pada program berbeda, tetapi harus unik dalam program yang sama. |
| MF-05 | CRUD Master Tempat PKL (nama, alamat, kota, provinsi, koordinat, kontak umum, is_active). |
| MF-06 | Bulk action pada Master Tempat PKL: hapus (jika tidak ada enrollment), merge ke tujuan. |
| MF-07 | CRUD User (akun login dengan role `admin`, `dosen`, `mahasiswa`, `pembimbing_lapangan`), termasuk foto profil/avatar dan field profil mahasiswa/dosen yang menyesuaikan role. |
| MF-08 | Manajemen Koordinator PKL (dosen, periode, prodi, status aktif). Satu periode‑prodi hanya boleh satu koordinator. |
| MF-09 | **[KONFIG]** Atur **kuota minimal & maksimal mahasiswa per tempat PKL** (default min=2, max=3). Sistem menolak enrollment jika melebihi max. |
| MF-10 | CRUD/seed organisasi akademik dengan relasi rekursif untuk merepresentasikan Universitas, Fakultas, dan Jurusan. `study_programs` terhubung ke organisasi induk agar scope laporan dapat dihitung dari hirarki. |
| MF-11 | Manajemen Viewer Laporan dari daftar dosen aktif. Viewer dapat diberi scope universitas, fakultas, jurusan, atau prodi; dosen tetap dapat memiliki role utamanya sebagai `dosen`. |

### 2.2 Konfigurasi Sistem (Per Periode & Global)

| ID | Kebutuhan |
|----|-----------|
| KS-01 | Setiap Periode PKL memiliki **konfigurasi operasional** (`internship_period_settings`), mencakup: <br> - **Jam kerja & status**: rentang waktu untuk *Masuk*, *Datang Terlambat*, *Pulang Cepat*, *Pulang* **[KONFIG]** <br> - **Durasi minimal harian** (default 6 jam) dan satuan sanksi per jam kurang **[KONFIG]** <br> - **Batas maksimum jarak** (meter) untuk validasi check‑in (opsional) **[KONFIG]** <br> - **Batas akurasi GPS**, maksimal umur snapshot lokasi, dan toleransi beda koordinat form terhadap snapshot GPS **[KONFIG]** <br> - **Radius bumi** untuk Haversine (default 6371 km) **[KONFIG]** <br> - **Daftar hari libur** yang dapat diganti/dikosongkan per periode **[KONFIG]** <br> - **Aturan laporan**: apakah hari Sabtu/Minggu dihitung? **[KONFIG]** <br> - **Batas maksimal pengajuan Lupa Presensi**; nilai 0 menonaktifkan fitur **[KONFIG]** <br> - **Komponen penilaian dosen, komponen penilaian Pembimbing Lapangan, dan pertanyaan survey institusi** **[KONFIG]** <br> - **Parameter peta**: center, zoom, tile URL **[KONFIG]** <br> - **Batas upload foto**: ukuran (MB), dimensi (px) **[KONFIG]** <br> - Atur kuota minimal & maksimal mahasiswa per tempat PKL **[KONFIG]** <br> - Konfigurasi dokumen cetak nilai: logo/header, format nomor berita acara, kota tanda tangan, Ketua Jurusan, dan dokumen pendukung lainnya **[KONFIG]** |
| KS-02 | **Deadline per periode** dikelola dalam tabel `period_deadlines` dengan jenis: <br> - Pendaftaran dibuka/ditutup <br> - Batas Proposal Rencana Kerja <br> - Batas Bab I, II, III, IV, V <br> - Batas Laporan Lengkap <br> - Batas Seminar <br> - Batas penyerahan hardcopy <br> Setiap deadline dapat memiliki bobot sanksi poin **[KONFIG]** |
| KS-03 | Hanya admin yang dapat mengubah konfigurasi periode. Periode terkunci (`is_locked=true`) tidak dapat diubah kecuali oleh admin super. |
| KS-04 | Setiap program kegiatan memiliki `rule_key`. Pada implementasi awal, semua program selain Kerja Praktik boleh memakai `rule_key='kerja_praktik'` agar workflow saat ini tetap berjalan. Desain konfigurasi harus memungkinkan rule per program didefinisikan ulang di masa datang tanpa mengubah data historis. |
| KS-05 | Admin dapat mengatur toggle pembatasan layanan mahasiswa per periode/global untuk usulan mitra, pindah mitra, dan perubahan pembimbing. Pendaftaran dibatasi oleh deadline pendaftaran, status aktif periode, dan status terkunci periode. Jika periode terkunci, layanan perubahan administratif mahasiswa ditutup, tetapi workflow pelaksanaan seperti presensi, unggah laporan, seminar, dan penyelesaian tetap mengikuti status operasional masing-masing modul. |

### 2.3 Workflow Mahasiswa (Pendaftaran & Pelaksanaan)

| ID | Kebutuhan |
|----|-----------|
| WM-01 | Mahasiswa melengkapi **Profil Saya** (NPM, nama, email, no HP, prodi). Wajib diisi sebelum pendaftaran PKL. |
| WM-02 | **Pendaftaran PKL/Program**: pilih program kegiatan, periode aktif, tempat PKL/mitra (atau usulkan baru), isi kontak mahasiswa, isi pembimbing lapangan (opsional). Sistem memeriksa kelayakan berdasarkan rule program. Pada implementasi awal, rule yang dipakai adalah rule Kerja Praktik: <br> - Telah mengambil mata kuliah KP/PKL di KRS semester ini (data input admin) <br> - Total SKS ≥ 100 (tidak termasuk KP) <br> - Semester ≥ 6 (S1) atau ≥ 4 (D3) <br> - IPK ≥ 2,00 <br> Jika tidak memenuhi, pendaftaran ditolak dengan pesan. |
| WM-03 | **Usulan Tempat PKL Baru** (nama instansi, alamat, kota, koordinat, kontak pembimbing lapangan). Disimpan ke `internship_place_proposals` status `pending`. |
| WM-04 | Admin/Koordinator **memvalidasi pendaftaran**: memilih dosen pembimbing (dari master dosen), mengisi/mengoreksi pembimbing lapangan, mengubah status enrollment (`pending_verification` → `active`/`revision_required`/`rejected`), memberi catatan. |
| WM-05 | Status enrollment: `draft`, `pending_verification`, `revision_required`, `active`, `completed`, `cancelled`, `rejected`. |
| WM-06 | **Pindah tempat PKL**: mahasiswa dapat mengajukan permohonan pindah instansi (alasan, bukti) selama layanan pindah mitra dibuka dan periode tidak terkunci. Admin/Kajur menyetujui/menolak. Setelah disetujui, enrollment diperbarui. |
| WM-07 | Hanya enrollment `active` pada periode yang masih aktif secara operasional yang dapat melakukan check‑in. |
| WM-08 | **Program Saya/Laporan Saya**: mahasiswa melihat daftar program yang diikuti, detail program, pembekalan, presensi dan catatan harian, progres laporan, seminar dan penilaian, penyelesaian hardcopy, sanksi, dan status nilai. |
| WM-09 | **Cetak Laporan** (PDF) hanya diizinkan jika: dosen pembimbing terpilih DAN pembimbing lapangan terisi. |

### 2.4 Check‑In Ganda, Durasi & Sanksi

| ID | Kebutuhan |
|----|-----------|
| CI-01 | Mahasiswa melakukan **check‑in masuk** (pagi) dan **check‑in pulang** (sore) pada hari yang sama. Sistem mengenali pasangan berdasarkan tanggal dan enrollment. |
| CI-02 | Setiap presensi realtime memakai `location_sample_id` dari snapshot GPS server-side, catatan, foto realtime, dan aksi masuk/pulang. Server menentukan status (Masuk/Datang Terlambat/Pulang Cepat/Pulang) berdasarkan jam dan konfigurasi periode. |
| CI-03 | Setelah check‑in pulang memiliki pasangan masuk, sistem menghitung **durasi harian** = waktu_pulang - waktu_masuk. Jika durasi < [durasi_minimal] (default 6 jam), maka catat **sanksi** dengan poin = (durasi_minimal - durasi_aktual) dalam jam. |
| CI-04 | Jika dalam satu hari hanya terdapat satu check‑in (misal hanya masuk atau hanya pulang), hari itu **tidak dihitung sebagai hari hadir**. Check-out tanpa check-in tetap boleh tersimpan sebagai data belum berpasangan dan mahasiswa diarahkan menggunakan Lupa Presensi Masuk jika kuota tersedia. |
| CI-05 | Server menyimpan device_info (user agent, IP), jarak (Haversine), dan path foto. |
| CI-06 | **Anti‑spoofing**: batas radius maksimum (opsional, lihat KS-01), rate limit (10 request/menit), snapshot GPS server-side, validasi umur snapshot, validasi jarak snapshot ke mitra, dan validasi beda koordinat form terhadap snapshot GPS. |
| CI-07 | **Cek kuota harian** : tidak ada pembatasan jumlah check‑in per hari selain satu pasang. |
| CI-08 | **Lupa Presensi**: mahasiswa dapat mengajukan koreksi presensi dengan tanggal, jam, jenis masuk/pulang, koordinat GPS, foto realtime, catatan aktivitas, dan alasan lupa. Pengajuan dibatasi kuota per periode, tidak boleh untuk tanggal masa depan, tanggal di luar periode presensi, Sabtu/Minggu, atau tanggal libur. |
| CI-09 | Pengajuan Lupa Presensi harus tetap memenuhi validasi pasangan: tidak boleh menggandakan aksi pada tanggal yang sama; pengajuan pulang membutuhkan presensi masuk pada tanggal tersebut; jam pulang harus setelah jam masuk. Pengajuan yang disetujui menghasilkan record `check_ins` dengan sumber koreksi dan dapat dipakai pada catatan harian/perhitungan durasi. |

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
| PN-01 | **Form nilai pembimbing lapangan** (skala 0–100) dengan komponen yang dapat dikonfigurasi per periode. Default: <br> - **A. Disiplin dan Kepatuhan**: Kehadiran (otomatis dari hari hadir valid/jumlah hari kerja efektif), Kepatuhan terhadap Tata Tertib <br> - **B. Kerja Sama**: Kerja Sama dengan Anggota Kelompok, Kolaborasi dengan Tim/Unit Lain, Komunikasi dan Respons terhadap Pembimbing <br> - **C. Prestasi Kerja**: Inisiatif dan Inovasi Kerja, Kemampuan Menyelesaikan Tugas, Tanggung Jawab dan Kesungguhan Kerja <br> - **Nilai lapangan** = rata-rata seluruh komponen yang berlaku. Rubrik dan survey institusi disimpan sebagai snapshot saat nilai disimpan. |
| PN-02 | **Form nilai dosen pembimbing**: nilai laporan/seminar berdasarkan rubrik yang dapat dikonfigurasi per periode. Bobot default laporan 60% dan seminar 40%. Rubrik disimpan sebagai snapshot saat nilai disimpan agar histori nilai tetap konsisten meskipun konfigurasi berubah. |
| PN-03 | **Nilai akhir KP/PKL** = (nilai lapangan × bobot_lapangan) + (nilai dosen × bobot_dosen) - pengurangan final. Sistem memberi suggest pengurangan dari total sanksi, tetapi admin/koordinator dapat menyesuaikan sebelum finalisasi. Nilai akhir dikonversi ke huruf mutu sesuai aturan Unila. |
| PN-04 | Hanya dosen pembimbing yang dapat mengisi nilai seminar/laporan via sistem. Pembimbing Lapangan mengisi nilai lapangan. Admin/koordinator mengesahkan nilai akhir sesuai scope. Setelah nilai akhir final, nilai dosen dan nilai pembimbing lapangan terkunci. |
| PN-05 | Setelah nilai final, mahasiswa dapat melihat rekap nilai akhir pada tab Penyelesaian dan mencetak Berita Acara Nilai. Dokumen cetak terdiri dari Berita Acara, Nilai Dosen, dan Nilai Pembimbing Lapangan, serta memuat QR verifikasi dokumen. |

### 2.7 Catatan Harian & Dukungan Paraf

| ID | Kebutuhan |
|----|-----------|
| CH-01 | Catatan harian disusun dari pasangan presensi harian: catatan presensi masuk menjadi **Rencana Aktivitas**, sedangkan catatan presensi pulang menjadi **Realisasi**. |
| CH-02 | Sistem menyediakan **cetak form bimbingan harian** berisi tanggal, jam masuk/pulang, durasi, jarak, rencana, realisasi, dan kolom paraf pembimbing lapangan. |
| CH-03 | Pembimbing Lapangan dapat memverifikasi catatan harian secara online melalui portal Pembimbing Lapangan, baik melalui akses token maupun login. Approval Lupa Presensi hanya ditampilkan pada akses login. |

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
| AU-02 | Role middleware (`admin`, `dosen`, `mahasiswa`, `pembimbing_lapangan`). Dosen dengan penugasan koordinator mendapat akses tambahan sebagai koordinator sesuai scope periode/prodi. Dosen dengan penugasan Viewer Laporan mendapat akses baca `report_viewer` sesuai scope organisasi/prodi. Pembimbing lapangan mendapat akses terbatas berdasarkan email pada enrollment atau token akses. |

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

-- Tabel organisasi akademik rekursif untuk scope laporan
CREATE TABLE organizations (
    id SERIAL PRIMARY KEY,
    parent_id INTEGER NULL REFERENCES organizations(id) ON DELETE SET NULL,
    type VARCHAR(40) NOT NULL, -- university, faculty, department
    code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tambahan kolom pada study_programs
ALTER TABLE study_programs ADD COLUMN organization_id INTEGER NULL REFERENCES organizations(id) ON DELETE SET NULL;

-- Tabel penugasan viewer laporan
CREATE TABLE report_viewer_assignments (
    id SERIAL PRIMARY KEY,
    lecturer_id INTEGER NOT NULL REFERENCES lecturers(id) ON DELETE CASCADE,
    organization_id INTEGER NULL REFERENCES organizations(id) ON DELETE SET NULL,
    study_program_id INTEGER NULL REFERENCES study_programs(id) ON DELETE SET NULL,
    level VARCHAR(40) NOT NULL, -- university, faculty, department, study_program
    status VARCHAR(20) DEFAULT 'active',
    starts_at DATE NULL,
    ends_at DATE NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (lecturer_id, organization_id, study_program_id, level)
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
- Constraint unik periode memakai scope `program_id, name, academic_year, semester, batch`. Dengan aturan ini, nama periode/tahun akademik/semester/batch yang sama boleh dipakai pada program kegiatan berbeda, tetapi duplikasi pada program yang sama ditolak melalui validasi form dan constraint database.

### 4.3A Organisasi Akademik dan Viewer Laporan

- Tabel `organizations` merepresentasikan hirarki akademik secara rekursif: Universitas → Fakultas → Jurusan.
- Tabel `study_programs` tetap menjadi master prodi utama dan ditautkan ke organisasi induk melalui `organization_id`.
- Seeder awal membuat hirarki **Universitas Lampung → FMIPA → Jurusan Ilmu Komputer** dan menautkan semua prodi yang ada ke Jurusan Ilmu Komputer.
- Tabel `report_viewer_assignments` memberi akses baca Analisis & Laporan kepada dosen aktif tanpa mengubah role utama dosen.
- Scope viewer dihitung dari level penugasan:
  - `university`: semua prodi di bawah universitas tersebut.
  - `faculty`: semua prodi di bawah fakultas dan turunannya.
  - `department`: semua prodi di bawah jurusan.
  - `study_program`: satu prodi spesifik.
- Viewer Laporan tidak memperoleh aksi workflow seperti validasi pendaftaran, finalisasi nilai, atau perubahan data operasional.

### 4.4 Perubahan pada tabel `internship_enrollments`

- Tambahkan `final_report_path TEXT`
- Tambahkan `total_sanctions_points INTEGER DEFAULT 0`

### 4.5 Perubahan pada tabel `check_ins`

- Tambahkan `pair_id INTEGER NULL` (referensi ke check‑in pasangan, atau gunakan logika query grouping per tanggal)
- Tambahkan `source_type VARCHAR DEFAULT 'realtime'` untuk membedakan presensi realtime dan koreksi dari Lupa Presensi.
- Tambahkan `forgotten_attendance_request_id INTEGER NULL` sebagai referensi ke pengajuan Lupa Presensi yang disetujui.
- Tambahkan `check_in_location_sample_id INTEGER NULL` sebagai referensi snapshot GPS realtime yang dipakai saat presensi.
- Tambahkan `student_location_accuracy_meters INTEGER NULL`, `location_status VARCHAR`, dan `location_flags JSON` untuk audit lokasi.

### 4.6 Tabel Implementasi Tambahan Saat Ini

Implementasi saat ini juga menambahkan tabel/struktur berikut:

| Tabel/Struktur | Fungsi |
|----------------|--------|
| `users.avatar_url` | Foto profil/avatar pengguna yang dipakai pada navigasi, profil, manajemen user, dan dokumen/tampilan yang mendukung foto profil. |
| `email_notifications` | Antrean email event-driven, status pengiriman, attempt, error, relasi notifiable, dan idempotency melalui `event_key`. |
| `system_settings` | Penyimpanan pengaturan global berbasis key/value JSON, termasuk toggle email, cakupan workflow notifikasi, dan override mail server. |
| `field_supervisor_access_tokens` | Token portal Pembimbing Lapangan, terhubung ke enrollment, email, hash token, masa berlaku, pencabutan, pembuat, dan waktu akses terakhir. |
| `seminar_requests` | Workflow seminar: pengajuan seminar, jalur ACC sistem/manual, validasi ACC manual, jadwal seminar, penilaian seminar sistem/manual, validasi nilai manual, berkas pendukung, dan snapshot rubrik penilaian dosen. |
| `field_supervisor_assessments` | Nilai pembimbing lapangan, skor komponen, snapshot rubrik, catatan untuk mahasiswa, rekomendasi mahasiswa, feedback institusi, snapshot survey, mode akses, dan identitas penilai. |
| `final_assessments` | Rekap nilai dosen, nilai pembimbing lapangan, pengurangan final, total nilai, huruf mutu, nomor berita acara, snapshot dokumen, token verifikasi, dan identitas finalisasi. |
| `forgotten_attendance_requests` | Pengajuan Lupa Presensi mahasiswa, termasuk tanggal/jam diminta, jenis masuk/pulang, lokasi, foto, catatan, alasan, status review, reviewer, dan relasi ke `check_ins` hasil koreksi. |
| `check_in_location_samples` | Snapshot GPS realtime sebelum submit presensi, berisi user, enrollment, koordinat GPS, akurasi, waktu tangkap, waktu pakai, dan device info. |
| `organizations` | Hirarki akademik rekursif untuk scope laporan, seperti universitas, fakultas, dan jurusan. |
| `study_programs.organization_id` | Relasi prodi ke organisasi induk agar scope fakultas/jurusan dapat diturunkan ke prodi. |
| `report_viewer_assignments` | Penugasan dosen sebagai Viewer Laporan pada level universitas, fakultas, jurusan, atau prodi dengan status dan masa berlaku opsional. |

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
| **Hari libur** | Daftar tanggal libur periode | `period_settings.report.holidays` | Dapat diganti penuh atau dikosongkan oleh admin |
| **Lupa Presensi** | Batas maksimal pengajuan koreksi presensi | `period_settings.report.max_forgotten_attendance_requests` | 3; 0 menonaktifkan fitur |
| **Presensi** | Batas akurasi GPS yang masih dianggap normal | `period_settings.check_in.max_location_accuracy_meters` | 100 meter |
| **Presensi** | Maksimal umur snapshot GPS yang boleh dipakai submit | `period_settings.check_in.location_sample_max_age_minutes` | 5 menit |
| **Presensi** | Toleransi beda koordinat form terhadap snapshot GPS | `period_settings.check_in.location_sample_mismatch_tolerance_meters` | 100 meter |
| **Kuota tempat PKL** | Minimal & maksimal mahasiswa per tempat | Global config (atau per periode) | min=2, max=3 |
| **Deadline** | Tanggal dan poin sanksi per jenis | Tabel `period_deadlines` | Ditentukan admin per periode |
| **Dokumen nilai** | Logo/header, nomor berita acara, kota tanda tangan, Ketua Jurusan | `period_settings.assessment_document.*` | Dipakai pada cetak berita acara nilai |

> **Cara akses konfigurasi**: Parameter periode dapat diubah melalui halaman admin **Konfigurasi Program**. Master program kegiatan dikelola melalui menu admin tersendiri. Pengaturan global email/notifikasi dikelola melalui **Email & Notifikasi**. Nilai default periode tetap disimpan di `config/monpkl.php` dan diwariskan saat periode baru dibuat.

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
| AU-02 | Sudah diimplementasikan sebagian | Role dasar `admin`, `dosen`, `mahasiswa`, dan `pembimbing_lapangan` sudah ada. Akses tambahan `koordinator` dihitung dari penugasan aktif dosen pada periode/prodi. Akses tambahan `report_viewer` dihitung dari penugasan Viewer Laporan aktif pada organisasi/prodi dan hanya membuka menu Analisis & Laporan sesuai scope. |
| NF-04 | Sudah diimplementasikan | Form utama menggunakan validasi server Laravel. |
| NF-05 | Sudah diimplementasikan | Password memakai hashing Laravel. |
| NF-07 | Sudah diimplementasikan sebagian | Route utama dilindungi auth dan role middleware. |
| NF-14 | Sudah diimplementasikan | Tile URL, center, dan zoom peta dapat berasal dari konfigurasi periode. |

### 8.2 Master Data dan Manajemen

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| MF-01 | Sudah diimplementasikan | CRUD Prodi tersedia di menu Manajemen. Prodi memiliki `degree_level` seperti D3, S1, S2, dan dipakai untuk membedakan aturan akademik. Prodi juga dapat terhubung ke organisasi induk untuk kebutuhan scope laporan. |
| MF-02 | Sudah diimplementasikan | CRUD Mahasiswa tersedia; profil mahasiswa juga dapat dilengkapi oleh mahasiswa sendiri. Form admin mendukung mode buat/tautkan akun login otomatis dari email, tautkan user yang sudah ada, atau simpan tanpa akun login. |
| MF-03 | Sudah diimplementasikan | Tabel dan CRUD Dosen tersedia, termasuk NIP, NIDN, prodi, status, dan relasi user. Form admin mendukung mode buat/tautkan akun login otomatis dari email, tautkan user yang sudah ada, atau simpan tanpa akun login. |
| MF-03A | Sudah diimplementasikan | Master Program Kegiatan tersedia dengan `code`, `name`, `description`, `rule_key`, dan `is_active`. Seed awal mencakup Kerja Praktik, Magang, dan Riset dengan fallback rule `kerja_praktik`. |
| MF-04 | Sudah diimplementasikan | CRUD Periode PKL tersedia dan periode terhubung ke Program Kegiatan. Keunikan periode sudah memakai scope program, sehingga kombinasi nama/tahun akademik/semester/batch yang sama dapat dipakai pada program berbeda tetapi ditolak jika duplikat dalam program yang sama. |
| MF-05 | Sudah diimplementasikan | Master Tempat PKL tersedia dengan input/edit lokasi Leaflet. Field `is_active` sudah tersedia dan tempat aktif dipakai pada pendaftaran serta permohonan pindah tempat. |
| MF-06 | Sudah diimplementasikan | Bulk hapus dan merge Master Tempat PKL tersedia. |
| MF-07 | Sudah diimplementasikan | CRUD User tersedia untuk role `admin`, `dosen`, `mahasiswa`, dan `pembimbing_lapangan`, termasuk foto profil/avatar. Jika role Mahasiswa atau Dosen dipilih, form user menampilkan field profil terkait dan memakai email user sebagai email profil agar tidak ada input email ganda. Akun pembimbing lapangan direkomendasikan dibuat/ditautkan dari menu Pembimbing Lapangan agar emailnya pasti terkait enrollment aktif, bukan dibuat bebas dari Manajemen User. |
| MF-08 | Sudah diimplementasikan | Penugasan Koordinator PKL per periode/prodi tersedia dan dibatasi satu koordinator per periode-prodi. Form tambah koordinator mendukung multi-select prodi untuk membuat beberapa penugasan sekaligus pada periode yang sama. |
| MF-09 | Sudah diimplementasikan sebagian | Kuota minimal dan maksimal tersedia di konfigurasi periode. Kuota maksimal sudah divalidasi pada pendaftaran mahasiswa dan input peserta admin; kuota minimal ditampilkan sebagai indikator peringatan pada validasi pendaftaran. |
| MF-10 | Sudah diimplementasikan | Tabel `organizations` tersedia. Seeder awal membuat Universitas Lampung, FMIPA, dan Jurusan Ilmu Komputer, lalu menautkan semua prodi saat ini ke Jurusan Ilmu Komputer. |
| MF-11 | Sudah diimplementasikan | Menu **Viewer Laporan** tersedia untuk admin. Admin dapat menugaskan dosen aktif sebagai viewer tingkat universitas, fakultas, jurusan, atau prodi dengan status aktif/nonaktif dan masa berlaku opsional. |

### 8.3 Konfigurasi Periode

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| KS-01 | Sudah diimplementasikan sebagian | Konfigurasi per periode tersedia melalui menu **Konfigurasi Program** dan tabel `internship_period_settings`, mencakup jam check-in, radius presensi, batas akurasi GPS, umur snapshot lokasi, toleransi beda koordinat, peta, upload foto, hari libur laporan, aturan laporan dasar, kuota, syarat akademik, komponen penilaian dosen, komponen penilaian Pembimbing Lapangan, survey institusi, serta model sanksi deadline tetap/per hari. Syarat minimal SKS sudah dibedakan per jenjang, misalnya D3=80 dan S1=100. |
| KS-02 | Sudah diimplementasikan | Tabel/model `period_deadlines` dan UI konfigurasi deadline per periode sudah tersedia, termasuk tanggal, poin penalti, dan flag penalti tetap. Deadline dipakai untuk menghitung sanksi unggahan progres laporan. |
| KS-03 | Sudah diimplementasikan | Konfigurasi hanya dapat diakses admin. Periode terkunci tidak dapat diubah melalui konfigurasi kecuali oleh super admin yang tercantum pada konfigurasi. Aksi **Set Selesai** pada periode mengubah enrollment aktif menjadi `completed`, menonaktifkan periode, dan mengunci periode. Checkbox `Terkunci` pada edit periode hanya mengubah status periode sehingga data peserta tidak otomatis diselesaikan. |
| KS-04 | Sudah diimplementasikan | Program terhubung ke periode dan memiliki `rule_key`. Implementasi saat ini memakai rule `kerja_praktik` sebagai fallback terstruktur, sehingga rule program lain dapat ditambahkan tanpa mengubah data historis. |
| KS-05 | Sudah diimplementasikan | Toggle layanan mahasiswa tersedia pada **Konfigurasi Program** untuk membatasi usulan mitra, pindah mitra, dan perubahan pembimbing. Pendaftaran tetap mengikuti deadline pendaftaran dan status periode. Jika periode terkunci, layanan administratif mahasiswa tersebut ditutup, sedangkan presensi, unggah laporan, seminar, dan penyelesaian tetap diproses sesuai syarat modul masing-masing. |

### 8.4 Workflow Mahasiswa

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| WM-01 | Sudah diimplementasikan | Mahasiswa dapat melengkapi profil: NPM, nama, email student, no HP, prodi. |
| WM-02 | Sudah diimplementasikan | Mahasiswa dapat mendaftar program melalui workflow 3 halaman: Program dan Periode, Mitra dan Pembimbing Lapangan, lalu Rule Program/Kelayakan Akademik/Dokumen. Mahasiswa memilih mitra aktif atau mengajukan mitra baru, mengisi kontak dan pembimbing lapangan, serta mengunggah satu PDF bukti akademik berisi Transkrip Sementara + KRS semester saat ini. Validasi kelayakan membaca `programs.rule_key`, `degree_level` prodi, dan konfigurasi akademik jenjang. |
| WM-03 | Sudah diimplementasikan | Mahasiswa dapat mengajukan tempat PKL baru melalui `internship_place_proposals`. |
| WM-04 | Sudah diimplementasikan | Admin/koordinator memiliki halaman Validasi Pendaftaran berbentuk tabel untuk menyetujui, meminta revisi, atau menolak pendaftaran, termasuk review dokumen bukti akademik dan catatan verifikasi. Kolom pembimbing tidak ditampilkan karena pembimbing diproses pada Peserta Periode. Halaman mendukung checkbox bulk action, check/uncheck all, dan tombol aksi ringkas berbasis Font Awesome. Mahasiswa dapat memperbaiki pendaftaran saat status `revision_required`. |
| WM-05 | Sudah diimplementasikan | Status enrollment sudah mendukung `draft`, `pending_verification`, `revision_required`, `active`, `inactive`, `completed`, `cancelled`, dan `rejected`, termasuk catatan admin. Dashboard mahasiswa menampilkan status efektif berdasarkan kombinasi status enrollment dan status periode: enrollment aktif pada periode aktif tampil sebagai **Aktif**, enrollment aktif pada periode nonaktif tampil sebagai **Periode Nonaktif**, draft/pending/revisi pada periode nonaktif tampil sebagai **Periode Tidak Tersedia**, sedangkan completed/rejected/cancelled mengikuti status akhirnya. |
| WM-06 | Sudah diimplementasikan | Mahasiswa dapat mengajukan pindah mitra dari kartu detail program. Form pindah mitra menerima nilai enrollment aktif dari tombol asal dan layanan dapat ditutup melalui toggle konfigurasi atau status periode terkunci. Admin/koordinator dapat menyetujui/menolak, dan mitra enrollment diperbarui saat disetujui. |
| WM-07 | Sudah diimplementasikan | Check-in hanya memakai enrollment `active` pada periode yang masih aktif. Dashboard dan halaman ringkasan hanya menampilkan tombol presensi untuk program yang aktif secara operasional. |
| WM-08 | Sudah diimplementasikan | Halaman mahasiswa sudah didesain ulang: dashboard menampilkan semua program aktif dalam kartu **Program Saya**, tombol **Detail** sebagai pintu masuk workflow, tombol **Presensi** untuk aksi harian, ringkasan status pendaftaran efektif, serta deadline penting. Detail program mahasiswa memakai tab **Detail Program**, **Pembekalan**, **Presensi & Catatan**, **Pelaporan**, **Seminar & Penilaian**, dan **Penyelesaian**. Tab Penyelesaian menampilkan nilai akhir setelah finalisasi dan tombol cetak berita acara nilai. |
| WM-09 | Sudah diimplementasikan sebagian | Cetak laporan mahasiswa memvalidasi dosen pembimbing dan pembimbing lapangan. Cetak Catatan Harian dan Cetak Laporan Presensi tersedia pada tab Presensi & Catatan. Berita Acara Nilai tersedia setelah nilai final. Output laporan lengkap utama masih dapat disempurnakan sesuai format resmi fakultas. |

### 8.5 Check-In, Peta, dan Laporan

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| CI-01 | Sudah diimplementasikan | Check-in memakai aksi eksplisit `check_in` dan `check_out`, lalu dipasangkan melalui `pair_id` pada hari/enrollment yang sama. |
| CI-02 | Sudah diimplementasikan | Server menentukan status berdasarkan jam konfigurasi dan presensi realtime memakai snapshot GPS server-side (`location_sample_id`) sebagai sumber koordinat final, disertai catatan dan foto realtime. |
| CI-03 | Sudah diimplementasikan | Saat check-out memiliki pasangan check-in, sistem menghitung durasi harian, menyimpan `duration_minutes`, menghitung poin sanksi jika durasi kurang, dan menambahkan poin ke total sanksi enrollment. Check-out tanpa pasangan belum menghitung durasi/sanksi. |
| CI-04 | Sudah diimplementasikan | Laporan monitoring dan ringkasan dashboard hanya menghitung hari hadir jika terdapat pasangan check-in masuk dan pulang. Check-out tanpa check-in dapat tersimpan sebagai data belum berpasangan dan diarahkan ke Lupa Presensi Masuk jika kuota tersedia. |
| CI-05 | Sudah diimplementasikan | Device info, jarak Haversine dari snapshot GPS ke mitra, lokasi kantor, lokasi mahasiswa, akurasi GPS, flag audit lokasi, dan foto disimpan. |
| CI-06 | Sudah diimplementasikan | Radius maksimum check-in dapat dikonfigurasi, route submit check-in dibatasi rate limit 10 request/menit, snapshot GPS disimpan server-side, snapshot kedaluwarsa ditolak, dan koordinat form yang berbeda jauh dari snapshot GPS ditolak sebagai indikasi manipulasi. |
| CI-07 | Sudah diimplementasikan | Sistem membatasi satu `check_in` dan satu `check_out` resmi per enrollment per hari. |
| CI-08 | Sudah diimplementasikan | Halaman presensi mahasiswa memiliki tab **Presensi** dan **Lupa Presensi**. Lupa Presensi menampilkan periode/mitra, kuota maksimal, kuota terpakai, sisa kuota, koordinat lokasi, kamera realtime, alasan, dan riwayat pengajuan. |
| CI-09 | Sudah diimplementasikan | Approval Lupa Presensi tersedia untuk admin/koordinator melalui menu **Lupa Presensi** dan untuk Pembimbing Lapangan login melalui blok **Pengajuan Lupa Presensi** di tab Catatan Harian. Pengajuan yang disetujui membuat `check_ins` koreksi dengan `source_type='forgotten_request'`. |
| PM-01 | Sudah diimplementasikan | Peta Tempat PKL memakai Leaflet dan marker cluster. |
| PM-02 | Sudah diimplementasikan sebagian | Peta Monitoring tersedia dengan filter periode/prodi dan scope role. |
| PM-03 | Sudah diimplementasikan | Peta picker tersedia pada input/edit tempat PKL. |
| PM-04 | Sudah diimplementasikan | Peta check-in menampilkan geolocation, marker instansi, dan polyline. |
| PM-05 | Sudah diimplementasikan | Input lokasi pada event pembekalan, master mitra, dan pengajuan mitra mahasiswa memiliki sugest lokasi dari riwayat internal, lalu fallback eksternal jika tidak ditemukan. |
| LR-01 | Sudah diimplementasikan sebagian | Rekap Monitoring tersedia dengan filter tanggal, periode, prodi, hari libur, Sabtu, dan Minggu. Status laporan dan sanksi belum tersedia. |
| LR-05 | Sudah diimplementasikan sebagian | Dashboard mahasiswa, dosen, dan koordinator menampilkan deadline penting dalam 7 hari ke depan berdasarkan scope role. Jika tidak ada deadline dalam 7 hari, sistem menampilkan deadline terdekat berikutnya sebagai fallback. |

### 8.6 Progres Laporan, Catatan Harian, dan Sanksi

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| PG-01 | Sudah diimplementasikan | Mahasiswa dapat mengunggah dokumen progres laporan pada tab Pelaporan untuk jenis Proposal, Bab I-V, dan Laporan Lengkap/Pelaporan Tahap 4 Bab 1 s.d. 5. Hardcopy dipindahkan ke tab Penyelesaian. Jenis Seminar dipisahkan ke workflow Pengajuan Seminar agar tidak bercampur dengan progres laporan biasa. |
| PG-02 | Sudah diimplementasikan | Unggahan terhubung ke `period_deadlines` berdasarkan periode enrollment dan mencatat waktu unggah. |
| PG-03 | Sudah diimplementasikan | Sistem menghitung sanksi keterlambatan unggahan berdasarkan deadline, poin penalti, dan mode penalti tetap/per hari, lalu menambahkan poin ke total sanksi enrollment. |
| PG-04 | Sudah diimplementasikan | Dosen pembimbing, koordinator sesuai scope, dan admin dapat memberi status `approved`, `revision`, atau `rejected` beserta catatan review. |
| PG-05 | Sudah diimplementasikan sebagian | Halaman Review Laporan menampilkan rekap unggahan progres untuk admin, dosen pembimbing, dan koordinator sesuai scope periode/prodi. |
| PG-06 | Sudah diimplementasikan sebagian | Unggahan `full_report` yang disetujui disimpan sebagai `final_report_path`. Perubahan otomatis status enrollment menjadi `completed` belum diaktifkan agar tetap menunggu modul penilaian akhir. |
| CH-01 | Sudah diimplementasikan | Catatan harian diambil dari pasangan presensi: catatan masuk sebagai rencana aktivitas dan catatan pulang sebagai realisasi. Tidak ada input/tabel log harian terpisah. |
| CH-02 | Sudah diimplementasikan | Sistem menyediakan cetak form catatan harian dari pasangan presensi dengan kolom tanggal, jam, jarak, rencana, realisasi, dan paraf pembimbing lapangan. |
| CH-03 | Sudah diimplementasikan | Pembimbing Lapangan dapat memvalidasi catatan harian secara online melalui portal token maupun login. Setelah validasi, baris tetap tampil dengan status Tervalidasi dan data validator. Approval Lupa Presensi hanya tersedia pada portal login. |

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
| EN-01 | Sudah diimplementasikan sebagian | Fondasi notifikasi email berbasis event tersedia melalui tabel `email_notifications`, `EmailNotificationService`, mailable `SystemNotificationMail`, command `silat:email-notifications:process`, dan scheduler tiap menit. Email memiliki subjek, penerima, body line terstruktur, tombol/link aksi, relasi `notifiable`, `event_key` idempotent, status pengiriman, retry manual untuk status gagal, serta pencatatan error. Menu **Email & Notifikasi** sudah tersedia dengan tab Status Notifikasi, Antrean Email, dan Mail Server. Pengaturan global aktif/nonaktif, cakupan workflow aktif/nonaktif, dan override SMTP disimpan di tabel `system_settings`; password SMTP disimpan terenkripsi. Jika pengiriman global nonaktif, event tetap berada di antrean dan tidak dikirim. Jika cakupan workflow nonaktif, event baru untuk prefix workflow tersebut tidak dibuat. |
| EN-02 | Sudah diimplementasikan | Email pendaftaran program dikirim kepada mahasiswa saat pendaftaran dikirim, revisi dikirim ulang, disetujui, diminta revisi, ditolak, atau data peserta diperbarui admin. Admin dan koordinator sesuai scope periode/prodi menerima email saat pendaftaran baru/revisi masuk serta reminder pendaftaran pending mendekati batas pendaftaran. |
| EN-03 | Sudah diimplementasikan | Email usulan mitra dikirim kepada mahasiswa saat usulan dikirim, disetujui sebagai master baru, digabung ke master mitra, atau ditolak. Admin menerima email saat ada usulan baru dan reminder untuk usulan pending minimal 48 jam. |
| EN-04 | Sudah diimplementasikan | Email pembekalan dikirim kepada mahasiswa saat event pembekalan aktif dibuka, reminder H-1/sebelum kegiatan, reminder ketika presensi belum tercatat mendekati waktu tutup, dan konfirmasi ketika presensi pembekalan berhasil dicatat. Admin dan koordinator sesuai scope periode/prodi menerima rekap setelah event ditutup berisi jumlah peserta, hadir, dan tidak hadir. Command `silat:orientation-notifications:queue` dijadwalkan hourly. |
| EN-05 | Sudah diimplementasikan | Digest presensi mingguan dikirim tanpa email per check-in/check-out. Mahasiswa menerima ringkasan hari tercatat, hari lengkap, durasi, jarak rata-rata, dan sanksi. Dosen pembimbing dan koordinator menerima digest mahasiswa dengan pola bermasalah seperti presensi tidak berpasangan, terlambat, durasi kurang, jarak tidak wajar, atau sanksi. Command `silat:attendance-digests:queue` dijadwalkan tiap Senin pukul 07.00. |
| EN-06 | Sudah diimplementasikan | Email laporan dikirim kepada mahasiswa saat upload berhasil, laporan disetujui, diminta revisi, atau ditolak. Jika upload melewati deadline, body email memuat sanksi keterlambatan yang tercatat. Dosen pembimbing menerima email saat ada laporan baru menunggu review, reminder jika pending review melewati threshold, dan summary mahasiswa bimbingan. Mahasiswa menerima reminder deadline H-7/H-3/H-1/hari H untuk jenis laporan yang belum diunggah/masih kosong. Admin dan koordinator menerima rekap laporan belum unggah, pending review, dan sanksi tertinggi. Summary admin/koordinator/dosen hanya dibuat untuk periode yang sudah mencapai tanggal Mulai Pelaksanaan/Presensi (`internship_periods.starts_at`). Command `silat:submission-progress-notifications:queue` dijadwalkan hourly. |
| EN-07 | Sudah diimplementasikan | Email perubahan pembimbing dikirim kepada mahasiswa saat permohonan dikirim, disetujui, atau ditolak. Admin dan koordinator sesuai scope periode/prodi menerima email saat ada permohonan baru serta reminder untuk permohonan pending minimal 48 jam. Saat permohonan disetujui dan dosen berubah, dosen pembimbing baru menerima notifikasi penugasan, sedangkan dosen pembimbing lama menerima notifikasi bahwa mahasiswa tidak lagi menjadi bimbingannya. |
| EN-08 | Sudah diimplementasikan | Email pindah tempat dikirim kepada mahasiswa saat permohonan dikirim, disetujui, atau ditolak. Admin dan koordinator sesuai scope periode/prodi menerima email saat ada permohonan baru serta reminder untuk permohonan pending minimal 48 jam. Saat permohonan disetujui, dosen pembimbing mahasiswa menerima notifikasi bahwa mahasiswa bimbingannya pindah mitra/tempat kegiatan. Modul pindah tempat juga dapat diakses koordinator dengan pembatasan data sesuai penugasan aktif. |
| EN-09 | Sudah diimplementasikan | Admin dapat membuat token akses pembimbing lapangan dari Peserta Periode. Sistem membuat token unik, masa berlaku 30 hari, dapat dicabut, dan mengantrekan email berisi URL portal Pembimbing Lapangan. Jika token lama kedaluwarsa dan belum ada token valid, command dapat membuat token baru dan mengantrekan email akses. Pembimbing Lapangan menerima reminder validasi catatan harian, reminder pengisian nilai, dan konfirmasi nilai berhasil disimpan. Admin dan koordinator menerima alert jika nilai belum diisi setelah form dapat dibuka atau token kedaluwarsa. Command `silat:field-supervisor-notifications:queue` dijadwalkan harian. |
| EN-10 | Sudah diimplementasikan | Email penilaian dikirim kepada dosen pembimbing saat seminar dijadwalkan sehingga mahasiswa siap dinilai, reminder pengisian nilai seminar jika jadwal sudah lewat tetapi nilai belum tersimpan, dan konfirmasi saat nilai seminar tersimpan. Admin menerima email saat nilai dosen dan nilai Pembimbing Lapangan lengkap dan mahasiswa siap difinalisasi. Admin/koordinator menerima alert jika komponen nilai belum lengkap mendekati akhir periode. Command `silat:assessment-notifications:queue` dijadwalkan harian. |
| EN-11 | Sudah diimplementasikan | Email operasional dikirim kepada admin saat periode dibuat, diperbarui, diselesaikan/dikunci, konfigurasi program diperbarui, import Firebase selesai, import Firebase gagal, dan ada email sistem gagal dikirim. Command `silat:operational-notifications:queue` dijadwalkan hourly untuk digest error pengiriman email. |
| NF-09 | Sudah diimplementasikan | Infrastruktur notifikasi otomatis dan scheduler sudah tersedia. Reminder otomatis yang sudah berjalan mencakup pendaftaran pending mendekati deadline, usulan mitra, perubahan pembimbing, pindah tempat, pembekalan, laporan/deadline, digest presensi, Pembimbing Lapangan, penilaian, dan operasional produksi. |

### 8.10 Seminar dan Penilaian Seminar

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| PN-01 | Sudah diimplementasikan | Pembimbing Lapangan dapat mengisi nilai lapangan pada tab **Penilaian dan Feedback**. Nilai Kehadiran dihitung otomatis dari hari hadir valid/jumlah hari kerja efektif, mengabaikan Sabtu/Minggu dan hari libur. Komponen penilaian dan pertanyaan survey institusi dapat dikonfigurasi per periode dan disimpan sebagai snapshot saat nilai tersimpan. |
| PN-02 | Sudah diimplementasikan sebagian | Workflow seminar menyediakan penilaian seminar oleh dosen pembimbing via sistem dan jalur manual. Pada jalur sistem, dosen mengisi komponen nilai seminar/laporan sesuai rubrik konfigurasi periode dan sistem menghitung total. Pada jalur manual, mahasiswa menginput komponen nilai, mengunggah berkas bukti/form penilaian, lalu admin/koordinator memvalidasi. Rubrik disimpan sebagai snapshot saat nilai tersimpan. Nilai dosen dipakai pada finalisasi nilai akhir. |
| PN-03 | Sudah diimplementasikan | Halaman **Finalisasi Nilai** menghitung nilai dasar dari nilai dosen dan pembimbing lapangan masing-masing 50%, memberi suggest pengurangan dari sanksi, memungkinkan admin/koordinator menyimpan pengurangan final dan nomor berita acara, lalu menghasilkan total nilai dan huruf mutu. |
| PN-04 | Sudah diimplementasikan | Setelah nilai akhir final, nilai dosen dan nilai pembimbing lapangan dikunci agar tidak dapat diedit. |
| PN-05 | Sudah diimplementasikan | Cetak Berita Acara Nilai tersedia setelah nilai final. Dokumen memuat halaman berita acara, nilai dosen, nilai pembimbing lapangan, nomor berita acara, data Ketua Jurusan dari konfigurasi, data Koordinator Periode Program sesuai prodi mahasiswa, dan QR verifikasi memakai API QR server. |
| PG-06 | Sudah diimplementasikan sebagian | Pengajuan seminar dipisahkan dari progres laporan. Mahasiswa baru dapat mengajukan seminar setelah mengunggah Pelaporan Tahap 4/Laporan Lengkap Bab 1 s.d. 5. Workflow seminar mendukung ACC dosen via sistem atau upload berkas ACC manual yang divalidasi admin/koordinator, penjadwalan seminar, penilaian seminar, serta validasi nilai manual. |

### 8.11 Role Pembimbing Lapangan

| ID SRS | Status Implementasi | Catatan |
|--------|---------------------|---------|
| PL-01 | Sudah diimplementasikan | Portal Pembimbing Lapangan tersedia melalui URL token dan login. Token disimpan pada `field_supervisor_access_tokens`, unik, memiliki masa berlaku, dapat dicabut, dan hanya membuka enrollment terkait. |
| PL-02 | Sudah diimplementasikan | Role `pembimbing_lapangan` tersedia. Login Google/email pembimbing lapangan diizinkan jika email tersebut tercatat pada enrollment aktif sebagai `field_supervisor_email`. Akses portal saat login tetap dibatasi berdasarkan email pembimbing lapangan pada enrollment. |
| PL-03 | Sudah diimplementasikan | Dashboard Pembimbing Lapangan menampilkan ringkasan periode aktif dan periode selesai, sedangkan menu **Mahasiswa Bimbingan** menampilkan daftar mahasiswa terkait. Istilah PL tidak dipakai pada UI; sistem memakai **Pembimbing Lapangan**. |
| PL-04 | Sudah diimplementasikan | Portal Pembimbing Lapangan memiliki tab **Catatan Harian** untuk validasi catatan harian dan approval Lupa Presensi, serta tab **Penilaian dan Feedback** untuk nilai dan masukan institusi. |
| PL-05 | Sudah diimplementasikan | Reminder email Pembimbing Lapangan tersedia untuk catatan harian belum divalidasi, pengajuan Lupa Presensi pending, nilai Pembimbing Lapangan belum diisi, serta reminder H-7/H-3/H-1 sebelum deadline `full_report` untuk validasi catatan harian dan pengisian nilai jika belum lengkap. |
| PL-06 | Sudah diimplementasikan | Portal Pembimbing Lapangan menampilkan detail foto audit pada tab Catatan Harian. Foto presensi masuk/pulang dapat dibuka dari baris catatan harian, sedangkan foto bukti Lupa Presensi tampil pada blok pengajuan Lupa Presensi pending. |

---

## 9. Identifikasi Fitur yang Akan Diimplementasikan

Bagian ini hanya mencatat kebutuhan SRS yang belum tersedia atau masih perlu disempurnakan pada iterasi berikutnya.

### 9.1 Penilaian dan Nilai Akhir

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| PN-06 | Sempurnakan format cetak resmi sesuai template fakultas terbaru jika ada perubahan logo/header, penomoran, atau struktur tanda tangan. | Menengah |
| PN-07 | Tambahkan audit log detail untuk perubahan nilai, finalisasi, pembatalan finalisasi jika kebijakan akademik mengizinkan, dan akses verifikasi QR. | Menengah |

### 9.2 Email Notifikasi

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| EN-12 | Implementasi email utama EN-01 sampai EN-11 sudah tercatat pada Bagian 8. Pengembangan lanjutan berfokus pada audit granular, preferensi penerima per role, template email per institusi, dan observabilitas produksi. | Menengah |

### 9.3 Role Pembimbing Lapangan

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|

### 9.4 Laporan, Export, dan Operasional Produksi

| ID SRS | Rencana Implementasi | Prioritas |
|--------|----------------------|-----------|
| LR-06 | **Sudah diimplementasikan.** Buat **Dashboard Progres Peserta Kegiatan** berbasis filter periode, program, prodi, mitra, dosen pembimbing, status peserta, dan rentang tanggal. Dashboard menampilkan kartu ringkasan total peserta, peserta aktif/selesai, presensi belum lengkap, catatan harian belum divalidasi, laporan terlambat, seminar belum diajukan, nilai belum lengkap, nilai final, dan sanksi tertinggi pada dashboard admin/koordinator. | Tinggi |
| LR-07 | **Sudah diimplementasikan.** Tambahkan **progress funnel** pelaksanaan: pendaftaran disetujui → presensi aktif → laporan lengkap → seminar dijadwalkan/selesai → nilai dosen masuk → nilai Pembimbing Lapangan masuk → nilai final. Funnel dipakai untuk membaca bottleneck proses dan tersedia untuk admin/koordinator/Viewer Laporan pada menu Analisis & Laporan sesuai scope. | Tinggi |
| LR-08 | **Sudah diimplementasikan.** Tambahkan **risk scoring peserta** dengan kategori Aman, Perlu Dipantau, Berisiko, dan Kritis. Indikator mencakup presensi tidak lengkap, tidak hadir beruntun, Lupa Presensi pending, catatan harian belum divalidasi, laporan terlambat/revisi berulang, seminar belum diajukan, nilai belum lengkap, dan total sanksi. Halaman tersedia untuk admin/koordinator/Viewer Laporan pada menu Analisis & Laporan dengan filter periode, program, prodi, dan kategori risiko sesuai scope. | Tinggi |
| LR-09 | **Sudah diimplementasikan.** Tambahkan tabel **Peserta Perlu Tindak Lanjut** yang dapat di-drill-down dari kartu/grafik. Tabel memuat mahasiswa, prodi, mitra, dosen, status risiko, masalah utama, sanksi, dan aksi cepat menuju presensi, laporan, seminar, Lupa Presensi, atau finalisasi nilai. Drill-down tersedia dari kartu kategori pada halaman Risk Scoring; aksi operasional tetap mengikuti role admin/koordinator, sedangkan Viewer Laporan bersifat baca sesuai scope. | Tinggi |
| LR-10 | **Sudah diimplementasikan.** Tambahkan visualisasi **heatmap kehadiran** per mahasiswa dan tanggal. Warna membedakan hadir valid, presensi satu sisi/tidak valid, tidak hadir, Lupa Presensi disetujui, Sabtu/Minggu, dan hari libur. Halaman tersedia untuk admin/koordinator/Viewer Laporan pada menu Analisis & Laporan dengan filter periode, program, prodi, dan rentang tanggal sesuai scope. | Tinggi |
| LR-11 | **Sudah diimplementasikan.** Tambahkan halaman **Grafik Operasional** pada menu Analisis & Laporan untuk admin/koordinator/Viewer Laporan. Grafik mencakup tren presensi harian, stacked bar status peserta per prodi, donut status laporan lengkap, bar chart top sanksi, dan progress status nilai dosen/Pembimbing Lapangan/final dengan filter periode, program, prodi, dan rentang tanggal sesuai scope. | Tinggi |
| LR-02 | **Sudah diimplementasikan.** Buat **Rekap Pelanggaran & Sanksi** pada menu Analisis & Laporan untuk admin/koordinator/Viewer Laporan. Rekap memisahkan sumber sanksi presensi, keterlambatan laporan, dan pengurangan final, dilengkapi filter periode, program, prodi, rentang tanggal, total ringkasan, dan aksi cepat sesuai hak akses role. | Tinggi |
| LR-03 | **Sudah diimplementasikan.** Buat **Rekap Nilai Akhir** pada menu Analisis & Laporan untuk admin/koordinator/Viewer Laporan. Rekap memuat nilai dosen, nilai Pembimbing Lapangan, nilai dasar, pengurangan final, total nilai, huruf mutu, nomor berita acara, status final, filter periode/program/prodi/status, dan aksi cepat sesuai hak akses role. | Tinggi |
| LR-12 | Tambahkan drill-down dari chart/grafik ke daftar mahasiswa terkait agar admin/koordinator dapat langsung melakukan tindak lanjut tanpa berpindah konteks manual. | Tinggi |
| LR-01 | Lengkapi Rekap Monitoring dengan status laporan, seminar, nilai, Lupa Presensi, validasi catatan harian, dan sanksi. | Menengah |
| LR-04 | Tambahkan export PDF/Excel/CSV untuk laporan utama: Rekap Monitoring, Presensi dan Catatan Harian, Sanksi, Progres Laporan, Status Seminar, Nilai Akhir, dan Finalisasi Nilai. | Menengah |
| LR-13 | Tambahkan export **snapshot dashboard** ke PDF yang berisi filter aktif, kartu ringkasan, grafik utama, peserta berisiko, tanggal cetak, dan nama pencetak. | Menengah |
| NF-11 | Siapkan strategi backup database harian, retensi backup, uji restore berkala, dan dokumentasi prosedur pemulihan. | Menengah |
| NF-10 | Buat audit log perubahan konfigurasi, sanksi, nilai, finalisasi, pembatalan finalisasi jika diizinkan, master data, enrollment, dan approval Lupa Presensi. | Menengah |
| NF-09 | Lengkapi reminder otomatis khusus deadline laporan/progres sesuai EN-06. Fondasi email otomatis sudah tercatat pada Bagian 8. | Menengah |
| NF-06 | Perketat akses file foto agar tidak terbuka publik tanpa otorisasi jika produksi membutuhkan. | Menengah |
| NF-12 | Uji dan rapikan responsif untuk tablet/desktop pada semua halaman dashboard, chart, tabel drill-down, dan export. | Menengah |
| NF-13 | Standarkan semua pesan validasi dalam Bahasa Indonesia. | Rendah |
| LR-14 | Tambahkan preferensi tampilan dashboard per role, misalnya pilihan chart favorit, kolom tabel default, dan penyimpanan filter terakhir. | Rendah |

### 9.5 Cutover dan Penghapusan Ketergantungan Lama

| Area | Rencana Implementasi | Prioritas |
|------|----------------------|-----------|
| Migrasi foto lama | Download/migrasikan foto lama dari Firebase Storage atau simpan URL lama secara terkendali. | Menengah |
| Uji paralel | Bandingkan output laporan lama dan baru dengan data historis. | Tinggi |
| Delta import | Buat prosedur import delta dari Firebase sebelum cutover final. | Tinggi |
| Decommission Firebase | Nonaktifkan write Firebase, arsipkan rules/config, dan lepas ketergantungan script lama. | Tinggi |
| Decommission Google Maps | Pastikan tidak ada script/API `google.maps.*` pada kode produksi baru. | Tinggi |


Catatan Perbaikan:
- Laporan hanya mengacu pada peserta aktif, masih ada kesalahan pada progrees funnel, presensi aktif masih menghitung data peserta non aktif, sehingga tidak konsisten: perlu diperiksa juga laporan lainnya
- Pada master prodi -> memetakan Foreign Key ke Jurusan -> Dropdown
- Perlu manajemen master ogranisasi
  