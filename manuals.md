# Manual Penggunaan SiLAT

**Versi dokumen:** 2.4
**Tanggal pembaruan:** 11 Juni 2026
**Status:** Mengikuti implementasi fitur sampai snapshot GPS presensi, Lupa Presensi, foto profil, rubrik penilaian per periode, dashboard analisis, Viewer Laporan berbasis organisasi, Finalisasi Nilai, Berita Acara Nilai, portal Pembimbing Lapangan, organisasi akademik, subnav laporan, dan pembatasan hapus master data terbaru.

SiLAT (Sistem Laporan Aktivitas Terpadu MBKM & Kerja Praktik) adalah sistem untuk mengelola pendaftaran program, presensi, pembekalan, laporan, catatan harian, perpindahan mitra, perubahan pembimbing, dan monitoring aktivitas mahasiswa.

> **What's New - Versi 2.4**
>
> - Tersedia struktur **Organisasi** untuk scope laporan: Universitas Lampung → FMIPA → Jurusan Ilmu Komputer, dengan semua prodi saat ini ditautkan ke Jurusan Ilmu Komputer.
> - Admin dapat menugaskan dosen aktif sebagai **Viewer Laporan** pada level universitas, fakultas, jurusan, atau prodi tanpa mengubah role utama dosen.
> - Menu **Analisis & Laporan** kini dapat diakses admin, koordinator, dan Viewer Laporan sesuai scope; Viewer Laporan bersifat baca dan tidak memiliki aksi workflow operasional.
> - Halaman laporan memakai tab subnav yang konsisten, posisi scroll sidebar disimpan, dan filter tanggal beberapa laporan otomatis mengikuti rentang presensi periode.
> - Progress Funnel hanya menghitung peserta **Aktif** dan **Selesai**, tampil dua kolom, dan memakai urutan terbaru: pendaftaran → presensi → laporan lengkap → nilai Pembimbing Lapangan → seminar → nilai dosen → nilai final.
> - Prodi, organisasi, dan Viewer Laporan memiliki aksi hapus dengan pembatasan aman. Prodi/organisasi tidak dapat dihapus jika masih dipakai data lain.
> - Berita Acara Nilai memakai QR QuickChart dengan logo dokumen jika tersedia.
> - Periode Program memakai keunikan berdasarkan program. Nama periode, tahun akademik, semester, dan gelombang yang sama boleh dipakai pada program berbeda, tetapi tidak boleh duplikat pada program yang sama.
> - Summary email progres laporan untuk admin, koordinator, dan dosen pembimbing hanya dikirim setelah tanggal **Mulai Pelaksanaan / Presensi** periode tercapai.
> - Presensi harian memakai **snapshot GPS server-side** sehingga koordinat final tidak bergantung pada field form yang dapat diedit dari browser.
> - Sistem menolak presensi jika koordinat form berbeda jauh dari snapshot GPS atau jika snapshot GPS berada di luar radius mitra.
> - Check-out tanpa check-in tetap dapat disimpan sebagai presensi belum berpasangan dan diarahkan untuk pengajuan **Lupa Presensi Masuk** jika kuota tersedia.
> - Halaman Presensi mahasiswa kini memakai tab **Presensi** dan **Lupa Presensi**.
> - Lupa Presensi memiliki kuota, sisa kuota, lokasi, foto realtime, alasan, dan riwayat pengajuan.
> - Foto profil pengguna dapat diunggah dari Profil Saya atau dikelola admin pada Manajemen User.
> - Master Mahasiswa dan Dosen mendukung pembuatan/penautan akun login otomatis dari email sehingga input data tidak perlu dilakukan dua kali.
> - Konfigurasi Program menyediakan batas akurasi GPS, umur snapshot lokasi, toleransi beda koordinat, tanggal libur, batas Lupa Presensi, rubrik penilaian, survey institusi, dan dokumen cetak nilai.
> - Menu **Analisis & Laporan** memuat dashboard progres, funnel, risk scoring, heatmap kehadiran, Grafik Operasional, Rekap Sanksi, dan Rekap Nilai Akhir.

Dokumen ini dibagi menjadi lima bagian utama berdasarkan role pengguna, ditambah catatan khusus untuk Viewer Laporan:

1. Role Mahasiswa
2. Role Dosen Pembimbing
3. Role Koordinator
4. Role Admin
5. Role Pembimbing Lapangan

Viewer Laporan adalah akses tambahan untuk dosen yang hanya perlu membaca Analisis & Laporan sesuai scope organisasi/prodi, tanpa memproses workflow.

Dokumentasi publik pada `/docs` membaca isi file ini, sehingga perubahan manual di sini otomatis menjadi sumber halaman dokumentasi aplikasi.

---

## Bagian 1. Role Mahasiswa

### 1.1 Ringkasan Hak Akses Mahasiswa

Mahasiswa dapat menggunakan SiLAT untuk:

- Melengkapi profil mahasiswa.
- Melihat ringkasan program pada dashboard.
- Mendaftar program/periode yang aktif.
- Memilih mitra/tempat kegiatan atau mengajukan mitra baru.
- Mengunggah dokumen bukti akademik saat pendaftaran.
- Melakukan presensi pembekalan.
- Melakukan presensi kegiatan harian, yaitu masuk dan pulang.
- Mengajukan **Lupa Presensi** jika lupa melakukan presensi realtime, sesuai kuota periode.
- Mengajukan pindah tempat.
- Mengajukan perubahan data pembimbing lapangan.
- Mengunggah progres laporan.
- Melihat rekap presensi, catatan harian, sanksi, dan status laporan.
- Mencetak laporan presensi, form catatan harian, dan berita acara nilai jika nilai sudah final.

### 1.2 Login dan Dashboard

Mahasiswa masuk ke sistem menggunakan akun yang sudah tersedia. Setelah login, mahasiswa akan diarahkan ke dashboard sesuai role.

Dashboard mahasiswa menampilkan:

- Status profil mahasiswa.
- Status pendaftaran efektif berdasarkan kombinasi status peserta dan status periode.
- Panel **Program Saya** yang berisi kartu untuk semua program aktif yang sedang diikuti.
- Tombol **Detail** sebagai pintu masuk utama workflow program.
- Tombol **Presensi** untuk aksi presensi harian jika program aktif secara operasional.
- Link presensi pembekalan jika ada event pembekalan yang sesuai.
- Deadline penting dalam 7 hari ke depan; jika tidak ada, sistem menampilkan deadline terdekat berikutnya.
- Ringkasan usulan mitra dan akses monitoring sesuai hak role.

Jika mahasiswa belum melengkapi profil atau belum memiliki program aktif, beberapa fitur seperti pendaftaran, presensi, dan laporan dapat belum tersedia.

Pada dashboard, program dianggap aktif secara operasional jika status peserta adalah **Active** dan periode program masih aktif. Jika peserta masih **Active** tetapi periode program sudah nonaktif, status akan ditampilkan sebagai **Periode Nonaktif** dan program tidak diperlakukan sebagai program aktif untuk aksi harian seperti presensi.

### 1.3 Profil Saya

Menu: **Profil Saya**

Mahasiswa wajib melengkapi profil sebelum melakukan pendaftaran program.

Data yang diisi:

- NPM.
- Nama lengkap.
- Email mahasiswa.
- Nomor HP.
- Program studi.
- Foto profil, jika ingin menampilkan avatar personal pada navigasi dan dokumen tertentu.

Program studi menentukan jenjang mahasiswa, misalnya D3 atau S1. Jenjang ini dipakai sistem untuk membaca syarat akademik yang sesuai saat pendaftaran program.

Foto profil memakai file gambar JPG, PNG, atau WebP dengan batas ukuran yang ditentukan sistem. Jika foto profil dihapus, sistem kembali memakai inisial nama pengguna.

### 1.4 Pendaftaran Program

Menu: **Pendaftaran Program**

Pendaftaran program dilakukan melalui tiga halaman.

#### Halaman 1. Program dan Periode

Mahasiswa memilih:

- Program kegiatan.
- Periode program.
- Program studi, mengikuti data profil mahasiswa dan tidak dipilih manual.

Setelah memilih program dan periode, klik **Lanjutkan**.

Periode hanya dapat dipilih jika masih tersedia untuk pendaftaran. Jika periode sudah nonaktif, terkunci, atau berada di luar rentang pendaftaran, mahasiswa tidak dapat membuat pendaftaran baru pada periode tersebut.

#### Halaman 2. Mitra dan Pembimbing Lapangan

Mahasiswa memilih mitra/tempat kegiatan.

Pilihan yang tersedia:

- Memilih mitra aktif yang sudah ada.
- Mengajukan mitra baru jika tempat belum tersedia.

Data pembimbing lapangan yang dapat diisi:

- Nama pembimbing lapangan.
- HP pembimbing lapangan.
- Email pembimbing lapangan.
- HP kontak mahasiswa selama periode ini.

Email pembimbing lapangan bersifat opsional saat pendaftaran awal. Email ini wajib dilengkapi sebelum mahasiswa mengajukan seminar dan dapat digunakan admin/koordinator untuk mengirim akses portal Pembimbing Lapangan.

Jika data sudah benar, klik **Lanjutkan**. Jika perlu memperbaiki pilihan program/periode, klik **Kembali**.

#### Halaman 3. Rule Program, Kelayakan Akademik, dan Dokumen Bukti

Mahasiswa mengisi data kelayakan akademik:

- Pernyataan sudah mengambil Kerja Praktik atau program terkait di KRS.
- Total SKS.
- Semester saat ini.
- IPK.

Mahasiswa juga wajib mengunggah satu file PDF berisi:

**Transkrip Sementara + KRS Semester saat ini.**

Batasan dokumen pendaftaran:

- Format file: PDF.
- Maksimal ukuran file: 5 MB.
- Pada revisi pendaftaran, file lama tetap dipakai jika mahasiswa tidak mengunggah file baru.

Klik **Kirim Pendaftaran** untuk mengirim data ke admin/koordinator.

### 1.5 Cara Kerja Validasi Kelayakan Akademik

Sistem memeriksa kelayakan berdasarkan rule program dan jenjang program studi mahasiswa.

Aturan default saat ini:

| Jenjang | Minimal SKS | Minimal Semester | Minimal IPK |
|---------|-------------|------------------|-------------|
| D3 | 80 | 4 | 2,00 |
| S1 | 100 | 6 | 2,00 |

Catatan:

- Nilai di atas dapat berubah melalui **Konfigurasi Program** oleh admin.
- Program studi mahasiswa menentukan jenjang yang dipakai.
- Jika total SKS, semester, atau IPK belum memenuhi syarat, sistem menolak pendaftaran dan menampilkan pesan validasi.
- Mahasiswa juga harus mencentang bahwa program/KP sudah diambil pada KRS semester berjalan.

### 1.6 Status Pendaftaran

Setelah dikirim, pendaftaran menunggu verifikasi admin/koordinator.

Status dasar enrollment yang dapat muncul:

| Status | Arti |
|--------|------|
| Draft | Pendaftaran masih tersimpan sebagai draft dan belum dikirim. |
| Pending Verification | Pendaftaran sudah dikirim dan menunggu validasi. |
| Revision Required | Admin/koordinator meminta perbaikan data. |
| Active | Pendaftaran disetujui dan mahasiswa dapat menjalankan program. |
| Rejected | Pendaftaran ditolak. |
| Cancelled | Pendaftaran dibatalkan. |
| Completed | Program selesai. |

Jika status **Revision Required**, mahasiswa dapat membuka kembali form pendaftaran, memperbaiki data, lalu mengirim ulang.

Dashboard mahasiswa memakai status efektif, yaitu gabungan status enrollment dan status periode program:

| Kondisi | Tampilan di dashboard |
|---------|-----------------------|
| Enrollment `active` dan periode aktif | Aktif |
| Enrollment `active` tetapi periode nonaktif | Periode Nonaktif |
| Enrollment draft/pending/revisi pada periode nonaktif | Periode Tidak Tersedia |
| Enrollment completed | Selesai |
| Enrollment rejected | Ditolak |
| Enrollment cancelled | Dibatalkan |
| Belum punya enrollment | Belum Mendaftar |

Ringkasan status hanya menampilkan kategori yang memiliki jumlah data. Contoh: jika mahasiswa memiliki 1 peserta aktif dan 1 peserta pada periode nonaktif, dashboard menampilkan **Aktif 1** dan chip **Periode Nonaktif 1**, bukan tujuh baris status kosong.

### 1.7 Usulan Mitra Baru

Menu: **Usulan Mitra**

Mahasiswa dapat mengajukan mitra baru jika tempat kegiatan belum tersedia pada daftar mitra, layanan usulan mitra sedang dibuka, dan periode terkait tidak terkunci.

Data yang diisi:

- Periode program.
- Nama instansi/mitra.
- Alamat.
- Kota.
- Latitude dan longitude.
- Nama pembimbing lapangan, jika sudah ada.
- HP pembimbing lapangan, jika sudah ada.

Sistem menyediakan sugest lokasi:

- Pertama dari riwayat internal sistem.
- Jika tidak ditemukan, sistem dapat mengambil sugest eksternal.

Mahasiswa dapat memilih lokasi dari peta agar koordinat lebih akurat. Usulan akan berstatus menunggu validasi sampai diproses admin/koordinator.

### 1.8 Presensi Pembekalan

Presensi pembekalan muncul di dashboard mahasiswa jika admin/koordinator sudah membuka event pembekalan untuk periode/program yang sesuai.

Cara melakukan presensi pembekalan:

1. Buka dashboard mahasiswa.
2. Klik **Presensi Pembekalan** pada event yang tersedia.
3. Izinkan browser mengakses lokasi GPS dan kamera.
4. Pastikan lokasi terbaca.
5. Ambil foto realtime dari kamera.
6. Klik **Simpan Presensi**.

Batasan presensi pembekalan:

- Presensi hanya dapat dilakukan satu kali untuk satu event pembekalan.
- Lokasi pembekalan ditentukan admin/koordinator.
- Mahasiswa tidak dapat mengubah marker lokasi pembekalan.
- Field koordinat lokasi pembekalan bersifat readonly.
- Sistem menghitung jarak mahasiswa dari lokasi pembekalan.
- Jika radius maksimum diatur dan mahasiswa berada di luar radius, presensi ditolak.
- Foto wajib diambil langsung dari kamera.

### 1.9 Presensi Kegiatan Harian

Menu: **Presensi**

Presensi kegiatan harian hanya dapat dilakukan jika mahasiswa memiliki enrollment dengan status **Active**.

Halaman Presensi memiliki dua tab:

| Tab | Isi |
|-----|-----|
| Presensi | Peta lokasi, form presensi masuk/pulang, kamera realtime, dan tabel **Check-in Terakhir**. |
| Lupa Presensi | Form pengajuan Lupa Presensi, info periode/mitra, koordinat lokasi pengajuan, kuota maksimal, kuota terpakai, sisa kuota, kamera realtime, dan tabel **Riwayat Pengajuan Lupa Presensi**. |

Setiap hari, mahasiswa melakukan dua presensi:

- **Masuk**: dilakukan saat mulai kegiatan.
- **Pulang**: dilakukan saat selesai kegiatan.

Data yang dikirim saat presensi:

