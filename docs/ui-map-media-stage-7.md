# Integrasi Peta dan Media Tahap 7 - SiLAT

Dokumen ini mencatat implementasi Tahap 7 dari `ui-design.md`: integrasi peta dan media, dengan fokus utama pada halaman `maps/monitoring`.

## Implementasi Utama

| Area | Implementasi |
|------|--------------|
| Default periode aktif | `maps/monitoring` otomatis memilih periode aktif saat `period_id` belum dipilih. |
| Pilihan periode lain | Dropdown periode tetap menampilkan seluruh periode yang tersedia sesuai scope user. |
| Filter tanggal | Ditambahkan filter `Dari`, `Sampai`, dan checkbox `Hari Ini`. |
| Role mahasiswa | Mahasiswa tetap mendapatkan filter periode dan tanggal, dibatasi pada enrollment miliknya. |
| Data marker | Panel tabel `Data Marker` dirender dari data JSON yang sama dengan marker peta. |
| Interaksi tabel-peta | Klik marker menyorot baris tabel; klik baris tabel mengarahkan peta ke marker. |
| Scope role | Admin melihat semua, dosen/koordinator sesuai supervisi atau scope koordinasi, mahasiswa sesuai enrollment sendiri. |

## Perubahan Teknis

- `MapController` menyiapkan `selectedPeriod`, `startDate`, `endDate`, dan `todayOnly`.
- Endpoint `maps.monitoring.data` memfilter `checked_at` berdasarkan rentang tanggal.
- `maps.partials.filters` kini mendukung filter periode/prodi/tanggal dengan opsi `showDateFilters`.
- `maps.monitoring` menampilkan layout peta dan tabel marker berdampingan.
- `resources/js/maps/leafletMaps.js` menambahkan renderer tabel monitoring dan sinkronisasi marker-tabel.

## Kesesuaian Rancangan Bagian 1-10

- Memakai layout sidebar/topbar dari Tahap 4.
- Memakai komponen `silat-card`, `silat-table`, `x-badge`, `x-select-input`, `x-text-input`, dan `x-icon`.
- Peta Leaflet tetap menjadi fokus utama monitoring.
- Tabel tetap responsif dengan scroll horizontal/vertikal.
- State kosong tersedia saat filter tidak menghasilkan check-in.
- Filter tetap tersedia untuk mahasiswa, tetapi data dibatasi oleh scope akun.

## Checklist Tahap 7

- [x] Peta monitoring default ke periode aktif.
- [x] Dropdown periode lain tetap tersedia.
- [x] Filter range tanggal tersedia.
- [x] Checkbox `Hari Ini` tersedia.
- [x] Tabel data marker tersedia.
- [x] Tabel dan marker tersinkronisasi.
- [x] Mahasiswa memiliki opsi filter.
- [x] Dokumentasi implementasi tahap 7 dibuat.
