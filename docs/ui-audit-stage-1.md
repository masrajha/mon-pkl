# Audit UI Tahap 1 - Inventarisasi Halaman

Dokumen ini adalah implementasi Tahap 1 dari `ui-design.md`: audit UI dan inventarisasi halaman untuk redesain **SiLAT**.

## Ringkasan

- Scope audit: `backend/resources/views`, `backend/resources/css`, `backend/resources/js`, `backend/routes`, dan teks UI pada controller.
- Master **Program Kegiatan** sudah masuk sebagai data dasar baru melalui halaman `management/programs`.
- UI saat ini memakai Laravel Blade, Tailwind CSS, Alpine.js, Leaflet, dan komponen Blade bawaan Breeze.
- Rebranding belum konsisten: masih banyak teks `MonPKL`, `Mon PKL`, `PKL`, `Tempat PKL`, `Periode PKL`, dan `Pendaftaran PKL`.
- Prioritas redesign tertinggi: layout/navigation, dashboard, pendaftaran, check-in, peta monitoring, validasi pendaftaran, laporan monitoring, dan laporan mahasiswa.

## Inventaris Halaman Berdasarkan Role

### Publik dan Autentikasi

| Area | Route | View | Catatan Audit |
|------|-------|------|---------------|
| Landing | `/` | `welcome.blade.php` | Masih template awal Laravel, perlu diselaraskan dengan SiLAT atau diarahkan ke login. |
| Login | `/login` | `auth/login.blade.php` | Perlu cek branding SiLAT dan opsi Google SSO. |
| Register | `/register` | `auth/register.blade.php` | Perlu keputusan apakah registrasi umum tetap dibuka. |
| Reset password | `/forgot-password`, `/reset-password` | `auth/*` | Pakai komponen Breeze standar. |
| Verifikasi email | `/verify-email` | `auth/verify-email.blade.php` | Pakai komponen Breeze standar. |

### Semua Role Terautentikasi

| Area | Route | View | Catatan Audit |
|------|-------|------|---------------|
| Dashboard umum | `/dashboard` | `dashboard.blade.php` | Berisi card per role, masih memakai label `Sistem Monitoring PKL`, `PKL Saya`, `Tempat PKL`. |
| Profil user | `/profile` | `profile/edit.blade.php` | Komponen Breeze, belum spesifik SiLAT. |
| Peta tempat | `/maps/places` | `maps/places.blade.php` | Prioritas tinggi karena memuat peta dan tabel tempat. |
| Peta monitoring | `/maps/monitoring` | `maps/monitoring.blade.php` | Prioritas tinggi untuk redesign workflow monitoring. |
| Rekap monitoring | `/reports/monitoring` | `reports/monitoring.blade.php` | Prioritas tinggi, masih memakai label PKL. |

### Admin

| Area | Route | View | Catatan Audit |
|------|-------|------|---------------|
| Dashboard manajemen | `/management` | `management/dashboard.blade.php` | Card statistik admin, perlu masuk program kegiatan dan status validasi. |
| User | `/management/users` | `management/users/*` | CRUD standar tabel dan form. |
| Mahasiswa | `/management/students` | `management/students/*` | CRUD standar, terkait profil mahasiswa. |
| Dosen | `/management/lecturers` | `management/lecturers/*` | CRUD standar, perlu konsistensi status dan prodi. |
| Koordinator | `/management/coordinators` | `management/coordinators/*` | Masih memakai label Koordinator PKL. |
| Prodi | `/management/study-programs` | `management/study-programs/*` | Master akademik. |
| Program Kegiatan | `/management/programs` | `management/programs/*` | Master baru untuk Kerja Praktik, Magang, Riset, dll. |
| Periode | `/management/periods` | `management/periods/*` | Sudah memuat program, masih label Periode PKL. |
| Tempat | `/management/places` | `management/places/index.blade.php` | Masih label Tempat PKL, perlu menjadi Tempat MBKM/KP atau Mitra. |
| Input lokasi | `/internship-places/create` | `internship-places/form.blade.php` | Dipakai admin/dosen; peta picker prioritas tinggi. |
| Usulan tempat | `/management/place-proposals` | `management/place-proposals/index.blade.php` | Validasi usulan tempat, masih label tempat PKL. |
| Pindah tempat | `/management/relocations` | `management/relocations/index.blade.php` | Workflow aktif, perlu terminology program. |
| Peserta periode | `/management/enrollments` | `management/enrollments/*` | CRUD peserta umum, perlu dibedakan dari validasi khusus. |
| Validasi pendaftaran | `/management/enrollment-validations` | `management/enrollments/validations.blade.php` | Prioritas tinggi; halaman khusus approval/revisi/tolak. |
| Konfigurasi sistem | `/system-configurations` | `system-configurations/*` | Prioritas tinggi karena memuat deadline, peta, rule, kuota. |