- Aksi presensi, yaitu masuk atau pulang.
- Snapshot GPS mahasiswa yang tersimpan di server.
- Koordinat form sebagai tampilan peta dan pembanding audit.
- Foto realtime dari kamera.
- Catatan aktivitas.
- Device info dan IP yang dicatat otomatis oleh server.

Catatan aktivitas berbeda sesuai aksi:

- Saat presensi masuk, catatan menjadi **Rencana Aktivitas**.
- Saat presensi pulang, catatan menjadi **Realisasi Aktivitas**.

Catatan wajib diisi dan sebaiknya ditulis jelas, minimal menggambarkan pekerjaan yang benar-benar akan atau sudah dilakukan.

### 1.9A Lupa Presensi

Tab **Lupa Presensi** digunakan jika mahasiswa lupa melakukan presensi realtime.

Data yang diisi:

- Tanggal presensi yang terlupa.
- Jam presensi.
- Jenis presensi: **Masuk** atau **Pulang**.
- Catatan aktivitas, yaitu rencana untuk Masuk atau realisasi untuk Pulang.
- Alasan lupa presensi.
- Foto bukti realtime dari kamera.
- Koordinat GPS lokasi pengajuan.

Informasi yang ditampilkan:

- Mitra dan periode program.
- Rentang tanggal presensi yang berlaku.
- Kuota maksimal pengajuan.
- Jumlah pengajuan yang sudah digunakan.
- Sisa kuota.
- Riwayat pengajuan terbaru.

Aturan penting:

- Pengajuan hanya bisa dibuat selama kuota masih tersedia.
- Jika kuota maksimal diatur 0 oleh admin, fitur Lupa Presensi tidak aktif.
- Pengajuan tidak boleh untuk tanggal masa depan.
- Pengajuan harus berada dalam rentang tanggal presensi periode.
- Sistem menolak pengajuan pada Sabtu, Minggu, atau tanggal libur periode.
- Sistem menolak duplikasi jenis presensi pada tanggal yang sama.
- Pengajuan **Pulang** hanya dapat dibuat jika sudah ada presensi **Masuk** pada tanggal tersebut.
- Jam Pulang harus setelah jam Masuk.
- Data baru masuk sebagai presensi resmi setelah disetujui Pembimbing Lapangan, admin, atau koordinator.
- Jika mahasiswa melakukan presensi **Pulang** tanpa presensi **Masuk**, data pulang tetap tersimpan sebagai presensi belum berpasangan. Hari tersebut belum dihitung valid sampai presensi masuk dilengkapi melalui Lupa Presensi dan disetujui.

### 1.10 Cara Kerja Status Presensi

Sistem menentukan status presensi berdasarkan jam server dengan zona waktu Asia/Jakarta.

Jadwal default:

| Rentang Waktu | Status |
|---------------|--------|
| 07:00 sampai sebelum 08:00 | Masuk |
| 08:00 sampai sebelum 12:00 | Datang Terlambat |
| 12:00 sampai sebelum 16:00 | Pulang Cepat |
| 16:00 sampai sebelum 19:00 | Pulang |

Catatan:

- Jadwal dapat diubah oleh admin pada **Konfigurasi Program**.
- Di luar rentang waktu aktif, presensi ditolak dengan pesan bahwa check-in hanya dapat dilakukan pada jam kerja.
- Sistem membatasi satu presensi masuk dan satu presensi pulang resmi per hari.
- Presensi pulang tanpa presensi masuk tetap dapat disimpan, tetapi berstatus belum berpasangan dan tidak dihitung sebagai hari hadir valid.
- Jika presensi pulang tanpa presensi masuk tersimpan, sistem menawarkan pengajuan Lupa Presensi Masuk selama kuota masih tersedia.
- Hari hadir hanya dihitung jika terdapat pasangan **Masuk** dan **Pulang** yang valid pada hari kerja.
- Jika satu hari hanya memiliki satu data presensi, misalnya hanya Masuk atau hanya Pulang, hari tersebut tidak dihitung sebagai hari hadir pada nilai kehadiran dan rekap efektif.

### 1.11 Cara Kerja Penghitungan Durasi

Durasi harian dihitung setelah mahasiswa melakukan presensi pulang.

Rumus:

```text
Durasi harian = waktu presensi pulang - waktu presensi masuk
```

Contoh:

```text
Masuk  : 07:35
Pulang : 16:10
Durasi : 8 jam 35 menit
```

Durasi minimal default adalah 6 jam per hari.

Jika durasi kurang dari durasi minimal, sistem memberi sanksi poin.

Rumus sanksi default:

```text
Sanksi = pembulatan ke atas((durasi minimal - durasi aktual) / 60 menit) x poin sanksi per jam
```

Default poin sanksi per jam kurang adalah 1 poin.

Contoh:

```text
Durasi minimal : 6 jam
Durasi aktual  : 4 jam 30 menit
Kekurangan     : 1 jam 30 menit
Pembulatan     : 2 jam
Sanksi         : 2 poin
```

### 1.12 Cara Kerja Penghitungan Jarak

Sistem menghitung jarak antara koordinat mahasiswa dan koordinat lokasi mitra/tempat kegiatan.

Perhitungan menggunakan rumus Haversine:

```text
a = sin(delta_lat / 2)^2 + cos(lat_awal) x cos(lat_tujuan) x sin(delta_lng / 2)^2
jarak = radius_bumi x 2 x atan2(sqrt(a), sqrt(1 - a))
```

Default radius bumi:

```text
6.371.000 meter
```

Jarak disimpan dalam meter.

Contoh:

```text
Lokasi mitra      : -5.3655200, 105.2432500
Lokasi mahasiswa  : -5.3659000, 105.2436000
Jarak hasil sistem: disimpan sebagai meter
```

Batasan jarak:

- Default radius maksimum presensi adalah 5.000 meter.
- Jika radius maksimum diisi 0, validasi batas jarak dapat dianggap tidak aktif.
- Nilai radius maksimum dapat diubah oleh admin.
- Jika jarak mahasiswa lebih besar dari radius maksimum, presensi ditolak.

### 1.13 Batasan Foto dan Kamera

Presensi kegiatan dan presensi pembekalan membutuhkan foto realtime.

Batasan foto:

- Foto wajib diambil langsung dari kamera melalui browser.
- Upload file foto manual tidak digunakan pada form presensi.
- Format kamera diproses oleh sistem sebagai data gambar.
- Foto kamera diperkecil otomatis oleh browser sebelum dikirim agar ukuran data tidak terlalu besar.
- Ukuran foto default maksimal 4.096 KB.
- Jika ukuran foto melebihi batas, sistem menolak presensi.

Saran penggunaan:

- Gunakan browser modern.
- Izinkan akses kamera.
- Pastikan pencahayaan cukup.
- Jangan menutup tab sebelum presensi berhasil disimpan.

### 1.14 Batasan Lokasi GPS

Agar presensi berhasil:

- Browser harus diberi izin akses lokasi.
- GPS/perizinan lokasi perangkat harus aktif.
- Koneksi internet harus stabil.
- Lokasi yang dikirim harus berada dalam rentang latitude -90 sampai 90 dan longitude -180 sampai 180.
- Sistem menyimpan snapshot GPS ke server saat lokasi berhasil terbaca. Snapshot ini menjadi sumber koordinat final saat presensi disimpan.
- Snapshot GPS harus masih segar sesuai konfigurasi periode. Jika sudah kedaluwarsa, ambil ulang lokasi atau muat ulang halaman.
- Jika koordinat form berbeda jauh dari snapshot GPS server, presensi ditolak karena berpotensi dimanipulasi.
- Jika snapshot GPS berada di luar radius lokasi mitra, presensi ditolak.
- Jika akurasi GPS kurang baik tetapi masih dalam radius, presensi dapat tersimpan dengan penanda audit lokasi.
- Jika titik peta kurang pas, tunggu beberapa saat lalu muat ulang halaman agar sistem membuat snapshot GPS baru.

Sistem menyimpan snapshot GPS, akurasi, jarak Haversine ke mitra, dan flag audit. Akurasi tetap bergantung pada perangkat, browser, jaringan, dan izin lokasi pengguna.

### 1.15 Detail Program Saya

Menu: **Ringkasan Program**, lalu klik tombol **Detail** pada kartu program.

Halaman detail program memakai tab agar workflow mahasiswa tidak bercampur dalam satu halaman panjang.

| Tab | Isi utama |
|-----|-----------|
| Detail Program | Ringkasan pelaksanaan, data program/periode, mitra, dosen pembimbing, pembimbing lapangan, deadline periode, tombol ajukan pindah mitra, dan tombol perubahan pembimbing. |
| Pembekalan | Event pembekalan yang sesuai program/periode dan tombol presensi pembekalan jika tersedia. |
| Presensi & Catatan | Presensi harian, log presensi, catatan harian, tombol cetak catatan harian, dan tombol cetak laporan presensi. |
| Pelaporan | Form unggah progres laporan sampai Pelaporan Tahap 4/Laporan Lengkap Bab 1 sampai 5, status review, catatan reviewer, dan file unggahan. |
| Seminar & Penilaian | Pengajuan seminar, ACC seminar, jadwal seminar, penilaian dosen via sistem, atau penilaian manual yang divalidasi admin/koordinator. |
| Penyelesaian | Upload bukti penyerahan laporan hardcopy, tombol cetak laporan, rekap nilai akhir setelah final, dan tombol cetak berita acara nilai jika nilai sudah final. |

Tab **Pelaporan** hanya digunakan untuk unggahan progres laporan sampai Laporan Lengkap. Jenis unggahan progres pada tab **Pelaporan**:

- Proposal Rencana Kerja.
- Pelaporan Tahap 1: Bab 1.
- Pelaporan Tahap 2: Bab 1 dan 2.
- Pelaporan Tahap 3: Bab 1, 2 dan 3.
- Pelaporan Tahap 4/Laporan Lengkap: Bab 1 sampai 5.

Batasan file unggahan laporan:

- Format file: PDF, DOC, atau DOCX.
- Maksimal ukuran file: 10 MB.
- Setiap unggahan disimpan dengan status review.

### 1.16 Seminar & Penilaian

Menu: **Ringkasan Program** > **Detail** > tab **Seminar & Penilaian**

- Mahasiswa wajib memiliki email pembimbing lapangan pada enrollment.
- Jika email belum ada, mahasiswa harus mengajukan pelengkapan/perubahan data pembimbing terlebih dahulu.
- Seminar diajukan melalui workflow Pengajuan Seminar, bukan sebagai progres laporan biasa.
- Pengajuan seminar baru dapat dilakukan setelah mahasiswa mengunggah Pelaporan Tahap 4/Laporan Lengkap Bab 1 sampai 5.
- ACC seminar dapat dilakukan melalui dua jalur: dosen pembimbing menyetujui lewat sistem, atau mahasiswa mengunggah berkas ACC seminar dari dosen untuk divalidasi admin/koordinator.
- Penilaian seminar juga memiliki dua jalur: dosen pembimbing mengisi nilai via sistem, atau mahasiswa menginput komponen nilai manual dan mengunggah berkas bukti/form penilaian untuk divalidasi admin/koordinator.
- Setelah seminar berstatus selesai, mahasiswa masih dapat mengajukan seminar lagi jika diperlukan, misalnya untuk perbaikan/ulang sesuai keputusan akademik.

Komponen nilai seminar:

| Komponen | Bobot |
|----------|-------|
| Penguasaan materi/metode | 20% |
| Sikap ilmiah dan argumentasi | 10% |
| Teknik penyajian dan kebahasaan | 10% |
| Originalitas laporan | 30% |
| Relevansi dan keterpaduan laporan | 15% |
| Penulisan, format, dan bahasa | 15% |

Sistem menghitung nilai total dari komponen tersebut. Pada jalur manual, mahasiswa menginput komponen nilai sesuai form bukti dan admin/koordinator memvalidasi kesesuaian berkasnya.

### 1.17 Penyelesaian dan Hardcopy

Menu: **Ringkasan Program** > **Detail** > tab **Penyelesaian**

Tab **Penyelesaian** digunakan untuk tahap akhir setelah pelaporan dan seminar. Mahasiswa mengunggah bukti penyerahan laporan **hardcopy** pada tab ini.

Hardcopy tidak termasuk jenis unggahan pada tab **Pelaporan**. Pemisahan ini membuat progres laporan hanya berisi tahapan akademik sampai Laporan Lengkap, sedangkan bukti penyerahan fisik menjadi bagian dari penyelesaian program.

Pada tab ini mahasiswa juga dapat mencetak laporan jika data penting sudah tersedia, terutama dosen pembimbing dan pembimbing lapangan.

Jika nilai akhir sudah difinalisasi admin/koordinator, tab **Penyelesaian** menampilkan:

- Nilai dosen pembimbing.
- Nilai pembimbing lapangan.
- Nilai dasar.
- Pengurangan final.
- Total nilai.
- Status finalisasi.
- Tombol cetak Berita Acara Nilai.

Berita Acara Nilai memakai data final yang sudah disahkan sistem. Hari, tanggal, dan jam pada berita acara mengikuti waktu seminar agar dokumen konsisten dan tidak berubah berdasarkan waktu cetak.

### 1.18 Cara Kerja Sanksi Keterlambatan Laporan

Setiap periode dapat memiliki deadline dan bobot sanksi sendiri.

Jika mahasiswa mengunggah laporan setelah deadline, sistem menghitung sanksi.

Ada dua mode sanksi:

- **Per hari**: jumlah hari terlambat dikali poin per hari.
- **Tetap**: poin dikenakan sekali ketika terlambat.

Rumus sanksi per hari:

```text
Sanksi = jumlah hari terlambat x poin deadline
```

Contoh:

```text
Deadline       : 10 Juli
Tanggal upload : 13 Juli
Terlambat      : 3 hari
Poin deadline  : 5 poin per hari
Sanksi         : 15 poin
```

Sanksi dari presensi dan laporan masuk ke total sanksi enrollment.

### 1.19 Catatan Harian

Catatan harian dibuat otomatis dari presensi masuk dan pulang.

Sumber catatan:

- Catatan presensi masuk menjadi **Rencana Aktivitas**.
- Catatan presensi pulang menjadi **Realisasi Aktivitas**.

Catatan harian menampilkan:

- Tanggal.
- Jam masuk.
- Jam pulang.
- Durasi.
- Jarak saat masuk.
- Jarak saat pulang.
- Rencana aktivitas.
- Realisasi aktivitas.

Mahasiswa dapat mencetak form catatan harian dari tab **Presensi & Catatan** untuk keperluan paraf pembimbing lapangan.

### 1.20 Cetak Laporan

Mahasiswa dapat mencetak laporan dari tab **Penyelesaian** pada detail program.

Cetak laporan memuat:

- Identitas mahasiswa.
- Program studi.
- Periode program.
- Mitra/tempat kegiatan.
- Dosen pembimbing.
- Pembimbing lapangan.
- Email pembimbing lapangan.
- Rekap presensi.
- Grafik/ringkasan durasi dan waktu presensi.

Cetak laporan hanya dapat dilakukan jika data penting sudah tersedia, terutama dosen pembimbing dan pembimbing lapangan.

Pada tab **Presensi & Catatan**, mahasiswa juga dapat mencetak:

- **Cetak Catatan Harian**: daftar rencana dan realisasi harian dari pasangan presensi.
- **Cetak Laporan Presensi**: rekap presensi masuk/pulang, durasi, jarak, dan status presensi.

