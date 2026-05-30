# Runbook Freeze Data Firebase

Tanggal snapshot lokal: 2026-05-30T13:35:06.155Z

## Keputusan Sumber Data

- Sumber utama migrasi: `ilkomunila-export (20220619-Periode Jan 2022).json`
- Sumber master kota: `ilkomunila-master-export.json`
- Alasan: Export produksi historis paling baru yang berisi path utama pkl, mon_pkl, mon_user, dan users. Export latihan 2025 dicatat sebagai kandidat terpisah, tetapi tidak dipilih sebagai sumber utama karena namanya menunjukkan data latihan.

## Prosedur Freeze Final

1. Umumkan waktu freeze kepada admin, dosen, dan mahasiswa.
2. Nonaktifkan sementara fitur tulis pada aplikasi lama: input tempat PKL, check-in, update profil, dan upload foto.
3. Tetapkan metadata periode/prodi untuk data yang akan diexport:
   - nama periode PKL.
   - tahun akademik.
   - semester atau gelombang.
   - prodi.
   - status data: produksi, latihan, arsip, atau delta.
4. Export Firebase Realtime Database dari console Firebase setelah freeze aktif.
5. Simpan file export final dengan pola nama `ilkomunila-export (YYYYMMDD-final).json`.
6. Jalankan:

```powershell
node tools/firebase-stage1-inventory.mjs --source "ilkomunila-export (YYYYMMDD-final).json" --backup-dir "firebase-freeze/2026-05-30"
```

7. Cocokkan SHA-256 di `backup-manifest.json` dengan file backup.
8. Jika data tidak bisa dibekukan total, lakukan export delta sejak waktu freeze dan catat nama file delta di dokumen ini.

## Matriks Periode dan Prodi

Data Firebase lama belum punya field periode/prodi yang eksplisit. Matriks ini harus dikunci bersama admin sebelum Tahap 3 agar import tidak mencampur peserta PKL antar semester atau antar prodi.

| File Export | Periode PKL | Tahun Akademik | Semester/Gelombang | Prodi | Status | Catatan |
| --- | --- | --- | --- | --- | --- | --- |
| `ilkomunila-export (20220619-Periode Jan 2022).json` | Periode Jan 2022 | perlu konfirmasi | perlu konfirmasi | Ilmu Komputer/default lama | produksi | Sumber utama saat ini |
| `ilkomunila-export (20211231-PKL Periode 2 2021).json` | Periode 2 2021 | perlu konfirmasi | perlu konfirmasi | perlu konfirmasi | latihan | Jangan digabung ke data produksi tanpa persetujuan |

Hasil matriks ini akan dibuat menjadi:

- `study_programs`.
- `internship_periods`.
- `internship_enrollments`.
- metadata audit seperti `legacy_source_file` dan `legacy_period_label`.

## Snapshot Lokal Saat Ini

Folder backup: `firebase-freeze/2026-05-30`

File manifest:

- `docs/migration/stage-1/backup-manifest.json`
- `docs/migration/stage-1/inventory-summary.json`

## Mekanisme Delta

Jika masih ada transaksi setelah export utama, ambil export baru dan jadikan:

- export utama lama sebagai baseline.
- export baru sebagai final.
- selisih `mon_pkl`, `pkl`, `users`, dan `master/kota` dipakai sebagai delta validasi sebelum import Tahap 3.
- setiap delta harus memakai periode dan prodi yang sama dengan baseline, kecuali ada dokumen keputusan admin yang menyatakan sebaliknya.