### Koordinator

| Area | Route | View | Catatan Audit |
|------|-------|------|---------------|
| Dashboard koordinator | `/coordinator` | `coordinator/dashboard.blade.php` | Masih label Koordinator PKL. |
| Validasi pendaftaran | `/management/enrollment-validations` | `management/enrollments/validations.blade.php` | Dipakai koordinator sesuai scope penugasan. |
| Peta monitoring | `/maps/monitoring` | `maps/monitoring.blade.php` | Perlu state filter program/periode/prodi. |
| Rekap monitoring | `/reports/monitoring` | `reports/monitoring.blade.php` | Perlu filter dan ringkasan sesuai scope koordinator. |

### Dosen Pembimbing

| Area | Route | View | Catatan Audit |
|------|-------|------|---------------|
| Dashboard umum | `/dashboard` | `dashboard.blade.php` | Bagian `Bimbingan Dosen` sudah ada, belum ada detail bimbingan/progres laporan. |
| Input lokasi | `/internship-places/create` | `internship-places/form.blade.php` | Akses dosen tersedia. |
| Peta monitoring | `/maps/monitoring` | `maps/monitoring.blade.php` | Scope dosen via mahasiswa bimbingan. |
| Rekap monitoring | `/reports/monitoring` | `reports/monitoring.blade.php` | Perlu status bimbingan/progres pada fase lanjut. |

### Mahasiswa

| Area | Route | View | Catatan Audit |
|------|-------|------|---------------|
| Dashboard mahasiswa | `/student` | `student/dashboard.blade.php` | Prioritas tinggi; banyak workflow mahasiswa terkumpul di sini. |
| Profil mahasiswa | `/student/profile` | `student/profile.blade.php` | Prasyarat pendaftaran. |
| Pendaftaran | `/student/enrollments/create` | `student/enrollments/create.blade.php` | Prioritas tinggi; sudah ada pilihan program dan periode. |
| Usulan tempat | `/student/place-proposals/create` | `student/proposals/create.blade.php` | Masih label Tempat PKL. |
| Pindah tempat | `/student/relocations/create` | `student/relocations/create.blade.php` | Masih label Tempat PKL. |
| Check-in | `/check-ins/create` | `check-ins/create.blade.php` | Prioritas tinggi; peta, kamera, izin browser, dan status presensi. |
| Laporan saya | `/student/reports/{enrollment}` | `student/reports/show.blade.php` | Prioritas tinggi; ringkasan laporan mahasiswa. |
| Cetak laporan | `/student/reports/{enrollment}/print` | `student/reports/print.blade.php` | Masih label Laporan Monitoring PKL. |

## Inventaris Komponen UI

| Komponen | Lokasi | Status Saat Ini | Catatan Redesign |
|----------|--------|-----------------|------------------|
| Layout aplikasi | `layouts/app.blade.php`, `layouts/navigation.blade.php` | Ada | Prioritas rebranding logo, nama, menu, dan responsive navigation. |
| Layout guest | `layouts/guest.blade.php` | Ada | Perlu branding SiLAT pada halaman auth. |
| Navigasi manajemen | `management/partials/nav.blade.php` | Ada | Perlu struktur menu lebih jelas dan istilah baru. |
| Button | `components/primary-button`, `secondary-button`, `danger-button` | Ada | Bisa dipertahankan, perlu standar warna brand. |
| Input | `components/text-input`, `input-label`, `input-error` | Ada | Bisa menjadi basis design system. |
| Dropdown | `components/dropdown*` | Ada | Dipakai navigation. |
| Modal | `components/modal.blade.php` | Ada | Ada komponen, belum dominan digunakan untuk workflow CRUD. |
| Card statistik | `dashboard.blade.php`, `management/dashboard.blade.php`, `student/dashboard.blade.php` | Ada | Masih tidak seragam; sebagian memakai class custom `monpkl-*`. |
| Tabel data | Banyak view `management/*`, `reports/*` | Ada | Perlu komponen reusable atau pola konsisten. |
| Filter bar | `reports/monitoring`, `maps/partials/filters`, `management/*` | Ada | Perlu standar spacing, label, dan responsive behavior. |
| Form | CRUD admin, mahasiswa, konfigurasi | Ada | Banyak inline class; perlu konsolidasi pola. |
| Toast/alert | `session('status')`, `$errors->any()` | Ada | Masih berulang per view, bisa jadi partial/komponen. |
| Badge/status pill | `monpkl-status-pill`, rounded status di view | Ada | Perlu token status konsisten. |
| Peta Leaflet | `maps/*`, `internship-places/form`, `check-ins/create`, `resources/js/maps/leafletMaps.js` | Ada | Prioritas QA visual dan state kosong/error. |
| Kamera/check-in | `checkInCamera.js`, `browserPermissions.js` | Ada | Perlu audit state izin, loading, gagal, sukses. |
| Halaman laporan/cetak | `reports/monitoring`, `student/reports/*` | Ada | Perlu format cetak dan ringkasan visual. |