Jika nilai sudah final, mahasiswa dapat mencetak **Berita Acara Nilai** dari tab **Penyelesaian**. Dokumen nilai terdiri dari:

- Halaman berita acara nilai.
- Halaman nilai dosen pembimbing.
- Halaman nilai pembimbing lapangan.

Dokumen berita acara dilengkapi QR verifikasi keabsahan dokumen.

### 1.21 Pindah Mitra

Menu: **Pindah Mitra**

Mahasiswa dapat mengajukan pindah mitra/tempat kegiatan jika memiliki enrollment aktif, periode tidak terkunci, dan layanan pindah mitra sedang dibuka oleh admin.

Jika tombol **Ajukan Pindah Mitra** diklik dari tab **Detail Program**, sistem membawa nilai program/periode tersebut ke form berikutnya sebagai **Enrollment Aktif**.

Data yang diisi:

- Enrollment/program yang sedang berjalan.
- Mitra baru yang dituju.
- Alasan pindah.

Permohonan akan diproses admin/koordinator. Jika disetujui, data mitra pada enrollment diperbarui.

### 1.22 Perubahan Pembimbing

Menu: **Perubahan Pembimbing**

Mahasiswa dapat mengajukan perubahan atau pelengkapan data pembimbing jika memiliki enrollment aktif, periode tidak terkunci, dan layanan perubahan pembimbing sedang dibuka oleh admin.

Jika tombol **Perubahan Pembimbing** diklik dari tab **Detail Program**, sistem membawa nilai program/periode tersebut ke form berikutnya sebagai **Enrollment Aktif**. Field usulan dosen pembimbing, nama pembimbing lapangan, HP, dan email pembimbing lapangan akan diisi dengan nilai lama jika data sebelumnya sudah ada, sehingga mahasiswa cukup memperbaiki bagian yang berubah.

Data yang dapat diajukan:

- Usulan dosen pembimbing.
- Nama pembimbing lapangan.
- HP pembimbing lapangan.
- Email pembimbing lapangan.
- Alasan pengajuan.

Minimal satu data pembimbing harus diisi. Pengajuan akan diproses admin/koordinator. Jika disetujui, data pembimbing pada enrollment diperbarui.

### 1.23 Data Mitra dan Peta

Menu: **Data Mitra**

Mahasiswa dapat melihat data mitra yang tersedia, termasuk lokasi pada peta. Data ini membantu mahasiswa memilih mitra saat pendaftaran.

Peta menggunakan Leaflet dan tile OpenStreetMap. Lokasi dapat ditampilkan sebagai marker, dan sistem dapat memakai koordinat tersebut sebagai acuan presensi setelah enrollment aktif.

### 1.24 Ringkasan Batasan Penting

| Area | Batasan |
|------|---------|
| Pendaftaran | Profil mahasiswa wajib lengkap, periode tersedia, dan rentang pendaftaran terbuka. |
| Periode terkunci | Pendaftaran, usulan mitra, pindah mitra, dan perubahan pembimbing ditutup untuk mahasiswa. |
| Toggle layanan | Admin dapat menonaktifkan usulan mitra, pindah mitra, atau perubahan pembimbing melalui Konfigurasi Program tanpa menghentikan presensi, pelaporan, seminar, dan penyelesaian. |
| Dokumen pendaftaran | PDF, maksimal 5 MB, berisi Transkrip Sementara + KRS semester saat ini. |
| Kelayakan S1 | Default SKS minimal 100, semester minimal 6, IPK minimal 2,00. |
| Kelayakan D3 | Default SKS minimal 80, semester minimal 4, IPK minimal 2,00. |
| Kuota mitra | Default maksimal 3 mahasiswa per tempat, dapat diubah admin. |
| Presensi harian | Hanya untuk enrollment aktif pada periode yang masih aktif secara operasional. |
| Presensi harian per hari | Maksimal satu masuk dan satu pulang. |
| Jam presensi default | 07:00 sampai sebelum 19:00. |
| Durasi minimal default | 6 jam per hari. |
| Jarak presensi default | Maksimal 5.000 meter dari lokasi mitra. |
| Foto presensi | Wajib kamera realtime, default maksimal 4.096 KB. |
| Catatan presensi | Wajib diisi; masuk menjadi rencana, pulang menjadi realisasi. |
| Presensi pembekalan | Satu kali per event, memakai lokasi pembekalan. |
| Unggah laporan | PDF/DOC/DOCX, maksimal 10 MB. |
| Seminar | Email pembimbing lapangan wajib tersedia dan Laporan Lengkap Bab 1-5 sudah diunggah sebelum pengajuan seminar. |

### 1.25 Alur Singkat Mahasiswa dari Awal sampai Selesai

1. Login ke SiLAT.
2. Lengkapi **Profil Saya**.
3. Buka **Pendaftaran Program**.
4. Pilih program dan periode.
5. Pilih mitra atau ajukan mitra baru.
6. Isi data kontak dan pembimbing lapangan.
7. Isi kelayakan akademik dan unggah PDF bukti akademik.
8. Kirim pendaftaran.
9. Tunggu validasi admin/koordinator.
10. Jika diminta revisi, perbaiki dan kirim ulang.
11. Jika sudah aktif, ikuti pembekalan jika tersedia.
12. Lakukan presensi masuk dan pulang setiap hari kegiatan.
13. Unggah progres laporan sesuai deadline.
14. Pantau sanksi, status review, dan catatan harian di detail program.
15. Lengkapi email pembimbing lapangan sebelum pengajuan seminar.
16. Ajukan seminar setelah Laporan Lengkap Bab 1-5 diunggah.
17. Ikuti alur ACC seminar, jadwal seminar, dan penilaian sesuai arahan dosen/admin/koordinator.
18. Cetak laporan atau form catatan harian jika diperlukan.

---

## Bagian 2. Role Dosen Pembimbing

### 2.1 Ringkasan Hak Akses Dosen Pembimbing

Dosen pembimbing dapat menggunakan SiLAT untuk:

- Melihat dashboard dosen pembimbing.
- Melihat daftar mahasiswa bimbingan.
- Membuka rekap bimbingan dan monitoring presensi mahasiswa.
- Membuka peta mitra dan peta monitoring.
- Melihat unggahan progres laporan mahasiswa bimbingan.
- Membuka file laporan yang diunggah mahasiswa.
- Memberikan review laporan: setujui, minta revisi, atau tolak.
- Memberikan catatan review kepada mahasiswa.
- Melihat sanksi keterlambatan pada unggahan laporan.
- Memproses ACC seminar melalui sistem.
- Mengisi nilai seminar melalui sistem jika seminar sudah terjadwal.

Catatan:

- Akses dosen pembimbing dibatasi pada mahasiswa yang ditetapkan sebagai bimbingannya.
- Jika dosen juga memiliki penugasan koordinator aktif, dosen dapat memperoleh akses tambahan untuk scope periode/prodi koordinator. Akses tersebut dibahas pada Bagian 3 Role Koordinator.
- Nilai akhir difinalisasi oleh admin/koordinator setelah nilai dosen dan nilai Pembimbing Lapangan tersedia.

### 2.2 Login dan Dashboard Dosen

Menu: **Dashboard**

Setelah login sebagai dosen, sistem menampilkan dashboard dosen pembimbing.

Dashboard dosen menampilkan:

- Jumlah mahasiswa bimbingan aktif.
- Jumlah mahasiswa yang memerlukan bimbingan/perhatian.
- Notifikasi aksi yang perlu diproses, seperti review laporan dan seminar/penilaian.
- Aksi cepat menuju modul yang membutuhkan tindakan dosen.
- Deadline mahasiswa bimbingan dalam 7 hari ke depan; jika tidak ada, sistem menampilkan deadline terdekat.
- Daftar mahasiswa bimbingan yang dikelompokkan berdasarkan program/periode.
- Aksi cepat per kelompok periode menuju Review Laporan, Seminar & Penilaian, Peta Monitoring, dan Rekap Bimbingan.

Data mahasiswa bimbingan berasal dari enrollment mahasiswa yang sudah ditetapkan dosen pembimbingnya oleh admin/koordinator.

Sidebar dosen menampilkan badge jumlah pekerjaan pada menu yang membutuhkan aksi, misalnya **Review Laporan (2)** atau **Seminar & Penilaian (5)**. Badge ini membantu dosen langsung melihat antrean pekerjaan tanpa membuka setiap menu satu per satu.

### 2.3 Daftar Mahasiswa Bimbingan

Pada dashboard dosen, tabel mahasiswa bimbingan menampilkan:

- Nama mahasiswa.
- NPM.
- Periode program.
- Program studi.
- Mitra/tempat kegiatan.
- Link menuju peta monitoring.
- Link menuju rekap bimbingan.

Dosen tidak memilih sendiri mahasiswa bimbingan. Penugasan dosen pembimbing dilakukan melalui validasi pendaftaran atau pengelolaan peserta oleh admin/koordinator.

### 2.4 Review Laporan

Menu: **Review Laporan**

Halaman Review Laporan digunakan untuk memeriksa dokumen progres laporan yang diunggah mahasiswa.

Dosen dapat melakukan:

- Mencari unggahan berdasarkan nama mahasiswa, NPM, atau mitra.
- Memfilter unggahan berdasarkan status.
- Membuka file laporan mahasiswa.
- Melihat jenis dokumen yang diunggah.
- Melihat waktu unggah.
- Melihat sanksi keterlambatan jika ada.
- Memberikan status review.
- Menulis catatan review.

Status review yang tersedia:

| Status | Fungsi |
|--------|--------|
| Setujui | Dokumen diterima dan dikunci. |
| Minta Revisi | Mahasiswa perlu memperbaiki dan mengunggah ulang. |
| Tolak | Dokumen ditolak karena tidak sesuai. |

Status yang terlihat pada sistem:

| Status Sistem | Arti |
|---------------|------|
| Menunggu Review | Mahasiswa sudah mengunggah dokumen, tetapi belum direview. |
| Disetujui | Dokumen sudah diterima dan terkunci. |
| Perlu Revisi | Mahasiswa harus memperbaiki dokumen. |
| Ditolak | Dokumen tidak diterima. |

### 2.5 Cara Melakukan Review Laporan

Langkah review:

1. Buka menu **Review Laporan**.
2. Gunakan kolom cari atau filter status jika diperlukan.
3. Pada baris mahasiswa, klik **Buka file** untuk memeriksa dokumen.
4. Pilih status review:
   - **Setujui**
   - **Minta Revisi**
   - **Tolak**
5. Isi catatan review jika diperlukan.
6. Klik **Simpan Review**.

Catatan wajib diisi jika dosen memilih **Minta Revisi** atau **Tolak**.

### 2.6 Dokumen yang Sudah Disetujui

Jika dokumen sudah berstatus **Disetujui**, dokumen menjadi terkunci.

Dampaknya:

- Dosen tidak dapat mengubah review dokumen tersebut melalui form review.
- Mahasiswa tidak dapat mengganti dokumen yang sudah disetujui pada jenis unggahan yang sama.
- Jika dokumen yang disetujui adalah **Laporan Lengkap**, sistem menyimpan file tersebut sebagai `final_report_path` pada enrollment.

Penguncian ini menjaga agar dokumen final tidak berubah tanpa proses administratif.

### 2.7 Catatan Review untuk Mahasiswa

Catatan review dipakai mahasiswa sebagai arahan perbaikan.

Saran isi catatan:

- Tuliskan bagian yang harus diperbaiki.
- Sebutkan halaman/bab jika memungkinkan.
- Gunakan kalimat singkat dan operasional.
- Hindari catatan yang terlalu umum seperti “perbaiki lagi” tanpa arahan.

Contoh catatan:

```text
Bab 2 perlu menambahkan struktur organisasi mitra dan penjelasan proses bisnis berjalan. Perbaiki juga sitasi pada halaman 8.
```

Catatan review akan terlihat oleh mahasiswa pada halaman **Laporan Saya**.

### 2.8 Rekap Bimbingan

Menu: **Rekap Bimbingan** atau **Rekap Monitoring**

Dosen dapat melihat rekap monitoring mahasiswa bimbingan.

Filter yang tersedia:

- Periode program.
- Program.
- Program studi.
- Tanggal mulai.
- Tanggal akhir.
- Termasuk Sabtu.
- Termasuk Minggu.
- Termasuk hari libur.

Rekap menampilkan:

- Jumlah mahasiswa.
- Total hari hadir.
- Total durasi.
- Nama mahasiswa.
- NPM.
- Email.
- Mitra/tempat kegiatan.
- Jumlah hari hadir.
- Jumlah check-in.
- Rata-rata jarak.
- Total durasi.
- Rentang jam masuk.
- Rentang jam pulang.

Untuk role dosen, data dibatasi pada mahasiswa bimbingan dosen tersebut. Jika dosen juga koordinator, sistem dapat memperluas data sesuai penugasan periode/prodi koordinator.

### 2.9 Seminar & Penilaian

Menu: **Seminar & Penilaian**

Dosen pembimbing dapat memproses pengajuan seminar mahasiswa bimbingannya.

Alur via sistem:

1. Mahasiswa mengajukan seminar setelah Laporan Lengkap Bab 1-5 diunggah.
2. Dosen membuka menu **Seminar & Penilaian**.
3. Dosen memilih keputusan ACC seminar: setujui, minta revisi, atau tolak.
4. Jika disetujui, admin/koordinator dapat menjadwalkan seminar.
5. Setelah seminar terjadwal, dosen mengisi komponen nilai seminar via sistem.

Komponen nilai seminar:

| Komponen | Bobot |
|----------|-------|
| Penguasaan materi/metode | 20% |
| Sikap ilmiah dan argumentasi | 10% |
| Teknik penyajian dan kebahasaan | 10% |
| Originalitas laporan | 30% |
| Relevansi dan keterpaduan laporan | 15% |
| Penulisan, format, dan bahasa | 15% |

Sistem menghitung nilai total berdasarkan komponen tersebut.

Jika mahasiswa memakai jalur manual, dosen memberi ACC atau nilai di luar sistem sesuai dokumen resmi. Mahasiswa mengunggah bukti, lalu admin/koordinator memvalidasi berkas manual tersebut.

### 2.10 Cara Kerja Hari Hadir pada Rekap

Sistem menghitung hari hadir dari pasangan presensi masuk dan pulang pada tanggal yang sama.

Ketentuan:

- Satu hari dihitung hadir jika ada presensi masuk dan presensi pulang.
- Jika hanya ada satu presensi, hari tersebut tidak dihitung sebagai hari hadir lengkap.
- Durasi dihitung dari selisih waktu pulang dan masuk.
- Filter Sabtu, Minggu, dan hari libur menentukan apakah hari tersebut ikut dihitung pada rekap.

Contoh:

```text
Presensi masuk : 07:45
Presensi pulang: 16:20
Hari hadir     : 1 hari
Durasi         : 8 jam 35 menit
```

### 2.11 Cara Kerja Jarak pada Rekap

Jarak pada rekap berasal dari jarak presensi mahasiswa terhadap lokasi mitra/tempat kegiatan.

Sistem menghitung jarak menggunakan rumus Haversine berdasarkan:

- Latitude dan longitude lokasi mitra.
- Latitude dan longitude presensi mahasiswa.
- Radius bumi default 6.371.000 meter.

Rata-rata jarak pada rekap dihitung dari data jarak presensi yang tersedia.

