# Redesain Dashboard Tahap 5 - SiLAT

Dokumen ini mencatat implementasi Tahap 5 dari `ui-design.md`: redesain dashboard per role agar sesuai rancangan bagian 1 sampai 10.

## Scope Implementasi

| Role | Halaman | Fokus Desain |
|------|---------|--------------|
| Admin | `/management` dan ringkasan admin di `/dashboard` | Statistik utama, pendaftaran pending, periode aktif, master data. |
| Koordinator | `/coordinator` dan ringkasan di `/dashboard` | Statistik scope koordinasi, peserta terbaru, sanksi tertinggi, penugasan periode/prodi. |
| Dosen Pembimbing | `/dashboard` | Mahasiswa bimbingan, kebutuhan bimbingan/revisi, akses peta dan rekap. |
| Mahasiswa | `/student` dan ringkasan mahasiswa di `/dashboard` | Hero presensi, status program, hari hadir, sanksi, deadline, progres laporan, izin perangkat. |

## Kesesuaian Rancangan Bagian 1-10

| Bagian | Implementasi |
|--------|--------------|
| 1. Prinsip Desain Umum | Dashboard memakai layout responsif, warna brand/status, kartu, tabel, kontras, dan tipografi dari design system. |
| 2. Layout Dasar | Semua dashboard berjalan di shell sidebar/topbar Tahap 4 dengan konten utama berstruktur. |
| 3. Komponen Reusable | Memakai `silat-card`, `silat-stat-card`, `silat-table`, `x-badge`, `x-alert`, `x-empty-state`, dan `x-icon`. |
| 4. Role Admin | Admin menampilkan statistik, pendaftaran perlu validasi, periode aktif, shortcut master data. |
| 5. Role Koordinator | Koordinator menampilkan statistik scope, peserta terbaru, sanksi tertinggi, dan tabel penugasan. |
| 6. Role Dosen | Dosen menampilkan kartu bimbingan, perlu bimbingan, seminar terdekat, dan daftar mahasiswa bimbingan. |
| 7. Role Mahasiswa | Mahasiswa menampilkan hero check-in, ringkasan status, progres laporan, izin perangkat, dan riwayat pendaftaran. |
| 8. Warna & Ikon | Kartu dashboard mengikuti pedoman warna dan ikon yang sudah dikunci di Tahap 3. |
| 9. User Journey | Dashboard mengarahkan pengguna ke aksi berikutnya: validasi, monitoring, pendaftaran, presensi, laporan, dan rekap. |
| 10. Tailwind | Implementasi memakai Blade + Tailwind + komponen reusable; tabel tetap responsif dengan horizontal scroll. |

## Catatan Data

- Statistik kehadiran memakai jumlah peserta aktif dibanding check-in unik hari ini.
- Progres laporan mahasiswa sementara berbasis keberadaan `final_report_path`.
- Seminar terdekat dosen masih placeholder `0` karena modul seminar belum tersedia.
- Sanksi koordinator memakai `total_sanctions_points` pada enrollment.

## Checklist Tahap 5

- [x] Dashboard admin dirapikan.
- [x] Dashboard koordinator dirapikan.
- [x] Dashboard dosen pembimbing dirapikan.
- [x] Dashboard mahasiswa dirapikan.
- [x] Empty state dan shortcut aksi tersedia.
- [x] Kartu warna/ikon mengikuti pedoman bagian 8.
- [x] Dokumentasi implementasi tahap 5 dibuat.
