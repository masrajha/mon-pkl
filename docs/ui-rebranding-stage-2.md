# Fondasi Rebranding Tahap 2 - SiLAT

Dokumen ini mencatat implementasi Tahap 2 berdasarkan audit UI Tahap 1.

## Brand Target

| Item | Nilai |
|------|-------|
| Nama aplikasi | SiLAT |
| Kepanjangan | Sistem Laporan Aktivitas Terpadu |
| Cakupan | MBKM & Kerja Praktik |
| Nama lama | Mon PKL / MonPKL |

## Perubahan Fondasi

- `APP_NAME` pada `backend/.env` diubah menjadi `SiLAT`.
- Layout utama memakai brand `SiLAT` dan subtitle `MBKM & Kerja Praktik`.
- Guest layout/auth diberi identitas `SiLAT` dan deskripsi `Sistem Laporan Aktivitas Terpadu`.
- Logo aplikasi Blade diganti dari ikon default Laravel menjadi wordmark SiLAT sederhana.
- Navigation desktop dan mobile memakai istilah umum:
  - `Program Saya`
  - `Pendaftaran Program`
  - `Periode Program`
  - `Koordinator Program`
  - `Mitra`
  - `Peta Mitra`
- Dashboard umum memakai headline `SiLAT` dan deskripsi brand baru.
- Label halaman prioritas dari audit digeser dari `PKL` generik ke istilah program/mitra.

## Istilah Baru

| Istilah Lama | Istilah Baru |
|--------------|--------------|
| MonPKL / Mon PKL | SiLAT |
| PKL Saya | Program Saya |
| Pendaftaran PKL | Pendaftaran Program |
| Periode PKL | Periode Program |
| Koordinator PKL | Koordinator Program |
| Tempat PKL | Mitra / Tempat Kegiatan |
| Peta Tempat PKL | Peta Mitra |
| Laporan Monitoring PKL | Laporan Monitoring Program |

## Catatan Batasan

- Istilah `PKL`, `KP`, dan `Kerja Praktik` masih dipertahankan pada konteks rule akademik, data historis, dan dokumen SRS yang menjelaskan aturan Kerja Praktik.
- Class CSS dan object JavaScript internal bernama `monpkl-*` belum diganti pada tahap ini agar risiko perubahan visual dan script tetap rendah. Ini dapat ditangani pada Tahap 3 saat standarisasi design system.
- Command import legacy masih menyebut Mon PKL karena fungsinya memang migrasi data lama.

## Checklist Tahap 2

- [x] Nama aplikasi utama diarahkan ke SiLAT.
- [x] Layout utama dan guest layout memakai identitas SiLAT.
- [x] Navigation desktop/mobile memakai istilah rebranding.
- [x] Label prioritas audit P0/P1 diperbarui.
- [x] Dokumentasi desain dan SRS menandai SiLAT sebagai brand target.