Interpretasi jarak:

- Jarak kecil menunjukkan mahasiswa melakukan presensi dekat lokasi mitra.
- Jarak besar perlu diperiksa, terutama jika melebihi radius yang dikonfigurasi.
- Perbedaan jarak dapat dipengaruhi akurasi GPS perangkat mahasiswa.

### 2.12 Peta Mitra

Menu: **Peta Mitra**

Dosen dapat membuka peta mitra untuk melihat sebaran instansi/tempat kegiatan.

Peta mitra membantu dosen:

- Mengenali lokasi tempat kegiatan mahasiswa.
- Melihat distribusi mitra.
- Mengecek konteks lokasi sebelum membaca rekap presensi.

Peta menggunakan Leaflet dan tile OpenStreetMap.

### 2.13 Peta Monitoring

Menu: **Peta Monitoring**

Dosen dapat melihat titik presensi mahasiswa pada peta monitoring sesuai hak aksesnya.

Peta monitoring membantu dosen:

- Melihat lokasi check-in mahasiswa.
- Membandingkan posisi mahasiswa dengan lokasi mitra.
- Membaca jarak presensi secara visual.
- Mengidentifikasi pola presensi yang perlu dikonfirmasi.

Data peta monitoring mengikuti filter dan scope role. Untuk dosen, data utama adalah mahasiswa bimbingan.

### 2.14 Akses File Laporan

Dosen dapat membuka file laporan melalui tombol **Buka file** pada halaman Review Laporan.

Batasan akses:

- Admin dapat membuka semua file.
- Dosen pembimbing hanya dapat membuka file mahasiswa bimbingannya.
- Koordinator hanya dapat membuka file sesuai scope periode/prodi penugasannya.
- Mahasiswa hanya dapat membuka file miliknya sendiri.

Jika dosen tidak termasuk pembimbing mahasiswa tersebut dan tidak memiliki scope koordinator yang sesuai, akses file ditolak.

### 2.15 Cara Kerja Sanksi Unggahan Laporan

Pada halaman Review Laporan, dosen dapat melihat poin sanksi jika mahasiswa terlambat mengunggah dokumen.

Sanksi dihitung otomatis berdasarkan deadline periode.

Mode sanksi:

- **Per hari**: jumlah hari terlambat dikali poin deadline.
- **Tetap**: poin dikenakan satu kali ketika dokumen terlambat.

Contoh:

```text
Deadline       : 10 Juli
Tanggal upload : 13 Juli
Terlambat      : 3 hari
Poin deadline  : 5 poin per hari
Sanksi         : 15 poin
```

Dosen tidak mengubah poin sanksi secara manual pada halaman review. Dosen hanya memberi status dan catatan review dokumen.

### 2.16 Batasan Role Dosen Pembimbing

| Area | Batasan |
|------|---------|
| Mahasiswa bimbingan | Dosen hanya melihat mahasiswa yang ditetapkan sebagai bimbingannya, kecuali dosen juga memiliki penugasan koordinator. |
| Review laporan | Dosen dapat mereview unggahan mahasiswa bimbingan. |
| Catatan review | Wajib jika meminta revisi atau menolak dokumen. |
| Dokumen disetujui | Dokumen yang sudah disetujui terkunci dan tidak dapat direview ulang dari form biasa. |
| File laporan | Akses file dibatasi berdasarkan relasi pembimbing atau scope koordinator. |
| Rekap monitoring | Data dibatasi sesuai mahasiswa bimbingan atau scope koordinator. |
| Peta monitoring | Data mengikuti hak akses role. |
| Penilaian numerik | Dosen mengisi nilai seminar/laporan sesuai workflow. Nilai akhir difinalisasi oleh admin/koordinator setelah nilai Pembimbing Lapangan tersedia. |
| Validasi pendaftaran | Dosen pembimbing biasa tidak memvalidasi pendaftaran, kecuali memiliki role/penugasan koordinator yang sesuai. |

### 2.17 Alur Singkat Dosen Pembimbing

1. Login ke SiLAT sebagai dosen.
2. Buka **Dashboard** untuk melihat ringkasan mahasiswa bimbingan.
3. Buka **Review Laporan** untuk memeriksa unggahan mahasiswa.
4. Klik **Buka file** pada dokumen yang akan diperiksa.
5. Pilih status review: setujui, minta revisi, atau tolak.
6. Isi catatan review, terutama jika meminta revisi atau menolak.
7. Klik **Simpan Review**.
8. Buka **Seminar & Penilaian** untuk memproses ACC atau nilai seminar jika mahasiswa sudah mengajukan seminar.
9. Buka **Rekap Bimbingan** untuk memantau presensi, durasi, dan jarak mahasiswa.
10. Gunakan **Peta Monitoring** jika perlu memeriksa lokasi presensi secara visual.
11. Lanjutkan komunikasi akademik dengan mahasiswa berdasarkan catatan review dan rekap aktivitas.

---

## Bagian 3. Role Koordinator

### 3.1 Ringkasan Hak Akses Koordinator

Koordinator adalah dosen yang diberi penugasan aktif pada kombinasi periode program dan program studi tertentu.

Koordinator dapat menggunakan SiLAT untuk:

- Melihat dashboard koordinator.
- Melihat scope penugasan periode/prodi.
- Memvalidasi pendaftaran mahasiswa pada scope tugasnya.
- Membuka dan mereview dokumen bukti akademik pendaftaran.
- Menetapkan atau mengoreksi dosen pembimbing pada proses validasi.
- Mengoreksi data pembimbing lapangan dan email pembimbing lapangan.
- Membuka dan mengelola event pembekalan program.
- Melihat rekap presensi pembekalan.
- Memproses permohonan perubahan pembimbing.
- Mereview progres laporan mahasiswa pada scope tugasnya.
- Memvalidasi ACC seminar manual, menjadwalkan seminar, dan memvalidasi nilai seminar manual pada scope tugasnya.
- Melihat peta monitoring dan rekap monitoring pada scope tugasnya.
- Memantau mahasiswa dengan sanksi tertinggi.
- Membuka **Analisis & Laporan** pada scope periode/prodi penugasannya untuk membaca dashboard progres, funnel, risiko, heatmap, dan grafik operasional.

Batas utama koordinator:

- Koordinator hanya mengakses data sesuai periode dan prodi penugasannya.
- Jika event pembekalan dibuat untuk semua prodi, koordinator tetap hanya melihat peserta sesuai scope prodi yang ditugaskan.
- Koordinator tidak mengelola master data global seperti user, prodi, program, periode, dan **Konfigurasi Program**. Fitur tersebut berada pada role admin.

### 3.2 Login dan Dashboard Koordinator

Menu: **Dashboard Koordinator**

Dashboard koordinator menampilkan ringkasan data pada scope penugasan.

Informasi yang ditampilkan:

- Jumlah mahasiswa terdaftar.
- Jumlah check-in hari ini.
- Jumlah laporan selesai.
- Total sanksi.
- Peserta terbaru.
- Rekap pembekalan.
- Mahasiswa dengan sanksi tertinggi.
- Daftar penugasan koordinator.

Tombol cepat pada dashboard:

- **Validasi Pendaftaran** untuk memproses pengajuan mahasiswa.
- **Kelola pembekalan** untuk membuka event pembekalan.
- **Peta** dan **Rekap** pada daftar penugasan untuk membuka monitoring sesuai periode/prodi.

### 3.3 Scope Penugasan Koordinator

Scope koordinator ditentukan oleh data penugasan:

- Dosen koordinator.
- Periode program.
- Program studi.
- Status aktif/nonaktif.

Satu penugasan aktif berarti koordinator bertanggung jawab pada satu kombinasi periode dan prodi.

Contoh:

```text
Dosen      : Dr. A
Periode    : Kerja Praktik Juli 2026
Prodi      : S1 Ilmu Komputer
Status     : Aktif
```

Koordinator dengan penugasan di atas hanya melihat dan memproses data mahasiswa S1 Ilmu Komputer pada periode Kerja Praktik Juli 2026, kecuali ia memiliki penugasan tambahan.

### 3.4 Validasi Pendaftaran

Menu: **Validasi Pendaftaran**

Halaman ini digunakan untuk memproses pendaftaran mahasiswa yang masuk ke scope koordinator.

Koordinator dapat:

- Mencari pendaftaran berdasarkan nama mahasiswa, NPM, atau mitra.
- Memfilter berdasarkan program, periode, dan prodi.
- Membaca data dalam tabel dengan kolom Program/Periode, Nama/NPM, Prodi, SKS/IPK, Lampiran, Catatan Verifikasi, dan Aksi.
- Membuka dokumen bukti akademik.
- Memberi catatan verifikasi.
- Mengambil keputusan: setujui, minta revisi, atau tolak.
- Memilih beberapa pendaftaran dengan checkbox, memakai check/uncheck all, lalu menjalankan bulk action setujui, minta revisi, atau tolak.

Kolom pembimbing tidak ditampilkan pada halaman ini. Penetapan atau koreksi pembimbing diproses melalui **Peserta Periode** agar validasi pendaftaran tetap fokus pada kelayakan dan keputusan pendaftaran.

### 3.5 Data yang Diperiksa saat Validasi

Pada setiap kartu pendaftaran, koordinator memeriksa:

- Nama mahasiswa.
- NPM.
- Program studi.
- Periode program.
- Mitra/tempat kegiatan.
- Status pendaftaran.
- KRS program/KP.
- Total SKS.
- Semester.
- IPK.
- Dokumen bukti akademik.
- Kuota minimal mitra jika sistem menampilkan peringatan.

Dokumen bukti akademik berisi:

```text
Transkrip Sementara + KRS Semester saat ini
```

Jika dokumen belum diunggah, sistem menampilkan informasi bahwa dokumen belum tersedia.

### 3.6 Keputusan Validasi Pendaftaran

Koordinator dapat memilih tiga keputusan.

| Keputusan | Dampak |
|-----------|--------|
| Setujui | Status enrollment menjadi `active`. Mahasiswa dapat menjalankan program dan melakukan presensi. |
| Minta Revisi | Status enrollment menjadi `revision_required`. Mahasiswa harus memperbaiki pendaftaran dan mengirim ulang. |
| Tolak | Status enrollment menjadi `rejected`. Pendaftaran tidak dilanjutkan. |

Ketentuan penting:

- Dosen pembimbing wajib dipilih sebelum pendaftaran diaktifkan.
- Catatan verifikasi sebaiknya diisi jika meminta revisi atau menolak.
- Email pembimbing lapangan opsional saat validasi, tetapi wajib tersedia sebelum mahasiswa mengajukan seminar.
- Jika kuota minimal mitra belum terpenuhi, sistem menampilkan peringatan. Peringatan ini membantu koordinator mengambil keputusan, tetapi alur akhir mengikuti kebijakan akademik yang berlaku.

### 3.7 Cara Melakukan Validasi Pendaftaran

Langkah validasi:

1. Buka menu **Validasi Pendaftaran**.
2. Gunakan filter program, periode, atau prodi jika diperlukan.
3. Pilih pendaftaran yang akan diproses.
4. Buka dokumen bukti akademik.
5. Periksa SKS, semester, IPK, KRS, mitra, dan data kontak.
6. Pilih dosen pembimbing.
7. Lengkapi data pembimbing lapangan jika perlu.
8. Isi catatan verifikasi jika ada arahan untuk mahasiswa.
9. Klik **Setujui**, **Minta Revisi**, atau **Tolak**.

### 3.8 Pembekalan Program

Menu: **Pembekalan**

Koordinator dapat membuka event pembekalan untuk periode/prodi sesuai scope tugasnya.

Data event pembekalan:

- Periode program.
- Prodi.
- Nama lokasi.
- Latitude.
- Longitude.
- Waktu dibuka.
- Waktu ditutup.
- Radius maksimum meter.
- Status aktif.

Nama event dibuat otomatis mengikuti program dan periode, misalnya:

```text
Pembekalan Kerja Praktik Periode Juli 2026
```

Koordinator menentukan lokasi pembekalan melalui nama lokasi dan peta.

### 3.9 Pick Lokasi Pembekalan dari Peta

Pada form event pembekalan, koordinator dapat menggunakan peta untuk menentukan koordinat.

Cara memilih lokasi:

1. Isi nama lokasi.
2. Pilih sugest lokasi jika tersedia.
3. Klik titik lokasi pada peta atau geser marker.
4. Pastikan latitude dan longitude terisi.
5. Isi waktu pembukaan dan penutupan presensi jika diperlukan.
6. Isi radius maksimum jika ingin membatasi jarak presensi.
7. Klik **Simpan Event**.

Sugest lokasi bekerja dengan urutan:

- Riwayat lokasi internal sistem.
- Sumber eksternal jika data internal tidak ditemukan.

Jika radius maksimum dikosongkan, sistem memakai konfigurasi radius presensi periode.

### 3.10 Rekap Presensi Pembekalan

Koordinator dapat membuka detail event pembekalan melalui tombol **Rekap** atau **Detail**.

Rekap pembekalan menampilkan:

- Nama event.
- Lokasi.
- Prodi.
- Jumlah peserta.
- Jumlah mahasiswa yang sudah presensi.
- Jumlah mahasiswa yang belum presensi.
- Daftar peserta.
- Status presensi setiap peserta.
- Waktu presensi.
- Jarak mahasiswa dari lokasi pembekalan.

Cara membaca status:

| Status | Arti |
|--------|------|
| Presensi | Mahasiswa sudah melakukan presensi pembekalan. |
| Belum presensi | Mahasiswa belum melakukan presensi pembekalan. |

Jarak dihitung dari koordinat mahasiswa saat presensi ke koordinat lokasi pembekalan.

### 3.11 Cara Kerja Presensi Pembekalan

Presensi pembekalan memakai mekanisme yang mirip dengan presensi mahasiswa.

Sistem mencatat:

- Event pembekalan.
- Mahasiswa.
- Waktu presensi.
- Koordinat mahasiswa.
- Koordinat event.
- Jarak dalam meter.
- Foto realtime.

Batasan:

- Mahasiswa hanya dapat presensi satu kali untuk satu event.
- Presensi hanya berlaku pada event yang sesuai periode/prodi enrollment mahasiswa.
- Jika event punya waktu buka/tutup, presensi di luar rentang waktu ditolak.
- Jika radius maksimum aktif dan mahasiswa di luar radius, presensi ditolak.
- Lokasi event tidak dapat diubah oleh mahasiswa.

### 3.12 Perubahan Pembimbing

Menu: **Perubahan Pembimbing**

Koordinator dapat memproses permohonan perubahan atau pelengkapan pembimbing mahasiswa pada scope tugasnya.

Data yang dapat direview:

- Mahasiswa dan enrollment.
- Dosen pembimbing saat ini.
- Usulan dosen pembimbing.
- Pembimbing lapangan saat ini.
- Usulan pembimbing lapangan.
- HP pembimbing lapangan.
- Email pembimbing lapangan.
- Alasan mahasiswa.

Keputusan yang dapat diambil:

- Setujui permohonan.
- Tolak permohonan.
- Beri catatan reviewer.

Jika disetujui, data pembimbing pada enrollment diperbarui sesuai keputusan reviewer.

### 3.13 Review Laporan oleh Koordinator

Menu: **Review Laporan**

Koordinator dapat mereview progres laporan mahasiswa pada scope periode/prodi penugasannya.

