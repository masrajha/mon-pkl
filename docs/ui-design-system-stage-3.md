# Standarisasi Design System Tahap 3 - SiLAT

Dokumen ini mencatat implementasi Tahap 3 dari `ui-design.md`: standarisasi design system untuk aplikasi **SiLAT**.

## Ruang Lingkup

- Token visual dasar dipusatkan di `backend/resources/css/app.css`.
- Namespace UI baru memakai prefix `silat-*`.
- Class lama `monpkl-*` tetap diberi alias agar halaman existing tidak rusak saat migrasi bertahap.
- Komponen Blade Breeze yang sudah dipakai aplikasi diarahkan ke style SiLAT.
- Komponen reusable baru ditambahkan untuk kebutuhan form, badge, alert, card, tabel, tabs, ikon, dan empty state.

## Token Visual

| Token | Nilai Utama |
|-------|-------------|
| Brand utama | `#1E3A8A` |
| Aksen primer | `#2563EB` / `#3B82F6` |
| Latar aplikasi | `#F9FAFB` |
| Teks utama | `#101828` |
| Teks sekunder | `#667085` |
| Garis/border | `#E4E7EC` |
| Radius standar | `0.5rem` |
| Shadow standar | `--silat-shadow` |
| Status sukses | Hijau |
| Status peringatan | Amber |
| Status bahaya | Merah |

## Komponen CSS Utama

| Class | Fungsi |
|-------|--------|
| `silat-shell` | Container halaman konsisten. |
| `silat-institution-bar` | Bar navigasi utama. |
| `silat-page-header` | Header halaman. |
| `silat-card` / `silat-section` | Panel konten dan card. |
| `silat-section-header` | Header panel. |
| `silat-stat-grid` / `silat-stat-card` | Grid dan card statistik. |
| `silat-btn` | Tombol primer. |
| `silat-btn-secondary` | Tombol sekunder. |
| `silat-btn-danger` | Tombol aksi bahaya. |
| `silat-field` | Input, select, dan textarea. |
| `silat-label` | Label form. |
| `silat-alert-*` | Alert info/sukses/peringatan/bahaya. |
| `silat-badge-*` | Badge info/sukses/peringatan/bahaya/netral. |
| `silat-table` | Tabel standar. |
| `silat-empty-state` | Empty state. |
| `silat-tab` / `silat-tab-active` | Navigasi tab. |

## Komponen Blade

Komponen existing yang dirapikan:

- `x-primary-button`
- `x-secondary-button`
- `x-danger-button`
- `x-text-input`
- `x-input-label`
- `x-input-error`
- `x-modal`

Komponen baru:

- `x-select-input`
- `x-textarea-input`
- `x-alert`
- `x-badge`
- `x-card`
- `x-empty-state`
- `x-icon`
- `x-table`
- `x-tabs`

## Ikon

- Layout utama memuat Font Awesome 6 via CDN.
- Komponen `x-icon` menjadi wrapper standar, contoh:

```blade
<x-icon name="fa-calendar-check" class="text-blue-600" />
```

## Kesesuaian Pedoman Dashboard Bagian 8

Dashboard sudah diselaraskan dengan pedoman warna dan ikon pada bagian 8 `ui-design.md`.

| Role | Implementasi |
|------|--------------|
| Admin | Kartu `Total Mahasiswa MBKM/KP` memakai `bg-blue-600` + `fa-users`, `Total Dosen Pembimbing` memakai `bg-green-600` + `fa-chalkboard-user`, `Total Mitra` memakai `bg-yellow-500` + `fa-building`, `Rata-rata Kehadiran` memakai `bg-purple-600` + `fa-calendar-check`, dan `Pendaftaran Pending` memakai border merah + `fa-hourglass-half`. |
| Koordinator | Kartu `Mahasiswa Terdaftar`, `Check-in Hari Ini`, `Laporan Selesai`, dan `Total Sanksi` memakai kombinasi warna/ikon `bg-cyan-700/fa-user-graduate`, `bg-emerald-500/fa-fingerprint`, `bg-amber-500/fa-file-alt`, dan `bg-rose-600/fa-gavel`. |
| Dosen | Kartu `Mahasiswa Bimbingan`, `Perlu Bimbingan`, dan `Seminar Terdekat` memakai `bg-blue-600/fa-chalkboard-user`, `bg-orange-500/fa-pen-ruler`, dan `bg-indigo-500/fa-calendar-day`. |
| Mahasiswa | Hero check-in memakai gradien hijau + `fa-location-dot`; ringkasan `Hari Hadir`, `Total Sanksi`, dan `Deadline Terdekat` memakai `bg-sky-100/fa-calendar-check`, `bg-rose-100/fa-exclamation-triangle`, dan `bg-amber-100/fa-hourglass-start`. |

## Catatan Migrasi

- Halaman lama masih dapat memakai `monpkl-*` karena alias tetap tersedia.
- Pengembangan UI baru sebaiknya memakai `silat-*` dan komponen Blade baru.
- Migrasi halaman existing dapat dilakukan bertahap mulai Tahap 4 sampai Tahap 9 tanpa mengubah logika bisnis.

## Checklist Tahap 3

- [x] Token visual dasar ditambahkan.
- [x] Namespace design system `silat-*` tersedia.
- [x] Alias `monpkl-*` dijaga untuk kompatibilitas.
- [x] Komponen tombol/input/modal existing diarahkan ke style baru.
- [x] Komponen reusable baru dibuat.
- [x] Font Awesome 6 disiapkan untuk standar ikon.
- [x] Dashboard admin, koordinator, dosen, dan mahasiswa mengikuti pedoman warna & ikon bagian 8.
- [x] Dokumentasi implementasi tahap 3 dibuat.
