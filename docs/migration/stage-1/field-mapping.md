# Mapping Field Firebase Tahap 1

Sumber utama: `ilkomunila-export (20220619-Periode Jan 2022).json`.
Sumber master kota: `ilkomunila-master-export.json`.

Catatan: `mon_pkl` ditetapkan sebagai sumber utama check-in. `mon_user/{npm}` hanya dipakai untuk validasi silang jumlah dan isi data.

Untuk desain periodik multi-prodi, data Firebase lama harus diperkaya saat import dengan metadata manual:

- `study_program_id`: prodi peserta PKL.
- `internship_period_id`: periode/semester PKL.
- `internship_enrollment_id`: relasi mahasiswa, prodi, periode, instansi, dosen, dan pembimbing.
- `legacy_source_file`: nama file export asal.
- `legacy_period_label`: label periode dari nama export atau keputusan migrasi.

Dengan model ini, `students` menyimpan identitas mahasiswa, sedangkan penempatan PKL per semester disimpan di `internship_enrollments`.

## pkl

Target tabel: `internship_places`.
Jumlah record: 54.

| Field Firebase | Tipe Terdeteksi | Target Awal | Catatan |
| --- | --- | --- | --- |
| `geometry.coordinates` | array | internship_places.longitude, internship_places.latitude | Validasi urutan koordinat GeoJSON [longitude, latitude] saat import. |
| `geometry.type` | string | internship_places.location type validation |  |
| `properties.alamat` | string | internship_places.address |  |
| `properties.hp_mhs` | string | internship_enrollments.contact_student_phone atau internship_places.contact_student_phone | Nomor ini berasal dari konteks penempatan; validasi apakah nomor milik mahasiswa atau instansi. |
| `properties.instansi` | string | internship_places.name |  |
| `properties.kota` | string | internship_places.city_id/name |  |
| `properties.mhs` | array | students + internship_enrollments | Bentuk enrollment per mahasiswa pada periode/prodi yang ditetapkan untuk export. |
| `properties.pemb_hp` | string | internship_places.field_supervisor_phone |  |
| `properties.pemb_lap` | string | internship_enrollments.field_supervisor atau internship_places.field_supervisor_name | Pembimbing lapangan bisa berbeda per penempatan/periode. |
| `properties.time` | number | internship_places.legacy_created_at |  |
| `properties.visited` | number | internship_places.visited |  |
| `type` | string | legacy_geojson_type |  |

## mon_pkl

Target tabel: `check_ins`.
Jumlah record: 3524.

| Field Firebase | Tipe Terdeteksi | Target Awal | Catatan |
| --- | --- | --- | --- |
| `geometry.coordinates` | array | check_ins.office/student coordinates | Validasi urutan koordinat GeoJSON [longitude, latitude] saat import. Relasikan ke enrollment sesuai NPM, periode, prodi, dan instansi. |
| `geometry.type` | string | check_ins.legacy_geometry_type |  |
| `properties.catatan` | string | check_ins.note |  |
| `properties.device.appVersion` | string | check_ins.device_info.appVersion |  |
| `properties.device.browserName` | string | check_ins.device_info.browserName |  |
| `properties.device.browserVersion` | number | check_ins.device_info.browserVersion |  |
| `properties.device.osName` | string | check_ins.device_info.osName |  |
| `properties.device.osVersion` | number | check_ins.device_info.osVersion |  |
| `properties.device.platform` | string | check_ins.device_info.platform |  |
| `properties.device.userAgent` | string | check_ins.device_info.userAgent |  |
| `properties.device.vendor` | string | check_ins.device_info.vendor |  |
| `properties.imgURL` | string | check_ins.photo_path/source_photo_url | Foto lama masih berupa URL Firebase Storage; migrasi file dilakukan terpisah. |
| `properties.instansi` | string | internship_places.name + internship_enrollments.internship_place_id | Cocokkan dengan penempatan pada periode/prodi aktif. |
| `properties.keterangan` | string | check_ins.type |  |
| `properties.nama` | string | students.full_name snapshot |  |
| `properties.npm` | string | students.npm + internship_enrollments.student_id | NPM dipakai untuk mencari enrollment pada periode/prodi export. |
| `properties.time` | number | check_ins.checked_at |  |
| `properties.url` | string | check_ins.source_url |  |
| `properties.user.displayName` | string | users.name snapshot |  |
| `properties.user.email` | string | users.email snapshot |  |
| `properties.user.photoURL` | string | users.avatar_url snapshot |  |
| `properties.user.uid` | string | users.google_id/firebase_uid_legacy |  |
| `type` | string | legacy_geojson_type |  |

## mon_user

Target tabel: tidak dimigrasikan langsung.
Jumlah record: 3521.
Jumlah NPM pada `mon_user`: 114.

| Field Firebase | Tipe Terdeteksi | Target Awal | Catatan |
| --- | --- | --- | --- |
| `geometry.coordinates` | array | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `geometry.type` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `npm` | string | students.npm legacy path key | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.catatan` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.device.appVersion` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.device.browserName` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.device.browserVersion` | number | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.device.osName` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.device.osVersion` | number | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.device.platform` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.device.userAgent` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.device.vendor` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.imgURL` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.instansi` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.keterangan` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.nama` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.npm` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.time` | number | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.url` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.user.displayName` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.user.email` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.user.photoURL` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `properties.user.uid` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |
| `type` | string | perlu konfirmasi saat import | Gunakan sebagai pembanding terhadap data mon_pkl, bukan sumber utama. |

## users

Target tabel: `users, students`.
Jumlah record: 115.

| Field Firebase | Tipe Terdeteksi | Target Awal | Catatan |
| --- | --- | --- | --- |
| `dosen` | string | internship_enrollments.lecturer_supervisor | Dosen pembimbing adalah atribut penempatan/periode, bukan profil permanen mahasiswa. |
| `email` | string | users.email |  |
| `instansi.lat` | string | internship_places.latitude fallback | Validasi numeric dan range koordinat saat import; ikat ke enrollment periode/prodi. |
| `instansi.lng` | string | internship_places.longitude fallback | Validasi numeric dan range koordinat saat import; ikat ke enrollment periode/prodi. |
| `instansi.nama` | string | internship_places.name + internship_enrollments.internship_place_id |  |
| `nama` | string | users.name/students.full_name |  |
| `npm` | string | students.npm |  |
| `pembimbing` | string | internship_enrollments.field_supervisor | Pembimbing lapangan adalah atribut penempatan/periode. |
| `photoURL` | string | users.avatar_url |  |

## master/kota

Target tabel: `cities`.
Jumlah record: 27.

| Field Firebase | Tipe Terdeteksi | Target Awal | Catatan |
| --- | --- | --- | --- |
| `legacy_index` | number | cities.legacy_index |  |
| `name` | string | cities.name |  |