Aksi yang tersedia sama seperti dosen pembimbing:

- Membuka file laporan.
- Melihat jenis dokumen.
- Melihat waktu unggah.
- Melihat poin sanksi keterlambatan.
- Memberi status review.
- Memberi catatan.

Status review:

| Status | Fungsi |
|--------|--------|
| Setujui | Dokumen diterima dan dikunci. |
| Minta Revisi | Mahasiswa perlu memperbaiki dan mengunggah ulang. |
| Tolak | Dokumen ditolak. |

Catatan wajib diisi jika koordinator meminta revisi atau menolak dokumen.

### 3.14 Review Seminar oleh Koordinator

Menu: **Review Seminar**

Koordinator dapat memproses bagian administratif seminar dalam scope periode/prodi penugasannya.

Koordinator dapat:

- Melihat pengajuan seminar mahasiswa dalam scope.
- Memvalidasi berkas ACC seminar manual.
- Menolak ACC manual jika bukti tidak sesuai dan memberi catatan.
- Menjadwalkan seminar setelah ACC valid.
- Memvalidasi nilai seminar manual yang diinput mahasiswa beserta berkas bukti/form penilaian.

Koordinator tidak menggantikan keputusan akademik dosen pembimbing untuk ACC atau nilai via sistem. Jalur dosen tetap dilakukan oleh dosen pembimbing, sedangkan koordinator menangani validasi administratif dan jadwal sesuai scope.

### 3.15 Rekap Monitoring

Menu: **Rekap Monitoring**

Koordinator dapat membaca rekap presensi dan aktivitas mahasiswa pada scope tugas.

Filter yang tersedia:

- Periode program.
- Program.
- Program studi.
- Tanggal mulai.
- Tanggal akhir.
- Termasuk Sabtu.
- Termasuk Minggu.
- Termasuk hari libur.

Rekap menampilkan:

- Total mahasiswa.
- Total hari hadir.
- Total durasi.
- Nama mahasiswa.
- NPM.
- Email.
- Mitra.
- Jumlah hari hadir.
- Jumlah check-in.
- Rata-rata jarak.
- Total durasi.
- Rentang jam masuk.
- Rentang jam pulang.

Koordinator dapat menggunakan rekap ini untuk melihat mahasiswa yang perlu dibimbing ulang, dipanggil, atau dikonfirmasi.

### 3.16 Peta Monitoring

Menu: **Peta Monitoring**

Peta monitoring menampilkan lokasi presensi mahasiswa sesuai scope koordinator.

Koordinator dapat menggunakan peta untuk:

- Melihat sebaran lokasi check-in mahasiswa.
- Membandingkan lokasi presensi dengan lokasi mitra.
- Membaca potensi presensi di luar lokasi.
- Mendukung evaluasi jika ada jarak presensi yang tidak wajar.

Data peta monitoring mengikuti filter periode/prodi dan hak akses koordinator.

### 3.17 Cara Kerja Hari Hadir, Durasi, dan Jarak

Koordinator membaca metrik presensi dengan aturan yang sama seperti role lain.

Hari hadir:

- Satu hari dihitung hadir jika mahasiswa memiliki presensi masuk dan pulang pada tanggal yang sama.
- Jika hanya masuk atau hanya pulang, hari tersebut belum dihitung sebagai hari hadir lengkap.

Durasi:

```text
Durasi = waktu presensi pulang - waktu presensi masuk
```

Jarak:

- Jarak dihitung dengan rumus Haversine.
- Acuan jarak adalah koordinat lokasi mitra untuk presensi harian.
- Untuk pembekalan, acuan jarak adalah koordinat lokasi pembekalan.
- Radius bumi default adalah 6.371.000 meter.

Sanksi durasi:

- Jika durasi harian kurang dari durasi minimal, sistem memberi poin sanksi.
- Default durasi minimal adalah 6 jam.
- Default sanksi adalah 1 poin per jam kurang, dibulatkan ke atas.

### 3.18 Sanksi dan Tindak Lanjut

Dashboard koordinator menampilkan mahasiswa dengan sanksi tertinggi.

Sumber sanksi:

- Durasi presensi harian kurang dari minimal.
- Keterlambatan unggah laporan sesuai deadline.

Koordinator dapat memakai data sanksi untuk:

- Menghubungi mahasiswa.
- Mengingatkan dosen pembimbing.
- Meminta mahasiswa memperbaiki pola presensi.
- Memeriksa apakah ada masalah lokasi, mitra, atau jadwal.
- Mengambil keputusan akademik sesuai kebijakan program.

### 3.18A Lupa Presensi

Menu: **Lupa Presensi**

Koordinator dapat memproses pengajuan Lupa Presensi dalam scope periode/prodi penugasannya.

Koordinator dapat:

- Mencari pengajuan berdasarkan mahasiswa, NPM, periode, atau status.
- Melihat tanggal dan jam presensi yang diajukan.
- Melihat jenis presensi, yaitu Masuk atau Pulang.
- Melihat jarak, foto bukti, akurasi/indikasi audit lokasi jika tersedia, catatan aktivitas, dan alasan lupa.
- Membaca catatan aktivitas dan alasan lupa.
- Melihat jarak/lokasi dan foto bukti pengajuan jika tersedia.
- Menyetujui atau menolak pengajuan.
- Memberi catatan review.

Jika disetujui, sistem membuat data presensi koreksi dan memasangkannya dengan presensi pada hari yang sama jika aturan pair terpenuhi. Jika ditolak, pengajuan tidak masuk ke data presensi resmi.

### 3.18B Finalisasi Nilai

Menu: **Finalisasi Nilai**

Koordinator dapat memfinalisasi nilai mahasiswa dalam scope periode/prodi penugasannya jika nilai dosen dan nilai Pembimbing Lapangan sudah tersedia.

Alur finalisasi:

1. Buka halaman **Finalisasi Nilai**.
2. Filter periode program jika diperlukan.
3. Periksa nilai dosen pembimbing dan nilai Pembimbing Lapangan.
4. Periksa total sanksi dan suggest pengurangan.
5. Isi atau koreksi **Pengurangan Final** jika diperlukan.
6. Isi nomor berita acara jika belum ada.
7. Tambahkan catatan koordinator/admin jika diperlukan.
8. Klik **Simpan Finalisasi**.

Setelah final:

- Total nilai dan huruf mutu tersimpan.
- Nilai dosen dan nilai Pembimbing Lapangan terkunci.
- Mahasiswa dapat melihat nilai akhir di tab **Penyelesaian**.
- Mahasiswa dapat mencetak Berita Acara Nilai.

### 3.19 Batasan Role Koordinator

| Area | Batasan |
|------|---------|
| Scope data | Koordinator hanya mengakses periode/prodi yang ditugaskan. |
| Validasi pendaftaran | Koordinator dapat menyetujui, meminta revisi, atau menolak pendaftaran dalam scope. |
| Aktivasi enrollment | Dosen pembimbing wajib dipilih sebelum enrollment diaktifkan. |
| Dokumen akademik | Koordinator dapat membuka dokumen bukti akademik pendaftaran dalam scope. |
| Pembekalan | Koordinator dapat membuat dan melihat event pembekalan pada scope tugasnya. |
| Presensi pembekalan | Koordinator melihat rekap hadir/belum hadir dan jarak, tetapi mahasiswa yang melakukan presensi. |
| Review laporan | Koordinator dapat mereview laporan dalam scope periode/prodi. |
| Review seminar | Koordinator dapat memvalidasi ACC manual, menjadwalkan seminar, dan memvalidasi nilai manual dalam scope periode/prodi. |
| Lupa Presensi | Koordinator dapat menyetujui/menolak pengajuan dalam scope periode/prodi. |
| Finalisasi nilai | Koordinator dapat memfinalisasi nilai dalam scope periode/prodi jika nilai dosen dan Pembimbing Lapangan sudah tersedia. |
| Perubahan pembimbing | Koordinator dapat memproses permohonan dalam scope. |
| Master data | Koordinator tidak mengelola master user, prodi, program, periode, dan Konfigurasi Program. |
| Admin global | Tindakan lintas seluruh data berada pada role admin. |

### 3.20 Alur Singkat Koordinator

1. Login ke SiLAT sebagai koordinator.
2. Buka **Dashboard Koordinator**.
3. Periksa scope penugasan periode/prodi.
4. Buka **Validasi Pendaftaran** untuk memproses pengajuan mahasiswa.
5. Buka dokumen bukti akademik sebelum mengambil keputusan.
6. Tetapkan dosen pembimbing dan lengkapi data pembimbing lapangan.
7. Setujui, minta revisi, atau tolak pendaftaran.
8. Buka **Pembekalan** untuk membuat event pembekalan.
9. Tentukan lokasi pembekalan dari peta dan simpan event.
10. Pantau rekap presensi pembekalan.
11. Buka **Review Laporan** untuk meninjau unggahan mahasiswa.
12. Buka **Review Seminar** untuk memvalidasi ACC manual, menjadwalkan seminar, atau memvalidasi nilai manual.
13. Buka **Rekap Monitoring** dan **Peta Monitoring** untuk memantau presensi, durasi, jarak, dan sanksi.
14. Buka **Analisis & Laporan** untuk membaca progres, risiko, heatmap, dan grafik operasional pada scope penugasan.
15. Tindak lanjuti mahasiswa dengan sanksi tinggi atau presensi tidak wajar.

---

## Bagian 4. Role Admin

### 4.1 Ringkasan Hak Akses Admin

Admin adalah role dengan akses paling luas pada SiLAT.

Admin dapat menggunakan sistem untuk:

- Melihat ringkasan manajemen lintas periode dan prodi.
- Mengelola user.
- Mengelola mahasiswa.
- Mengelola dosen.
- Mengelola pembimbing lapangan berbasis email enrollment.
- Mengelola program studi.
- Mengelola program kegiatan.
- Mengelola periode program.
- Mengelola koordinator program.
- Mengelola Viewer Laporan.
- Mengelola master mitra.
- Memvalidasi usulan mitra.
- Memproses pindah tempat.
- Memvalidasi pendaftaran.
- Mengelola peserta periode secara langsung.
- Memproses perubahan pembimbing.
- Mengelola pembekalan program.
- Mereview laporan mahasiswa.
- Mengubah konfigurasi program/periode.
- Mengelola email dan notifikasi.
- Mengelola review seminar, ACC manual, jadwal seminar, dan validasi nilai manual.
- Melihat peta mitra, peta monitoring, dan rekap monitoring.

Admin tidak dibatasi oleh scope periode/prodi seperti koordinator. Karena itu, perubahan admin dapat berdampak ke seluruh data sistem.

### 4.2 Ringkasan Manajemen

Menu: **Ringkasan Manajemen**

Halaman ringkasan menampilkan indikator utama sistem, seperti:

- Jumlah mahasiswa.
- Jumlah dosen.
- Jumlah mitra.
- Jumlah koordinator.
- Jumlah periode.
- Pengajuan pendaftaran yang menunggu validasi.
- Ringkasan pembekalan.
- Akses cepat ke modul manajemen.

Gunakan halaman ini sebagai titik awal untuk memantau kondisi umum sistem.

### 4.3 Manajemen User

Menu: **User**

Admin mengelola akun login sistem.

Data user:

- Nama.
- Email.
- Role.
- Password.
- Foto profil.

Role yang tersedia:

| Role | Fungsi |
|------|--------|
| Admin | Mengelola sistem lintas data. |
| Dosen | Mereview laporan dan memantau mahasiswa bimbingan. |
| Mahasiswa | Mengikuti workflow pendaftaran, presensi, dan laporan. |
| Pembimbing Lapangan | Melihat mahasiswa terkait melalui portal pembimbing lapangan. |

Catatan:

- Role koordinator dihitung dari penugasan koordinator aktif pada data dosen, bukan sekadar role user biasa.
- Jika dosen harus menjadi koordinator, buat/tautkan user dosen terlebih dahulu, lalu buat penugasan pada menu **Koordinator Program**.
- Akses Viewer Laporan juga dihitung dari penugasan aktif pada data dosen, bukan role user dasar. Buat/tautkan user dosen terlebih dahulu, lalu buat penugasan pada menu **Viewer Laporan**.
- Akun Pembimbing Lapangan sebaiknya dibuat/ditautkan dari menu **Pembimbing Lapangan**, bukan langsung dari Manajemen User, agar email akun pasti terkait data pembimbing lapangan pada enrollment aktif.
- Jika memilih role **Mahasiswa** atau **Dosen** dari form User, form menampilkan field profil yang relevan. Email user menjadi email akun login dan dipakai sebagai email profil terkait agar tidak terjadi input email ganda.
- Admin dapat mengunggah atau menghapus foto profil user. Foto ini dipakai sebagai avatar pada navigasi, daftar user, dan tampilan/dokumen yang mendukung foto profil.

### 4.4 Manajemen Mahasiswa

Menu: **Mahasiswa**

Admin dapat menambah dan mengubah data mahasiswa.

Data mahasiswa:

- NPM.
- Nama lengkap.
- Program studi.
- User terkait.
- Email dan kontak jika tersedia pada profil.

Program studi mahasiswa penting karena sistem menggunakan prodi dan `degree_level` untuk menentukan aturan akademik pendaftaran.

Mode akun login:

| Mode | Fungsi |
|------|--------|
| Buat/tautkan otomatis dari email | Sistem mencari akun user dengan email tersebut. Jika belum ada, sistem membuat user role Mahasiswa dan menautkannya ke data mahasiswa. |
| Tautkan akun yang sudah ada | Admin memilih user role Mahasiswa yang sudah tersedia dan belum tertaut ke mahasiswa lain. |
| Belum dihubungkan | Data mahasiswa disimpan tanpa akun login. Mode ini dipakai jika akun akan dibuat belakangan. |

Jika memakai mode otomatis, email login juga menjadi email profil mahasiswa. Dengan cara ini admin cukup mengisi satu pintu data dan tidak perlu membuat user terlebih dahulu lalu kembali menautkannya ke mahasiswa.

### 4.5 Manajemen Dosen

Menu: **Dosen**

Admin dapat menambah dan mengubah data dosen.

Data dosen:

- Nama.
- Email.
- NIP.
- NIDN.
- Program studi.
- User terkait.
- Status aktif/nonaktif.

Dosen yang aktif dapat dipilih sebagai:

- Dosen pembimbing mahasiswa.
- Dosen koordinator program.
- Viewer Laporan.
- Reviewer laporan sesuai relasi bimbingan atau scope koordinator.

Mode akun login pada form Dosen sama seperti Mahasiswa:

| Mode | Fungsi |
|------|--------|
| Buat/tautkan otomatis dari email | Sistem mencari user role Dosen berdasarkan email. Jika belum ada, sistem membuat user dan menautkannya ke data dosen. |
| Tautkan akun yang sudah ada | Admin memilih user role Dosen yang belum tertaut ke dosen lain. |
| Belum dihubungkan | Data dosen disimpan sebagai master tanpa akun login. |

Email pada data dosen menjadi email utama dosen sekaligus acuan akun login saat mode otomatis dipakai.

### 4.6 Manajemen Pembimbing Lapangan

Menu: **Pembimbing Lapangan**

Admin/koordinator dapat melihat daftar pembimbing lapangan yang berasal dari email pembimbing lapangan pada enrollment aktif.

Data yang ditampilkan:

- Nama pembimbing lapangan.
- Email pembimbing lapangan.
- Jumlah mahasiswa/enrollment terkait.
- Status akun login jika sudah ada.
- Status token akses aktif jika tersedia.

Fungsi utama:

- Membuat atau menautkan akun role **Pembimbing Lapangan** berdasarkan email yang sudah tercatat pada enrollment.
- Memastikan akun pembimbing lapangan tidak dibuat untuk email yang tidak terkait enrollment aktif.
- Membantu admin melihat siapa saja pembimbing lapangan yang sudah siap memakai portal.

Catatan:

- Role Pembimbing Lapangan juga dapat dibuat dari Manajemen User, tetapi cara yang direkomendasikan adalah dari menu ini agar tidak ambigu.
- Jika email pembimbing lapangan belum terisi pada peserta, lengkapi melalui **Peserta Periode** atau proses perubahan pembimbing terlebih dahulu.

### 4.7 Manajemen Program Studi

Menu: **Prodi**

Admin mengelola master program studi.

Data prodi:

- Kode.
- Nama prodi.
- Jenjang atau `degree_level`, misalnya D3, S1, S2.
- Organisasi induk bertipe **Jurusan** untuk kebutuhan scope laporan.
- Fakultas diturunkan dari parent organisasi, bukan diisi manual.
- Status aktif/nonaktif.

`degree_level` berpengaruh pada validasi pendaftaran mahasiswa. Organisasi induk dipakai untuk membatasi scope Viewer Laporan pada level universitas, fakultas, atau jurusan.

Aturan hapus:

- Prodi dapat dihapus hanya jika belum dipakai data lain.
- Sistem menolak hapus jika prodi masih dipakai mahasiswa, dosen, enrollment, koordinator, usulan mitra, pembekalan, atau penugasan Viewer Laporan.

Contoh:

| Jenjang | Default Minimal SKS | Default Minimal Semester |
|---------|---------------------|--------------------------|
| D3 | 80 | 4 |
| S1 | 100 | 6 |

Nilai default dapat diubah pada Konfigurasi Program.

### 4.8 Manajemen Program Kegiatan

Menu: **Program Kegiatan**

Admin mengelola jenis program yang dapat diikuti mahasiswa.

Data program:

- Kode.
- Nama program.
- Deskripsi.
- `rule_key`.
- Status aktif/nonaktif.

`rule_key` menentukan aturan workflow akademik yang dipakai sistem. Implementasi saat ini menggunakan rule `kerja_praktik` sebagai rule utama/fallback untuk program yang belum punya rule khusus.

Contoh program:

- Kerja Praktik.
- Magang.
- Riset.
- Studi Independen.

### 4.9 Manajemen Periode Program

Menu: **Periode Program**

Admin mengelola periode pelaksanaan program.

Data periode:

- Program kegiatan.
- Nama periode.
- Tahun akademik.
- Semester.
- Batch.
- Tanggal mulai.
- Tanggal selesai.
- Status aktif.
- Status terkunci.

Keunikan periode dihitung dalam scope program kegiatan. Kombinasi nama periode, tahun akademik, semester, dan batch yang sama boleh dipakai pada program berbeda, tetapi ditolak jika sudah ada pada program yang sama.

Periode menjadi acuan untuk:

- Pendaftaran mahasiswa.
- Konfigurasi presensi.
- Deadline laporan.
- Pembekalan.
- Rekap monitoring.
- Scope koordinator.
- Scope laporan.

Admin juga dapat menyelesaikan periode. Saat periode diselesaikan, peserta aktif pada periode tersebut dapat diubah menjadi selesai sesuai proses yang tersedia pada sistem.

### 4.10 Manajemen Koordinator Program

Menu: **Koordinator Program**

Admin menentukan dosen koordinator untuk periode dan prodi tertentu.

Data koordinator:

- Dosen koordinator.
- Periode program.
- Program studi.
- Status.

Pada form tambah, prodi dapat dipilih lebih dari satu melalui multi-select. Sistem akan membuat penugasan terpisah untuk setiap prodi yang dipilih.

Ketentuan:

- Satu kombinasi periode-prodi hanya boleh memiliki satu koordinator aktif.
- Jika prodi sudah memiliki koordinator pada periode yang sama, sistem menolak duplikasi.
- Koordinator hanya dapat mengakses data pada scope periode/prodi penugasannya.

### 4.10A Viewer Laporan

Menu: **Viewer Laporan**

Viewer Laporan dipakai untuk memberi akses baca Analisis & Laporan kepada dosen yang memiliki kebutuhan pemantauan, misalnya Ketua Jurusan, Sekretaris Jurusan, Ketua Program Studi, atau pimpinan fakultas/universitas.

Prinsip utama:

- Viewer Laporan diambil dari daftar dosen aktif.
- Role utama dosen tidak berubah. Dosen tetap dapat berperan sebagai dosen pembimbing jika memang memiliki mahasiswa bimbingan.
- Akses Viewer Laporan hanya membuka menu **Analisis & Laporan** sesuai scope.
- Viewer Laporan tidak dapat melakukan aksi workflow seperti validasi pendaftaran, memproses Lupa Presensi, review laporan, review seminar, atau finalisasi nilai.
- Penugasan Viewer Laporan dapat dihapus dari kolom aksi.
- Dosen menerima email notifikasi saat ditetapkan atau diperbarui sebagai Viewer Laporan.

Level scope yang tersedia:

| Level | Cakupan |
|-------|---------|
| Universitas | Semua prodi di bawah organisasi universitas yang dipilih. |
| Fakultas | Semua prodi di bawah fakultas dan turunannya. |
| Jurusan | Semua prodi di bawah jurusan. |
| Prodi | Satu prodi spesifik. |

Data penugasan:

- Dosen.
- Level akses.
- Organisasi atau prodi sesuai level.
- Status aktif/nonaktif.
- Tanggal mulai dan selesai, jika masa akses ingin dibatasi.

Seeder awal membuat hirarki:

```text
Universitas Lampung
└── FMIPA
    └── Jurusan Ilmu Komputer
```

Semua prodi yang sudah ada saat ini ditautkan ke **Jurusan Ilmu Komputer**. Jika struktur fakultas/jurusan/prodi berkembang, admin dapat menyesuaikan data organisasi dan relasi prodi.

Aturan organisasi:

- Jenis **Jurusan** hanya dapat memilih parent **Fakultas**.
- Jenis **Fakultas** hanya dapat memilih parent **Universitas**.
- Jenis **Universitas** tidak memakai parent.
- Organisasi tidak dapat dihapus jika masih memiliki child, prodi, atau penugasan Viewer Laporan.

### 4.11 Manajemen Master Mitra

Menu: **Mitra**

Admin mengelola master tempat kegiatan/mitra.

Data mitra:

- Nama instansi.
- Alamat.
- Kota.
- Koordinat latitude dan longitude.
- Kontak/pembimbing lapangan jika tersedia.
- Status aktif/nonaktif.

Fitur lokasi:

- Admin dapat memilih lokasi dari peta.
- Input lokasi memiliki sugest dari riwayat internal.
- Jika sugest internal tidak ditemukan, sistem dapat mengambil sugest eksternal.

Bulk action pada master mitra:

| Aksi | Fungsi |
|------|--------|
| Hapus yang peserta 0 | Menghapus mitra yang tidak memiliki peserta. |
| Merge ke tempat tujuan | Menggabungkan beberapa mitra ke satu master tujuan. |

Gunakan merge untuk merapikan data mitra ganda atau variasi nama instansi yang sama.

### 4.12 Validasi Usulan Mitra

Menu: **Usulan Mitra**

Mahasiswa dapat mengajukan mitra baru. Admin memvalidasi usulan tersebut.

Admin dapat:

- Melihat nama mitra.
- Melihat mahasiswa pengusul.
- Melihat periode dan prodi.
- Melihat alamat, kota, dan koordinat.
- Menyetujui usulan.
- Menolak usulan.
- Memberi catatan admin.

Pilihan saat menyetujui:

| Mode | Fungsi |
|------|--------|
| Jadikan master baru | Usulan dibuat menjadi master mitra baru. |
| Gabungkan ke master | Usulan digabung ke master mitra yang sudah ada. |

Jika ditolak, alasan penolakan wajib diisi agar mahasiswa memahami tindak lanjutnya.

### 4.13 Pindah Mitra

Menu: **Pindah Mitra**

Admin memproses permohonan pindah mitra/tempat kegiatan dari mahasiswa.

Admin dapat:

- Melihat mahasiswa dan enrollment.
- Melihat mitra lama.
- Melihat mitra baru yang diminta.
- Membaca alasan pindah.
- Menyetujui atau menolak permohonan.
- Memberi catatan admin.

Jika disetujui, sistem memperbarui mitra pada enrollment mahasiswa.

### 4.14 Validasi Pendaftaran

Menu: **Validasi Pendaftaran**

Admin dapat memvalidasi pendaftaran mahasiswa lintas semua periode/prodi.

Data yang diperiksa:

- Mahasiswa.
- NPM.
- Prodi.
- Program dan periode.
- Mitra.
- KRS program/KP.
- Total SKS.
- Semester.
- IPK.
- Dokumen bukti akademik.
- Catatan verifikasi.

Halaman Validasi Pendaftaran memakai tabel dengan kolom Program/Periode, Nama/NPM, Prodi, SKS/IPK, Lampiran, Catatan Verifikasi, dan Aksi. Admin dapat memilih beberapa baris dengan checkbox, memakai check/uncheck all, lalu menjalankan bulk action **Setujui**, **Minta Revisi**, atau **Tolak**. Tombol aksi per baris dibuat ringkas dengan ikon Font Awesome.

Kolom pembimbing tidak ditampilkan pada halaman validasi. Penetapan dosen pembimbing, pembimbing lapangan, HP, dan email pembimbing lapangan diproses melalui **Peserta Periode**.

Dokumen bukti akademik yang direview:

```text
Transkrip Sementara + KRS Semester saat ini
```

Keputusan validasi:

| Keputusan | Dampak |
|-----------|--------|
| Setujui | Enrollment menjadi aktif. |
| Minta Revisi | Mahasiswa harus memperbaiki pendaftaran. |
| Tolak | Pendaftaran ditolak. |

Dosen pembimbing wajib dipilih sebelum enrollment diaktifkan.

### 4.15 Peserta Periode

Menu: **Peserta Periode**

Admin dapat menambah dan mengubah enrollment peserta secara langsung.

Fitur penting:

- Mode tambah menggunakan pencarian mahasiswa berdasarkan nama/NPM, bukan dropdown panjang.
- Pada mode edit, mahasiswa tidak dapat diganti.
- Program studi mengikuti prodi mahasiswa.
- Admin memilih periode program, mitra, dosen pembimbing, data pembimbing lapangan, kontak mahasiswa, kelayakan akademik, dan status enrollment.
- Admin dapat membuat token akses pembimbing lapangan jika email pembimbing lapangan sudah terisi.
- Token akses pembimbing lapangan memiliki masa berlaku, dapat dicabut, dan dikirim melalui antrean email.

Status enrollment yang tersedia:

| Status | Arti |
|--------|------|
| Draft | Data awal belum diproses. |
| Menunggu Verifikasi | Menunggu validasi. |
| Perlu Revisi | Mahasiswa perlu memperbaiki data. |
| Aktif | Mahasiswa dapat menjalankan program dan presensi. |
| Nonaktif | Enrollment tidak aktif. |
| Selesai | Program sudah selesai. |
| Batal | Enrollment dibatalkan. |
| Ditolak | Pendaftaran ditolak. |

Gunakan modul ini untuk perbaikan administratif peserta periode, bukan sebagai pengganti workflow pendaftaran mahasiswa jika alur normal masih memungkinkan.

### 4.16 Perubahan Pembimbing

Menu: **Perubahan Pembimbing**

Admin dapat memproses permohonan perubahan pembimbing dari mahasiswa lintas semua periode/prodi.

Data yang dapat diubah:

- Dosen pembimbing.
- Nama pembimbing lapangan.
- HP pembimbing lapangan.
- Email pembimbing lapangan.

Jika permohonan disetujui, data enrollment diperbarui sesuai keputusan admin.

Email pembimbing lapangan penting karena wajib tersedia sebelum mahasiswa mengajukan seminar.

### 4.17 Pembekalan Program

Menu: **Pembekalan**

Admin dapat membuat event pembekalan untuk periode program.

Data event:

- Periode program.
- Program studi, atau semua prodi pada periode.
- Nama lokasi.
- Koordinat lokasi.
- Waktu dibuka.
- Waktu ditutup.
- Radius maksimum presensi.
- Status aktif.

Nama event dibuat otomatis mengikuti program dan periode.

Contoh:

```text
Pembekalan Magang Juli 2026
```

Admin dapat memilih lokasi melalui peta. Mahasiswa yang sesuai dengan event akan melihat link presensi pembekalan pada dashboard.

### 4.18 Rekap Pembekalan

Admin dapat membuka rekap setiap event pembekalan.

Rekap menampilkan:

- Jumlah peserta.
- Jumlah yang sudah presensi.
- Jumlah yang belum presensi.
- Daftar mahasiswa.
- Prodi.
- Status presensi.
- Waktu presensi.
- Jarak mahasiswa dari lokasi pembekalan.

Presensi pembekalan hanya dapat dilakukan satu kali oleh mahasiswa pada event yang sesuai.

### 4.19 Review Laporan

Menu: **Review Laporan**

Admin dapat mereview unggahan laporan mahasiswa lintas semua periode/prodi.

Admin dapat:

- Mencari unggahan berdasarkan mahasiswa, NPM, atau mitra.
- Memfilter status review.
- Membuka file laporan.
- Melihat sanksi keterlambatan.
- Menyetujui dokumen.
- Meminta revisi.
- Menolak dokumen.
- Memberi catatan review.

Catatan wajib diisi jika status review adalah **Minta Revisi** atau **Tolak**.

Dokumen yang sudah disetujui terkunci. Jika dokumen **Laporan Lengkap** disetujui, file disimpan sebagai laporan final pada enrollment.

### 4.20 Review Seminar

Menu: **Review Seminar**

Admin/koordinator dapat memproses sisi administratif workflow seminar.

Fungsi utama:

- Melihat daftar pengajuan seminar.
- Memvalidasi berkas ACC seminar manual yang diunggah mahasiswa.
- Menolak ACC manual jika bukti tidak sesuai dan memberi catatan.
- Menjadwalkan seminar setelah ACC valid, baik ACC via sistem maupun ACC manual.
- Memvalidasi nilai seminar manual yang diinput mahasiswa beserta berkas bukti/form penilaian.

Alur seminar:

1. Mahasiswa mengajukan seminar setelah Laporan Lengkap Bab 1-5 diunggah.
2. ACC seminar dapat berjalan via sistem oleh dosen atau via upload berkas ACC manual oleh mahasiswa.
3. Jika memakai jalur manual, admin/koordinator memvalidasi berkas ACC.
4. Admin/koordinator menjadwalkan seminar.
5. Nilai seminar dapat diisi oleh dosen via sistem atau diinput manual oleh mahasiswa dengan bukti.
6. Jika nilai manual, admin/koordinator memvalidasi komponen nilai dan berkas bukti.

### 4.20A Lupa Presensi

Menu: **Lupa Presensi**

Admin dapat memproses semua pengajuan Lupa Presensi lintas periode/prodi.