## Temuan Rebranding

### Branding Lama yang Masih Muncul

| Pola | Contoh Lokasi | Rekomendasi |
|------|---------------|-------------|
| `MonPKL`, `Mon PKL`, `monpkl-*` | `layouts/navigation.blade.php`, `resources/css/app.css`, `SRS.md`, command import | Ganti display UI ke SiLAT. Class CSS dapat diganti bertahap atau tetap sebagai class internal sementara. |
| `Sistem Monitoring PKL` | `dashboard.blade.php` | Ubah ke `SiLAT`. |
| `PKL Saya` | Navigation dan dashboard | Ubah ke `MBKM/KP Saya` atau `Program Saya`. |
| `Pendaftaran PKL` | Mahasiswa, pesan controller, navigation | Ubah ke `Pendaftaran Program`. |
| `Tempat PKL` | Peta, master tempat, form, pesan validasi | Ubah ke `Tempat MBKM/KP` atau `Mitra`. |
| `Periode PKL` | Periode, konfigurasi, pesan check-in | Ubah ke `Periode Program`. |
| `Koordinator PKL` | Dashboard koordinator dan menu | Ubah ke `Koordinator Program`. |
| `Laporan Monitoring PKL` | Cetak laporan | Ubah ke `Laporan Monitoring MBKM/KP`. |

### Catatan

- Istilah `PKL/KP` masih boleh muncul pada konteks rule Kerja Praktik, tetapi display umum aplikasi harus menuju SiLAT.
- `Program Kegiatan` sudah menjadi master baru dan harus tampil pada workflow periode, pendaftaran, validasi, monitoring, dan laporan.

## Prioritas Halaman

| Prioritas | Halaman | Alasan |
|-----------|---------|--------|
| P0 | `layouts/navigation.blade.php` dan `dashboard.blade.php` | Branding dan navigasi adalah first impression semua role. |
| P0 | `student/enrollments/create.blade.php` | Workflow awal mahasiswa, sudah memakai Program Kegiatan. |
| P0 | `check-ins/create.blade.php` | Workflow harian kritikal, melibatkan peta, kamera, lokasi, dan error state. |
| P0 | `maps/monitoring.blade.php` dan `resources/js/maps/leafletMaps.js` | Monitoring utama dosen/admin/koordinator. |
| P0 | `management/enrollments/validations.blade.php` | Approval pendaftaran, berdampak ke status mahasiswa. |
| P1 | `reports/monitoring.blade.php` | Output rekap operasional, perlu filter program dan konsistensi tabel. |
| P1 | `student/dashboard.blade.php` dan `student/reports/show.blade.php` | Ringkasan utama mahasiswa. |
| P1 | `system-configurations/edit.blade.php` | Konfigurasi rule, deadline, peta, dan kuota. |
| P1 | `management/programs/*`, `management/periods/*`, `management/places/*` | Master data inti untuk SiLAT. |
| P2 | Auth, profile, CRUD user/prodi/dosen/mahasiswa | Dapat dirapikan setelah workflow utama stabil. |

## Checklist Tahap 1

- [x] Master Program Kegiatan masuk audit.
- [x] Halaman Blade diinventarisasi berdasarkan role.
- [x] Komponen reusable dan pola UI dicatat.
- [x] Teks lama MonPKL/PKL ditandai untuk rebranding.
- [x] Halaman prioritas tinggi dipetakan.

## Rekomendasi Lanjutan

Tahap 2 sebaiknya dimulai dari layout dan navigation: ubah display brand ke **SiLAT**, rapikan istilah menu, lalu baru masuk ke dashboard dan workflow prioritas P0. Ini membuat perubahan berikutnya lebih terasa konsisten tanpa harus menyentuh semua halaman sekaligus.
