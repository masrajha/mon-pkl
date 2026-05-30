# Tahap 1 - Inventarisasi dan Freeze Data

Artefak ini dihasilkan oleh `tools/firebase-stage1-inventory.mjs`.

## Output

- `field-mapping.md`: mapping field Firebase ke target tabel awal.
- `inventory-summary.json`: ringkasan struktur path dan jumlah record.
- `backup-manifest.json`: daftar backup JSON export beserta SHA-256.
- `feature-inventory.md`: daftar fitur aktif dan fitur lama/dev yang boleh dihentikan.
- `freeze-runbook.md`: langkah freeze final dan opsi delta export.
- Matriks metadata periode/prodi: acuan manual untuk mengikat export lama ke `internship_periods`, `study_programs`, dan `internship_enrollments`.

## Ringkasan Data

| Path | Jumlah |
| --- | ---: |
| `pkl` | 54 |
| `mon_pkl` | 3524 |
| `mon_user/{npm}` | 114 NPM / 3521 check-in |
| `users` | 115 |
| `master/kota` | 27 |

Backup lokal saat ini berada di `firebase-freeze/2026-05-30`.

## Sumber Utama

`ilkomunila-export (20220619-Periode Jan 2022).json`

Export produksi historis paling baru yang berisi path utama pkl, mon_pkl, mon_user, dan users. Export latihan 2025 dicatat sebagai kandidat terpisah, tetapi tidak dipilih sebagai sumber utama karena namanya menunjukkan data latihan.

## Metadata Periode dan Prodi

Data Firebase lama belum menyimpan periode PKL dan prodi sebagai field eksplisit. Karena sistem baru akan dipakai periodik tiap semester dan dapat mencakup beberapa prodi, setiap export harus diberi metadata sebelum import.

Matriks awal:

| Export | Periode PKL | Tahun Akademik | Semester/Gelombang | Prodi | Status |
| --- | --- | --- | --- | --- | --- |
| `ilkomunila-export (20220619-Periode Jan 2022).json` | Periode Jan 2022 | perlu konfirmasi | perlu konfirmasi | Ilmu Komputer/default lama | sumber utama |
| `ilkomunila-export-latihan-periode1-2025.json` | Periode 1 2025 | perlu konfirmasi | perlu konfirmasi | perlu konfirmasi | latihan, bukan sumber utama |

Saat import, metadata ini menjadi:

- `study_programs`: daftar prodi.
- `internship_periods`: daftar periode/semester PKL.
- `internship_enrollments`: relasi mahasiswa, prodi, periode, instansi, dosen, dan pembimbing.
- `check_ins.internship_enrollment_id`: pengikat semua data check-in ke peserta PKL pada periode dan prodi tertentu.

## Fitur Aktif Terdeteksi

Jumlah halaman HTML yang terdeteksi: 23.
Detail ada di `feature-inventory.md`.