Admin dapat:

- Memfilter pengajuan berdasarkan periode dan status.
- Mencari mahasiswa atau NPM.
- Membaca detail tanggal, jam, jenis presensi, catatan, alasan, lokasi, dan bukti foto.
- Mempertimbangkan jarak, akurasi/indikasi audit lokasi, foto bukti, pola pengajuan, dan kuota sebelum menyetujui atau menolak.
- Menyetujui pengajuan.
- Menolak pengajuan.
- Memberi catatan review.

Jika pengajuan disetujui, sistem membuat record presensi koreksi dengan sumber **forgotten_request**. Record ini dipakai pada pasangan presensi, catatan harian, durasi, dan rekap jika aturan pair valid.

### 4.20B Finalisasi Nilai

Menu: **Finalisasi Nilai**

Admin dapat memfinalisasi nilai akhir mahasiswa lintas periode/prodi.

Syarat utama:

- Nilai dosen pembimbing sudah tersedia.
- Nilai Pembimbing Lapangan sudah tersedia.
- Data seminar dan periode program sudah benar.

Alur:

1. Buka **Finalisasi Nilai**.
2. Gunakan filter periode program jika diperlukan.
3. Periksa nilai dosen, nilai Pembimbing Lapangan, nilai dasar, total sanksi, dan suggest pengurangan.
4. Isi nomor berita acara.
5. Isi **Pengurangan Final**. Nilai ini dapat mengikuti suggest sanksi atau disesuaikan berdasarkan keputusan admin/koordinator.
6. Isi catatan admin/koordinator jika perlu.
7. Klik **Simpan Finalisasi**.

Dampak finalisasi:

- Total nilai dan huruf mutu tersimpan.
- Mahasiswa dapat melihat rekap nilai pada tab **Penyelesaian**.
- Tombol cetak Berita Acara Nilai muncul pada tab **Penyelesaian**.
- Nilai dosen dan nilai Pembimbing Lapangan tidak dapat diedit lagi.
- Berita acara memakai Koordinator Periode Program aktif sesuai prodi mahasiswa, bukan konfigurasi manual nama koordinator.

### 4.21 Analisis & Laporan

Menu: **Analisis & Laporan**

Admin, koordinator, dan Viewer Laporan memakai menu ini untuk membaca progres pelaksanaan dalam bentuk indikator, tabel tindak lanjut, dan grafik. Admin melihat data lintas periode/prodi, koordinator hanya melihat data sesuai scope penugasannya, sedangkan Viewer Laporan melihat data sesuai scope organisasi/prodi yang diberikan admin.

Semua halaman laporan memakai subnav berbentuk tab sehingga pengguna dapat berpindah antar laporan tanpa mencari menu sidebar lagi. Sidebar juga menyimpan posisi scroll terakhir pada browser, sehingga setelah halaman refresh pengguna tetap berada di area menu yang sama.

Untuk laporan progres, data utama hanya menghitung peserta dengan status **Aktif** dan **Selesai**. Peserta draft, menunggu verifikasi, perlu revisi, nonaktif, batal, atau ditolak tidak dihitung sebagai progres pelaksanaan aktif.

Halaman yang tersedia:

| Halaman | Fungsi |
|---------|--------|
| Dashboard Progres | Menampilkan kartu ringkasan peserta aktif/selesai, presensi belum lengkap, catatan harian belum divalidasi, laporan terlambat, seminar belum diajukan, nilai belum lengkap, nilai final, dan sanksi tertinggi. |
| Progress Funnel | Menampilkan alur peserta dari pendaftaran disetujui, presensi aktif, laporan lengkap, nilai Pembimbing Lapangan, seminar, nilai dosen, sampai nilai final untuk menemukan bottleneck proses. Tampilan dibuat dua kolom. |
| Risk Scoring | Mengelompokkan peserta menjadi Aman, Perlu Dipantau, Berisiko, atau Kritis berdasarkan indikator presensi, laporan, seminar, nilai, Lupa Presensi, dan sanksi. |
| Heatmap Kehadiran | Menampilkan status kehadiran per mahasiswa dan tanggal, termasuk hadir valid, presensi satu sisi, Lupa Presensi disetujui, akhir pekan, dan hari libur. |
| Grafik Operasional | Menampilkan tren presensi harian, stacked bar status peserta per prodi, donut status laporan lengkap, top sanksi, dan progres nilai dosen/Pembimbing Lapangan/final. |

Filter umum:

- Periode program.
- Program kegiatan.
- Program studi.
- Mitra atau dosen pembimbing jika tersedia pada halaman terkait.
- Status peserta atau kategori risiko.
- Rentang tanggal.

Pada halaman Rekap Monitoring, Rekap Sanksi, Heatmap Kehadiran, dan Grafik Operasional, filter tanggal **Dari** dan **Sampai** otomatis mengikuti rentang presensi periode yang dipilih. Jika hari ini masih sebelum tanggal akhir presensi, tanggal **Sampai** memakai hari ini. Pengguna tetap dapat mengubah tanggal manual sebelum menekan tombol filter.

Gunakan halaman analisis sebagai pintu tindak lanjut. Jika ada mahasiswa berisiko, buka detail/aksi cepat menuju presensi, laporan, seminar, Lupa Presensi, atau finalisasi nilai sesuai masalah utama yang muncul.

Catatan untuk Viewer Laporan:

- Filter periode dan prodi dibatasi oleh scope penugasan viewer.
- Viewer dapat membaca rekap, grafik, heatmap, risk scoring, sanksi, dan nilai akhir sesuai scope.
- Aksi operasional tetap mengikuti role asli pengguna. Jika dosen hanya memiliki akses Viewer Laporan, tautan aksi yang bersifat finalisasi atau validasi tidak diberikan.
- Cetak Berita Acara Nilai dapat dibuka jika data mahasiswa berada dalam scope viewer dan nilai sudah final.

### 4.22 Konfigurasi Program

Menu: **Konfigurasi Program**

Admin mengatur konfigurasi per periode.

Area konfigurasi:

- Umum.
- Pendaftaran dan kuota.
- Kontrol layanan mahasiswa.
- Deadline periode.
- Check-in.
- Laporan dan kalender.
- Komponen penilaian dan survey.
- Dokumen cetak nilai.
- Peta dan lokasi.

Halaman edit konfigurasi memakai tab agar setiap kelompok pengaturan lebih mudah ditemukan.

#### Umum

Konfigurasi umum:

- Timezone.

Default timezone adalah Asia/Jakarta.

#### Pendaftaran dan Kuota

Konfigurasi:

- Kuota minimal per tempat.
- Kuota maksimal per tempat.
- Minimal SKS S1.
- Minimal SKS D3.
- Minimal semester S1.
- Minimal semester D3.
- Minimal IPK.

Dampak:

- Digunakan saat mahasiswa mendaftar program.
- Digunakan untuk validasi kelayakan akademik.
- Kuota maksimal membatasi jumlah mahasiswa pada mitra.
- Kuota minimal dapat muncul sebagai peringatan saat validasi.

#### Kontrol Layanan Mahasiswa

Admin dapat mengaktifkan atau menonaktifkan layanan mahasiswa berikut:

- Usulan mitra.
- Pindah mitra.
- Perubahan pembimbing.

Dampak:

- Jika layanan dimatikan, mahasiswa tidak dapat membuat pengajuan baru untuk layanan tersebut.
- Pendaftaran tetap mengikuti deadline **Pendaftaran Dibuka** dan **Pendaftaran Ditutup**.
- Jika periode program terkunci, layanan usulan mitra, pindah mitra, dan perubahan pembimbing otomatis tertutup untuk mahasiswa.
- Workflow lain seperti presensi, unggah laporan, seminar, dan penyelesaian tidak ikut ditutup oleh toggle ini.

#### Status Periode

Periode memiliki dua status penting:

- **Aktif**: periode tersedia secara operasional dan dapat dipakai oleh peserta yang aktif.
- **Terkunci**: periode sudah dikunci untuk perubahan administratif.

Catatan implementasi:

- Aksi **Set Selesai** pada Periode Program mengubah peserta aktif menjadi **Completed**, menonaktifkan periode, dan mengunci periode.
- Checkbox **Terkunci** pada form edit periode hanya mengubah status periode; peserta aktif tidak otomatis diubah menjadi selesai.
- Jika periode nonaktif tetapi peserta masih active, dashboard mahasiswa menampilkan status efektif **Periode Nonaktif**.

#### Deadline Periode

Admin mengatur deadline untuk:

- Pendaftaran dibuka.
- Pendaftaran ditutup.
- Proposal.
- Bab 1.
- Bab 1 dan 2.
- Bab 1, 2, dan 3.
- Laporan lengkap.
- Seminar.
- Hardcopy.

Setiap deadline memiliki:

- Tanggal.
- Poin sanksi.
- Mode sanksi tetap atau per hari.

Dampak:

- Sistem menghitung sanksi otomatis jika mahasiswa terlambat mengunggah dokumen.

#### Check-In

Konfigurasi check-in:

- Jumlah riwayat check-in yang ditampilkan.
- Maksimal ukuran foto.
- Radius maksimum meter.
- Batas akurasi GPS meter.
- Maksimal umur snapshot lokasi dalam menit.
- Toleransi beda koordinat form dengan snapshot GPS dalam meter.
- Durasi minimal harian dalam menit.
- Sanksi per jam kurang.
- Pesan jam tidak aktif.
- Jadwal status presensi.

Jadwal default:

| Rentang Waktu | Status |
|---------------|--------|
| 07:00 sampai sebelum 08:00 | Masuk |
| 08:00 sampai sebelum 12:00 | Datang Terlambat |
| 12:00 sampai sebelum 16:00 | Pulang Cepat |
| 16:00 sampai sebelum 19:00 | Pulang |

Dampak:

- Menentukan status presensi mahasiswa.
- Menentukan apakah presensi diizinkan pada waktu tertentu.
- Menentukan sanksi jika durasi harian kurang.
- Menentukan batas jarak presensi.

#### Laporan dan Kalender

Konfigurasi:

- Cutoff satu check-in.
- Infer pulang pagi.
- Infer masuk siang.
- Daftar tanggal libur.
- Maksimal pengajuan Lupa Presensi.

Dampak:

- Membantu perhitungan laporan dan rekap.
- Menentukan hari libur yang dapat dikecualikan atau disertakan pada rekap monitoring.
- Menentukan batas pengajuan Lupa Presensi mahasiswa pada periode tersebut.

Catatan:

- Jika **Maksimal pengajuan Lupa Presensi** diisi 0, fitur Lupa Presensi tidak aktif.
- Daftar tanggal libur dapat diganti penuh atau dikosongkan. Jika semua tanggal dihapus lalu konfigurasi disimpan, daftar libur periode menjadi kosong.
- Field **Infer pulang pagi** dan **Infer masuk siang** disimpan sebagai konfigurasi, tetapi aturan inferensi presensi otomatis belum dijadikan dasar utama karena sistem saat ini memakai mekanisme Lupa Presensi dan validasi pair.

#### Komponen Penilaian dan Survey

Konfigurasi:

- Komponen Penilaian Dosen.
- Komponen Penilaian Pembimbing Lapangan.
- Pertanyaan Survey Institusi.

Format pengaturan memakai JSON terstruktur. Gunakan format yang sudah tersedia sebagai acuan dan ubah label, bobot, atau pilihan jawaban dengan hati-hati.

Dampak:

- Rubrik dosen dipakai pada form nilai seminar/laporan.
- Rubrik Pembimbing Lapangan dipakai pada tab **Penilaian dan Feedback** portal Pembimbing Lapangan.
- Survey institusi dipakai untuk feedback institusi/program studi dari Pembimbing Lapangan.
- Saat nilai disimpan, sistem menyimpan snapshot rubrik/survey yang sedang berlaku. Perubahan konfigurasi setelah itu tidak mengubah struktur nilai historis yang sudah tersimpan.

#### Dokumen Cetak Nilai

Konfigurasi:

- Logo/header dokumen.
- Website dan email header.
- Format nomor berita acara.
- Kota tanda tangan.
- Nama dan NIP Ketua Jurusan.

Dampak:

- Digunakan pada cetak Berita Acara Nilai.
- Nama/NIP Koordinator tidak diisi di konfigurasi ini karena berita acara mengambil Koordinator Periode Program aktif sesuai prodi mahasiswa.
- QR verifikasi Berita Acara Nilai dibuat memakai QuickChart. Jika logo/header dokumen tersedia, logo dapat dipakai sebagai gambar tengah QR agar dokumen lebih mudah dikenali dan tetap bisa discan.

#### Peta dan Lokasi

Konfigurasi:

- Radius bumi meter.
- Latitude/longitude tengah peta.
- Zoom default.
- Max zoom.
- Fit max zoom.
- Zoom instansi.
- Zoom lokasi saat ini.
- Limit monitoring default.
- Limit monitoring maksimum.
- Timeout geolocation.
- Cache geolocation.
- High accuracy GPS.
- Tile URL.
- Tile attribution.

Dampak:

- Mengatur tampilan peta.
- Mengatur akurasi dan perilaku geolocation.
- Mengatur perhitungan jarak Haversine.
- Mengatur jumlah data monitoring yang dimuat.

### 4.22 Email & Notifikasi

Menu: **Email & Notifikasi**

Admin mengelola pengiriman email sistem dan konfigurasi mail server.

Bagian atas halaman menampilkan ringkasan 4 kolom: status sistem, jumlah email pending, jumlah email terkirim, dan jumlah email gagal.

Tab yang tersedia:

| Tab | Fungsi |
|-----|--------|
| Status Notifikasi | Mengaktifkan/menonaktifkan pengiriman email global dan cakupan workflow email. |
| Antrean Email | Melihat email pending, terkirim, gagal, error pengiriman, attempt, serta menjalankan proses antrean atau retry gagal. |
| Mail Server | Menyimpan override konfigurasi mail server seperti mailer, SMTP host, port, enkripsi, username, password, email pengirim, dan nama pengirim. |

Kontrol global:

- Jika **Email notifikasi aktif** dimatikan, event email tetap berada di antrean tetapi tidak dikirim.
- Cocok digunakan saat SMTP bermasalah, masa uji coba, atau maintenance.

Cakupan notifikasi:

