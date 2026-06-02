# Redesain Layout dan Navigasi Tahap 4 - SiLAT

Dokumen ini mencatat implementasi Tahap 4 dari `ui-design.md`: redesain layout utama dan navigasi agar selaras dengan rancangan bagian 1 sampai 10.

## Implementasi Layout

- Layout aplikasi memakai sidebar desktop fixed di sisi kiri.
- Topbar fixed tersedia untuk konteks aplikasi, identitas pengguna, menu profil, dan tombol menu mobile.
- Konten utama diberi offset `lg:pl-64` dan `pt-16` agar tidak tertutup sidebar/topbar.
- Header halaman tetap memakai slot `$header`, tetapi kini berada di dalam shell konten utama.
- Mobile memakai drawer/off-canvas dari kiri dengan overlay dan tombol close.

## Struktur Navigasi

Navigasi dikelompokkan berdasarkan workflow:

| Grup | Isi Utama |
|------|-----------|
| Utama | Dashboard |
| Program Saya | Ringkasan, profil, pendaftaran, usulan tempat, pindah tempat, presensi |
| Koordinator | Dashboard koordinator, validasi pendaftaran, monitoring prodi, rekap prodi |
| Workflow Akademik | Ringkasan manajemen, program kegiatan, periode program, koordinator, validasi, peserta, usulan, relokasi |
| Master Data | User, mahasiswa, dosen, prodi, mitra |
| Monitoring | Peta mitra, peta monitoring, rekap monitoring, input lokasi |
| Konfigurasi | Konfigurasi sistem |

## Kesesuaian Rancangan Bagian 1-10

| Bagian Rancangan | Implementasi |
|------------------|--------------|
| 1. Prinsip Desain Umum | Warna utama sidebar memakai biru tua, aksen biru dipakai untuk active state, layout responsif desktop/mobile. |
| 2. Layout Dasar | Sidebar desktop, topbar, dan konten utama sudah mengikuti struktur dasar rancangan. |
| 3. Komponen Reusable | Navigasi memakai design system `silat-*`, `x-icon`, dropdown, dan pola focus/hover. |
| 4. Role Admin | Menu admin dikelompokkan ke workflow akademik, master data, monitoring, dan konfigurasi. |
| 5. Role Koordinator | Menu koordinator tersedia sesuai tanggung jawab periode/prodi. |
| 6. Role Dosen Pembimbing | Dosen mendapat akses monitoring dan input lokasi sesuai hak akses saat ini. |
| 7. Role Mahasiswa | Menu mahasiswa berpusat pada program saya, pendaftaran, presensi, dan laporan terkait. |
| 8. Pedoman Warna & Ikon Dashboard | Layout mempertahankan Font Awesome 6; dashboard sudah diselaraskan di Tahap 3. |
| 9. Alur Pengguna | Urutan menu mengikuti journey utama: dashboard, program/validasi, monitoring, laporan, konfigurasi. |
| 10. Implementasi Tailwind | Implementasi memakai Blade + Tailwind + Alpine.js untuk drawer mobile dan active state route. |

## Catatan

- `layouts/navigation.blade.php` sekarang menjadi sumber utama struktur menu berbasis role.
- `components/nav-link.blade.php` dan `responsive-nav-link.blade.php` masih dipertahankan untuk kompatibilitas halaman lama, tetapi layout utama tidak lagi bergantung pada horizontal navbar lama.
- Tahap berikutnya dapat memigrasikan halaman-halaman lama agar memakai `silat-shell`, `silat-card`, dan komponen reusable secara lebih merata.

## Checklist Tahap 4

- [x] Sidebar desktop tersedia.
- [x] Topbar tersedia.
- [x] Drawer mobile tersedia.
- [x] Menu dikelompokkan berdasarkan workflow.
- [x] Menu tampil sesuai role.
- [x] Active state berbasis route tersedia.
- [x] Font Awesome dipakai untuk ikon navigasi.
- [x] Layout konten tidak tertutup sidebar/topbar.