- Admin dapat mengaktifkan/menonaktifkan workflow yang boleh membuat antrean email baru.
- Workflow yang sudah tersedia: pendaftaran, usulan mitra, pindah mitra, perubahan pembimbing, pembimbing lapangan, pembekalan, digest presensi, laporan, penilaian, dan operasional.
- Pembekalan mengirim email saat event dibuka, reminder sebelum kegiatan, reminder mendekati waktu tutup jika belum presensi, konfirmasi presensi berhasil, dan rekap hadir/tidak hadir kepada admin/koordinator setelah event ditutup.
- Digest presensi dikirim mingguan, bukan setiap check-in/check-out. Mahasiswa menerima ringkasan presensi pribadi, sedangkan dosen pembimbing dan koordinator menerima daftar mahasiswa dengan pola presensi yang perlu perhatian.
- Laporan mengirim email upload berhasil, hasil review, reminder deadline H-7/H-3/H-1/hari H, reminder review dosen, summary mahasiswa bimbingan untuk dosen, dan rekap admin/koordinator untuk laporan kosong, pending review, serta sanksi tertinggi.
- Summary laporan untuk admin, koordinator, dan dosen pembimbing hanya dibuat setelah periode mencapai tanggal **Mulai Pelaksanaan / Presensi**. Periode masa depan belum memicu summary laporan walaupun deadline sudah dikonfigurasi.
- Pembimbing Lapangan mengirim email token akses, token pengganti jika token lama kedaluwarsa, reminder validasi catatan harian, reminder pengajuan Lupa Presensi pending, reminder H-7/H-3/H-1 sebelum deadline Laporan Lengkap untuk validasi catatan dan nilai, reminder pengisian nilai, konfirmasi nilai tersimpan, dan alert admin/koordinator untuk nilai/token yang perlu ditindaklanjuti.
- Penilaian mengirim email kepada dosen saat seminar dijadwalkan, reminder nilai seminar yang belum diisi, konfirmasi nilai tersimpan, serta alert admin/koordinator ketika nilai siap finalisasi atau belum lengkap mendekati akhir periode.
- Operasional mengirim email kepada admin untuk perubahan periode, penguncian/penyelesaian periode, perubahan konfigurasi program, hasil import Firebase, kegagalan import, dan digest email gagal.
- Workflow yang belum tersedia tampil sebagai referensi dan checkbox-nya tidak dapat diaktifkan.

Mail server:

- Konfigurasi mail server disimpan di database pada tabel `system_settings`, bukan menulis langsung ke `.env`.
- Jika belum ada konfigurasi database, sistem memakai konfigurasi dari `.env`.
- Password SMTP disimpan terenkripsi.

### 4.24 Cara Kerja Presensi dan Jarak dari Sisi Admin

Admin perlu memahami cara sistem menghitung presensi.

Presensi harian:

- Mahasiswa melakukan presensi masuk dan pulang.
- Sistem memasangkan presensi berdasarkan tanggal dan enrollment.
- Presensi pulang tanpa presensi masuk dapat tersimpan sebagai data belum berpasangan, tetapi tidak dihitung hadir efektif.
- Satu hari hadir dihitung jika ada masuk dan pulang.
- Jika hanya ada satu presensi dalam satu hari, hari tersebut tidak dihitung sebagai hari hadir efektif.
- Durasi dihitung dari pulang dikurangi masuk.
- Presensi hasil Lupa Presensi yang disetujui diperlakukan sebagai presensi koreksi dan ikut dihitung jika pasangan harian valid.

Validasi lokasi presensi harian:

- Browser mengirim snapshot GPS ke server melalui endpoint khusus sebelum presensi disimpan.
- Submit presensi memakai `location_sample_id`; server memakai koordinat snapshot sebagai sumber lokasi final.
- Field koordinat pada form hanya menjadi tampilan dan pembanding audit, bukan sumber utama yang dipercaya.
- Jika koordinat form berbeda jauh dari snapshot GPS, presensi ditolak.
- Jika snapshot GPS berada di luar radius mitra, presensi ditolak.
- Jika akurasi snapshot GPS rendah atau mendekati radius, presensi dapat disimpan dengan status audit lokasi.

Rumus durasi:

```text
Durasi harian = waktu pulang - waktu masuk
```

Sanksi durasi:

```text
Sanksi = pembulatan ke atas((durasi minimal - durasi aktual) / 60 menit) x poin per jam kurang
```

Jarak:

- Sistem memakai rumus Haversine.
- Radius bumi default 6.371.000 meter.
- Jarak presensi harian dihitung dari lokasi mahasiswa ke lokasi mitra.
- Jarak pembekalan dihitung dari lokasi mahasiswa ke lokasi pembekalan.

Jika radius maksimum diisi 0, pembatasan radius dapat dianggap tidak aktif.

### 4.25 Peta dan Rekap Monitoring

Admin dapat membuka:

- **Peta Mitra**
- **Peta Monitoring**
- **Rekap Monitoring**

Peta Mitra:

- Menampilkan sebaran mitra.
- Membantu melihat lokasi dan distribusi tempat kegiatan.

Peta Monitoring:

- Menampilkan lokasi presensi mahasiswa.
- Membandingkan lokasi mahasiswa dengan lokasi mitra.
- Membantu mendeteksi presensi yang jauh dari lokasi mitra.

Rekap Monitoring:

- Menampilkan jumlah mahasiswa.
- Total hari hadir.
- Total durasi.
- Rata-rata jarak.
- Rentang jam masuk.
- Rentang jam pulang.
- Filter periode, program, prodi, tanggal, Sabtu, Minggu, dan hari libur.

Ekspor PDF/Excel pada halaman rekap masih dalam status belum aktif jika tombol tampil disabled.

### 4.26 Batasan dan Kehati-hatian Admin

| Area | Batasan/Kehati-hatian |
|------|-----------------------|
| User | Perubahan role berdampak ke akses sistem. |
| Prodi | `degree_level` memengaruhi validasi akademik mahasiswa. Hapus prodi dibatasi restrict jika masih dipakai data lain. |
| Organisasi | Struktur organisasi menentukan scope Viewer Laporan. Hapus organisasi dibatasi restrict jika masih memiliki child, prodi, atau penugasan Viewer Laporan. |
| Program | `rule_key` memengaruhi aturan workflow program. |
| Periode | Periode menjadi acuan pendaftaran, deadline, pembekalan, monitoring, dan scope laporan. Kombinasi nama/tahun akademik/semester/batch unik dalam program yang sama. |
| Koordinator | Satu periode-prodi hanya boleh satu koordinator aktif. |
| Viewer Laporan | Beri scope paling kecil yang dibutuhkan karena akses ini membuka data Analisis & Laporan sesuai organisasi/prodi. |
| Mitra | Koordinat mitra menjadi acuan jarak presensi harian. |
| Merge mitra | Pastikan tujuan merge benar karena enrollment akan merujuk ke master tujuan. |
| Konfigurasi SKS/IPK | Langsung memengaruhi pendaftaran mahasiswa. |
| Konfigurasi radius | Langsung memengaruhi diterima/ditolaknya presensi. |
| Konfigurasi deadline | Langsung memengaruhi sanksi keterlambatan laporan. |
| Konfigurasi Lupa Presensi | Langsung memengaruhi batas pengajuan mahasiswa; nilai 0 menonaktifkan fitur. |
| Konfigurasi rubrik penilaian | Perubahan hanya memengaruhi nilai baru. Nilai yang sudah tersimpan memakai snapshot rubrik/survey saat penilaian dilakukan. |
| Finalisasi nilai | Mengunci nilai dosen dan nilai Pembimbing Lapangan serta membuka cetak berita acara nilai untuk mahasiswa. |
| Status peserta nonaktif | Enrollment nonaktif tidak mengikuti workflow operasional, tidak dianggap program aktif, dan tidak dihitung pada progres peserta aktif/selesai. Data historis tetap tersimpan untuk audit. |
| Email & Notifikasi | Toggle global menghentikan pengiriman, sedangkan toggle cakupan menghentikan pembuatan antrean baru untuk workflow terkait. |
| Mail Server | Override database dipakai saat antrean email diproses; jika kosong sistem memakai `.env`. |
| Dokumen disetujui | Dokumen laporan yang disetujui terkunci. |
| Periode terkunci | Konfigurasi periode terkunci hanya dapat diubah oleh admin khusus/super admin. |

### 4.27 Alur Operasional Admin

Alur awal setup:

1. Buat atau periksa user admin, dosen, dan mahasiswa.
2. Lengkapi master prodi beserta `degree_level`.
3. Lengkapi master dosen.
4. Lengkapi master program kegiatan.
5. Buat periode program.
6. Atur konfigurasi periode melalui **Konfigurasi Program**.
7. Tambahkan master mitra jika sudah tersedia.
8. Buat penugasan koordinator program.
9. Buat penugasan **Viewer Laporan** jika pimpinan prodi/jurusan/fakultas/universitas perlu akses baca Analisis & Laporan.
10. Periksa **Email & Notifikasi** jika sistem akan mengirim email otomatis.

Alur pendaftaran:

1. Mahasiswa mendaftar program.
2. Admin/koordinator membuka **Validasi Pendaftaran**.
3. Periksa kelayakan akademik dan dokumen bukti.
4. Tetapkan dosen pembimbing.
5. Lengkapi data pembimbing lapangan jika perlu.
6. Setujui, minta revisi, atau tolak.

Alur pelaksanaan:

1. Admin/koordinator membuat event pembekalan.
2. Mahasiswa melakukan presensi pembekalan.
3. Mahasiswa melakukan presensi harian.
4. Mahasiswa mengunggah progres laporan.
5. Dosen/koordinator/admin mereview laporan.
6. Admin/koordinator mengirim token akses pembimbing lapangan jika diperlukan.
7. Mahasiswa mengajukan seminar setelah Laporan Lengkap Bab 1-5 diunggah.
8. Dosen/admin/koordinator memproses ACC, jadwal, dan nilai seminar sesuai jalur sistem atau manual.
9. Pembimbing Lapangan memvalidasi catatan harian, memproses Lupa Presensi jika ada, dan mengisi nilai lapangan.
10. Admin/koordinator memproses pengajuan Lupa Presensi yang belum diproses Pembimbing Lapangan jika diperlukan.
11. Admin memantau rekap monitoring, dashboard analisis, sanksi, pembekalan, seminar, dan antrean email.

Alur penutupan:

1. Pastikan laporan final dan hardcopy sudah sesuai.
2. Pastikan nilai dosen dan nilai Pembimbing Lapangan sudah lengkap.
3. Lakukan finalisasi nilai dan isi nomor berita acara.
4. Pastikan mahasiswa dapat melihat nilai akhir dan mencetak Berita Acara Nilai.
5. Selesaikan periode jika seluruh proses sudah selesai.
6. Gunakan rekap monitoring sebagai arsip evaluasi periode.

---

## Bagian 5. Role Pembimbing Lapangan

### 5.1 Ringkasan Hak Akses Pembimbing Lapangan

Pembimbing Lapangan dapat menggunakan SiLAT untuk melihat mahasiswa yang terkait dengan email pembimbing lapangan pada enrollment aktif.

Akses tersedia melalui dua cara:

- URL token akses yang dikirim oleh admin/koordinator.
- Login dengan akun role **Pembimbing Lapangan** jika email login sama dengan email pembimbing lapangan pada enrollment aktif.

### 5.2 Akses Melalui Token URL

Admin/koordinator dapat membuat token akses dari data peserta periode.

Ketentuan token:

- Token bersifat unik.
- Token memiliki masa berlaku.
- Token dapat dicabut oleh admin.
- Token hanya membuka data mahasiswa/enrollment yang terkait dengan token tersebut.
- Link token dikirim melalui antrean email sistem jika email notifikasi aktif.

Langkah penggunaan:

1. Buka email akses dari SiLAT.
2. Klik tautan portal pembimbing lapangan.
3. Periksa data mahasiswa yang tampil.
4. Gunakan informasi presensi dan catatan harian sebagai bahan monitoring.

### 5.3 Akses Melalui Login

Jika akun Pembimbing Lapangan sudah dibuat, pembimbing dapat login menggunakan email yang sama dengan email pembimbing lapangan pada enrollment.

Sistem hanya menampilkan mahasiswa yang memiliki `field_supervisor_email` sama dengan email akun login.

Jika tidak ada mahasiswa yang tampil:

- Pastikan email login sama dengan email pembimbing lapangan pada data peserta.
- Hubungi admin/koordinator untuk mengecek data pembimbing lapangan.
- Pastikan enrollment mahasiswa masih aktif.

### 5.4 Dashboard Pembimbing Lapangan

Dashboard Pembimbing Lapangan menampilkan ringkasan mahasiswa yang terkait dengan email pembimbing lapangan pada enrollment.

Informasi utama:

- Periode aktif.
- Periode selesai.
- Jumlah mahasiswa bimbingan.
- Status pengisian nilai.
- Akses ke daftar **Mahasiswa Bimbingan**.

Menu **Mahasiswa Bimbingan** menampilkan mahasiswa terkait. Pada detail mahasiswa, Pembimbing Lapangan melihat dua tab utama:

| Tab | Fungsi |
|-----|--------|
| Catatan Harian | Melihat presensi masuk/pulang, durasi, jarak, rencana, realisasi, status validasi, tombol validasi catatan harian, dan blok pengajuan Lupa Presensi yang masih pending. |
| Penilaian dan Feedback | Mengisi nilai Pembimbing Lapangan, catatan untuk mahasiswa, rekomendasi mahasiswa, serta feedback untuk institusi/program studi. |

Pada tab **Catatan Harian**, Pembimbing Lapangan dapat memvalidasi satu baris melalui tombol **Validasi** atau memilih beberapa baris dengan checkbox lalu klik **Validasi Terpilih** untuk validasi massal.

Jika foto presensi tersedia, baris catatan harian menampilkan bagian **Foto audit presensi** untuk membuka foto masuk dan/atau pulang. Pengajuan Lupa Presensi pending juga menampilkan **Foto bukti Lupa Presensi** jika mahasiswa mengirim foto bukti.

Validasi Lupa Presensi untuk Pembimbing Lapangan berada di:

```text
Mahasiswa Bimbingan -> buka mahasiswa -> tab Catatan Harian -> Pengajuan Lupa Presensi
```

Blok **Pengajuan Lupa Presensi** hanya muncul jika ada pengajuan dengan status menunggu review. Pembimbing Lapangan dapat memilih **Setujui** atau **Tolak** dan memberi catatan review. Saat memproses, Pembimbing Lapangan sebaiknya memeriksa jarak, foto bukti, akurasi/indikasi audit lokasi jika tersedia, catatan aktivitas, dan alasan lupa.

Catatan:

- Approval Lupa Presensi hanya ditampilkan pada akses login Pembimbing Lapangan.
- Akses melalui token URL dapat dipakai untuk monitoring dan validasi catatan harian, tetapi tidak menampilkan aksi approval Lupa Presensi.
- Setelah nilai akhir mahasiswa difinalisasi admin/koordinator, form nilai Pembimbing Lapangan terkunci dan tidak dapat diedit.

### 5.5 Batasan Role Pembimbing Lapangan

| Area | Batasan |
|------|---------|
| Scope data | Hanya data mahasiswa yang email pembimbing lapangannya sama dengan email token/login. |
| Token | Token dapat kedaluwarsa atau dicabut admin. |
| Login | Email login harus cocok dengan email pembimbing lapangan pada enrollment aktif. |
| Validasi catatan harian | Aktif pada portal login dan token sesuai izin rute. |
| Lupa Presensi | Approval Lupa Presensi hanya tampil pada portal login Pembimbing Lapangan, bukan pada akses token. |
| Penilaian lapangan | Aktif sampai nilai akhir difinalisasi. Setelah final, nilai terkunci. |

### 5.6 Alur Singkat Pembimbing Lapangan

1. Terima email akses atau login dengan akun pembimbing lapangan.
2. Buka portal Pembimbing Lapangan.
3. Periksa daftar mahasiswa terkait.
4. Buka mahasiswa pada menu **Mahasiswa Bimbingan**.
5. Validasi catatan harian pada tab **Catatan Harian**.
6. Proses pengajuan Lupa Presensi yang muncul pada tab **Catatan Harian** jika ada.
7. Isi nilai dan feedback pada tab **Penilaian dan Feedback** setelah periode presensi selesai.
8. Hubungi admin/koordinator jika data mahasiswa tidak sesuai.
