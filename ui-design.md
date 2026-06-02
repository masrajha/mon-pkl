# Rancangan UI/UX Sistem Monitoring MBKM dan Kerja Praktik (SiLAT)

**Platform:** Web responsif  
**Framework Frontend:** Laravel Blade + Tailwind CSS + Alpine.js (interaktivitas ringan)  
**Peta:** Leaflet.js + OpenStreetMap  
**Ikon:** Font Awesome 6 (Free)  
**Rebranding:** Dari Mon PKL menjadi **Monitoring MBKM dan Kerja Praktik** (SiLAT)

---

## Daftar Isi

1. [Prinsip Desain Umum](#1-prinsip-desain-umum)
2. [Layout Dasar (Semua Role)](#2-layout-dasar-semua-role)
3. [Komponen Reusable](#3-komponen-reusable)
4. [Role Admin](#4-role-admin)
5. [Role Koordinator MBKM/PKL](#5-role-koordinator-mbkm-pkl)
6. [Role Dosen Pembimbing](#6-role-dosen-pembimbing)
7. [Role Mahasiswa](#7-role-mahasiswa)
8. [Pedoman Warna & Ikon Dashboard](#8-pedoman-warna--ikon-dashboard)
9. [Alur Pengguna (User Journey)](#9-alur-pengguna-user-journey)
10. [Implementasi Tailwind – Tips Praktis](#10-implementasi-tailwind--tips-praktis)
11. [Tahapan Implementasi Redesain UI](#11-tahapan-implementasi-redesain-ui)

---

## 1. Prinsip Desain Umum

- **Responsif** – Breakpoint Tailwind: `sm` (640px), `md` (768px), `lg` (1024px), `xl` (1280px)
- **Konsisten** – Sidebar tetap, navbar atas, kartu, tabel, form, tombol.
- **Warna utama (brand)** – Biru tua `#1E3A8A` (bg-blue-900) untuk sidebar, aksen biru muda `#3B82F6` (bg-blue-500) untuk tombol primer.
- **Kontras** – Latar belakang abu-abu terang (`#F3F4F6`), kartu putih.
- **Tipografi** – Keluarga font `Inter`, `sans-serif` (default Tailwind).
- **Ikon** – Font Awesome 6 (solid, regular, brands) untuk navigasi dan aksi.

---

## 2. Layout Dasar (Semua Role)

Layout tetap dengan sidebar dan navbar atas.

```
+--------------------------------------------------+
|  Navbar Atas (logo, judul halaman, user menu)   |
+--------+-----------------------------------------+
|        |                                         |
| Sidebar|  Konten Utama                          |
| (menu  |  - Breadcrumb (opsional)               |
|  role) |  - Filter bar (jika ada)               |
|        |  - Card dashboard / Tabel / Form / Peta |
|        |                                         |
+--------+-----------------------------------------+
```

### Implementasi Tailwind

```html
<!-- Sidebar fixed -->
<aside class="fixed top-0 left-0 w-64 h-full bg-blue-900 text-white shadow-lg z-10">
  <!-- Logo area -->
  <div class="p-4 border-b border-blue-800">
    <h1 class="text-xl font-bold">SiLAT</h1>
    <p class="text-xs text-blue-300">Monitoring MBKM & KP</p>
  </div>
  <!-- Menu -->
  <nav class="mt-6">
    <ul class="space-y-1">
      <li><a href="#" class="flex items-center px-4 py-2 hover:bg-blue-800 rounded"><i class="fas fa-tachometer-alt w-5"></i><span class="ml-3">Dashboard</span></a></li>
      <!-- menu lain -->
    </ul>
  </nav>
</aside>

<!-- Navbar atas -->
<header class="fixed top-0 left-64 right-0 h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
  <div class="text-gray-600">Halo, Admin</div>
  <div class="flex items-center gap-4">
    <i class="fas fa-bell text-gray-500"></i>
    <div class="flex items-center gap-2">
      <img src="avatar.jpg" class="w-8 h-8 rounded-full">
      <span class="text-sm">Admin</span>
    </div>
  </div>
</header>

<!-- Konten utama -->
<main class="ml-64 pt-16 p-6 bg-gray-100 min-h-screen">
  <!-- breadcrumb -->
  <div class="text-sm text-gray-500 mb-4">Dashboard / ...</div>
  <!-- konten spesifik -->
</main>
```

---

## 3. Komponen Reusable

### 3.1 DataTable (dengan filter, sorting, pagination)
- Menggunakan Alpine.js atau Livewire untuk interaktivitas.
- Kelas Tailwind: `table-auto w-full text-left border-collapse`
- Header: `bg-gray-50 border-b`
- Baris: `hover:bg-gray-100`

### 3.2 Modal Form
- Overlay: `fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-20`
- Container: `bg-white rounded-lg shadow-xl max-w-md w-full p-6`

### 3.3 Notifikasi Toast
- Posisi: `fixed bottom-4 right-4 z-30`
- Warna sukses: `bg-green-500 text-white px-4 py-2 rounded shadow`

### 3.4 Kartu Statistik (Card)
- Struktur umum:
```html
<div class="bg-white rounded-lg shadow p-5 flex items-center justify-between">
  <div><p class="text-gray-500 text-sm">Judul</p><p class="text-2xl font-bold">Nilai</p></div>
  <div class="rounded-full p-3 text-white"><i class="..."></i></div>
</div>
```

### 3.5 Form Input
- Kelas global (dapat menggunakan `@apply` di CSS):
```css
.input-form { @apply w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500; }
```

### 3.6 Peta Leaflet
- Container: `<div id="map" class="h-96 w-full rounded-lg shadow-md z-0"></div>`

---

## 4. Role Admin

Admin memiliki akses penuh ke seluruh data dan konfigurasi.

### 4.1 Halaman Dashboard Admin

**Komponen:**
- Baris kartu statistik (4 kartu)
- Grafik jumlah check-in per hari (Chart.js sederhana)
- Tabel pendaftaran pending (5 baris terbaru) dengan aksi cepat

**Desain Kartu Dashboard Admin**

| Card | Warna (lingkaran ikon) | Ikon Font Awesome | Default Value |
|------|------------------------|-------------------|----------------|
| Total Mahasiswa MBKM/PKL | `bg-blue-600` | `fa-users` | 156 |
| Total Dosen Pembimbing | `bg-green-600` | `fa-chalkboard-user` | 32 |
| Total Mitra (Tempat) | `bg-yellow-500` | `fa-building` | 48 |
| Rata-rata Kehadiran Hari Ini | `bg-purple-600` | `fa-calendar-check` | 87% |

```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
  <!-- Card 1 -->
  <div class="bg-white rounded-lg shadow p-6 flex items-center justify-between">
    <div><p class="text-gray-500 text-sm">Total Mahasiswa MBKM/PKL</p><p class="text-2xl font-bold">156</p></div>
    <div class="bg-blue-600 rounded-full p-3 text-white"><i class="fas fa-users text-xl"></i></div>
  </div>
  <!-- Card 2 ... -->
</div>
```

**Card Tambahan – Pendaftaran Perlu Validasi**
- Warna latar putih, border kiri merah.
```html
<div class="bg-white rounded-lg shadow p-5 mt-6 border-l-4 border-red-500">
  <div class="flex items-center gap-2"><i class="fas fa-hourglass-half text-red-500"></i><h3 class="font-semibold">Pendaftaran Perlu Validasi</h3><span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">5 baru</span></div>
  <!-- tabel singkat -->
</div>
```

### 4.2 Halaman Master Program Kegiatan
- Tabel daftar program kegiatan dengan kolom: Nama Program, Kode, Deskripsi, Status, Rule Aktif, Jumlah Periode, dan Aksi.
- Contoh data awal: **Kerja Praktik**, **Magang**, **Riset**, **Studi Independen**, **Proyek Kemanusiaan**, atau program MBKM lain yang digunakan fakultas.
- Tombol "Tambah Program" membuka form berisi nama program, kode singkat, deskripsi, status aktif, dan pilihan rule.
- Rule default sementara menggunakan **Rule Kerja Praktik** agar program baru dapat langsung dipakai tanpa mengubah workflow yang sudah ada.
- Sediakan label informasi pada kolom rule, misalnya `Rule Kerja Praktik (default)`, agar admin memahami bahwa aturan dapat dibedakan pada pengembangan berikutnya.
- Aksi: edit, aktif/nonaktif, lihat periode terkait, dan konfigurasi rule jika modul rule per program sudah tersedia.

### 4.3 Halaman Manajemen Periode
- Form periode wajib memilih **Program Kegiatan** agar satu periode selalu terikat pada program seperti Kerja Praktik, Magang, atau Riset.
- Tabel daftar periode dengan aksi edit, set aktif, kunci.
- Tombol “Tambah Periode” (modal form).
- Kolom aksi: <i class="fas fa-edit"></i>, <i class="fas fa-lock"></i>, <i class="fas fa-cog"></i> (konfigurasi).

### 4.4 Halaman Konfigurasi Periode (Tabbed Form)
- Tab **Umum** harus memuat pilihan program kegiatan.
- Tambahkan tab atau panel **Rule Program** untuk menampilkan rule yang dipakai. Pada fase awal seluruh program memakai Rule Kerja Praktik; desain UI harus siap untuk override rule per program di fase berikutnya.
Tab:
1. **Umum** – nama, tanggal, status
2. **Jam Kerja & Check-in** – daftar rentang jam (repeatable)
3. **Durasi & Jarak** – input number
4. **Deadline** – tabel period_deadlines (inline edit)
5. **Bobot Nilai & Sanksi** – slider / input
6. **Peta & Upload** – koordinat, tile URL, batas foto

### 4.5 Manajemen Tempat MBKM
- Tabel dengan kolom: Nama, Alamat, Kota, Koordinat, Jumlah Peserta, Status
- **Bulk action** dropdown: Hapus, Merge.
- Tombol Tambah: form dengan peta picker Leaflet.

### 4.6 Validasi Pendaftaran
- Tabel validasi menampilkan kolom Program agar admin/koordinator dapat membedakan pendaftaran Kerja Praktik, Magang, Riset, atau program lain.
- Tabel enrollment status `pending_verification`.
- Setiap baris: NPM, Nama, Prodi, Tempat, Dosen Pembimbing (dropdown), Pembimbing Lapangan (input text).
- Tombol: Setujui (hijau), Revisi (kuning), Tolak (merah).

### 4.7 Laporan (Rekap)
- Filter laporan wajib menyediakan pilihan program selain periode, prodi, dan tanggal.
- Tabs: Rekap Monitoring, Rekap Pelanggaran, Rekap Nilai Akhir.
- Filter periode, prodi, tanggal.
- Tombol Ekspor PDF/Excel.

---

## 5. Role Koordinator MBKM/PKL

Koordinator adalah dosen yang ditugaskan untuk periode & prodi tertentu.

### 5.1 Dashboard Koordinator

**Kartu statistik (4 kartu)**

| Card | Warna lingkaran | Ikon FA |
|------|----------------|---------|
| Mahasiswa Terdaftar | `bg-cyan-700` | `fa-user-graduate` |
| Check-in Hari Ini | `bg-emerald-500` | `fa-fingerprint` |
| Laporan Lengkap Selesai | `bg-amber-500` | `fa-file-alt` |
| Total Sanksi (poin) | `bg-rose-600` | `fa-gavel` |

```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
  <div class="bg-white rounded-lg shadow p-5 flex items-center gap-4">
    <div class="bg-cyan-700 rounded-full p-3 text-white"><i class="fas fa-user-graduate text-xl"></i></div>
    <div><p class="text-gray-500 text-sm">Mahasiswa Terdaftar</p><p class="text-2xl font-bold">42</p></div>
  </div>
  <!-- ... -->
</div>
```

**Card Daftar Mahasiswa dengan Sanksi Tertinggi**
- Warna latar putih, border atas merah.
```html
<div class="bg-white rounded-lg shadow p-5 mt-6 border-t-4 border-red-500">
  <div class="flex items-center gap-2"><i class="fas fa-triangle-exclamation text-red-500"></i><h3>Mahasiswa Sanksi Tertinggi</h3></div>
  <ul>...</ul>
</div>
```

### 5.2 Halaman Validasi Pendaftaran (terbatas periode-prodi)
Sama seperti admin namun otomatis terfilter.

### 5.3 Monitoring Progres Mahasiswa
Tabel kolom: NPM, Nama, Status Laporan (ikon), Total Sanksi, Nilai Sementara.
Klik baris → detail unggahan dan catatan dosen.

### 5.4 Peta Monitoring
Menampilkan semua mahasiswa di periode/prodi tugas. Sama dengan peta dosen.

---

## 6. Role Dosen Pembimbing

### 6.1 Dashboard Dosen

**Kartu statistik (3 kartu)**

| Card | Warna | Ikon FA |
|------|-------|---------|
| Mahasiswa Bimbingan | `bg-blue-600` | `fa-chalkboard-user` |
| Perlu Bimbingan (revisi) | `bg-orange-500` | `fa-pen-ruler` |
| Seminar Terdekat (hari) | `bg-indigo-500` | `fa-calendar-day` |

```html
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
  <div class="bg-white rounded-lg shadow p-5 flex items-center gap-4">
    <div class="bg-blue-600 rounded-full p-3 text-white"><i class="fas fa-chalkboard-user text-xl"></i></div>
    <div><p>Mahasiswa Bimbingan</p><p class="text-2xl font-bold">8</p></div>
  </div>
  <!-- ... -->
</div>
```

**Card Daftar Mahasiswa Bimbingan (tabel ringkas)**
- Kolom: NPM, Nama, Status Laporan terakhir, Aksi “Beri Catatan” (ikon komentar).

### 6.2 Halaman Detail Bimbingan
- Profil mahasiswa dan tempat PKL.
- Timeline unggahan: daftar submission_progress dengan status, link unduh, form catatan dan status (setujui/revisi).
- Tab Nilai: input nilai laporan & seminar, tombol simpan.

### 6.3 Peta Monitoring
Menampilkan mahasiswa bimbingan saja, polyline lokasi mahasiswa ke instansi.

---

## 7. Role Mahasiswa

### 7.1 Dashboard Mahasiswa

**Hero Card – Check-in Hari Ini (gradien hijau)**
```html
<div class="bg-gradient-to-r from-green-500 to-green-700 rounded-lg shadow p-6 text-white">
  <div class="flex justify-between">
    <div>
      <p class="text-green-100">Selasa, 3 Juni 2025</p>
      <h3 class="text-2xl font-bold">Check-in MBKM/PKL</h3>
      <p>Durasi saat ini: 6 jam 15 menit</p>
      <div class="flex gap-3 mt-4">
        <button class="bg-white text-green-700 px-5 py-2 rounded-lg"><i class="fas fa-right-to-bracket mr-2"></i> Masuk</button>
        <button class="bg-white text-green-700 px-5 py-2 rounded-lg"><i class="fas fa-right-from-bracket mr-2"></i> Pulang</button>
      </div>
    </div>
    <i class="fas fa-location-dot text-6xl opacity-50"></i>
  </div>
</div>
```

**Ringkasan card kecil (3 card)**
| Card | Warna (bg + text) | Ikon FA |
|------|--------------------|---------|
| Hari Hadir | `bg-sky-100 text-sky-800` | `fa-calendar-check` |
| Total Sanksi (poin) | `bg-rose-100 text-rose-800` | `fa-exclamation-triangle` |
| Deadline Terdekat | `bg-amber-100 text-amber-800` | `fa-hourglass-start` |

```html
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <div class="bg-sky-100 rounded-lg p-4 flex items-center gap-3">
    <i class="fas fa-calendar-check text-sky-700"></i><div><p class="text-sm">Hari Hadir</p><p class="font-bold text-xl">22</p></div>
  </div>
  <!-- ... -->
</div>
```

**Card Progres Laporan**
- Progress bar Tailwind, ikon `fa-book-open`, persentase, link ke detail unggahan.

### 7.2 Halaman Pendaftaran MBKM/PKL (Multi-step)
Step 1: Pilih program kegiatan dan periode aktif.  
Step 2: Pilih tempat (dengan info kuota) atau usulkan baru.  
Step 3: Isi data pembimbing lapangan, kontak.  
Step 4: Konfirmasi rule yang berlaku, ringkasan data, lalu kirim.

### 7.3 Halaman Check-in
- Layout 2 kolom: kanan form (30%), kiri peta Leaflet (70%).
- Form: catatan, upload foto, tombol kirim.
- Peta menampilkan marker lokasi user, marker tempat PKL, polyline jarak.

### 7.4 Halaman Progres Laporan
- Daftar deadline jenis, status unggah, tombol upload.
- Jika sudah unggah: link unduh, catatan dosen, form upload revisi.

### 7.5 Catatan Harian
- Tabel tanggal, uraian kegiatan, aksi edit/hapus.
- Tombol “Tambah Catatan” (modal).
- Tombol “Cetak Form Bimbingan” (PDF tabel 50 baris).

### 7.6 Laporan Saya & Cetak
- Tabel rekapitulasi kehadiran (tanggal, status masuk/pulang, durasi, jarak).
- Ringkasan sanksi.
- Tombol “Cetak Laporan” – aktif hanya jika dosen pembimbing dan pembimbing lapangan terisi.

---

## 8. Pedoman Warna & Ikon Dashboard

Ringkasan semua card dashboard per role.

### Admin

| Card | Warna (lingkaran ikon) | Ikon FA |
|------|------------------------|---------|
| Total Mahasiswa MBKM/PKL | `bg-blue-600` | `fa-users` |
| Total Dosen Pembimbing | `bg-green-600` | `fa-chalkboard-user` |
| Total Mitra (Tempat) | `bg-yellow-500` | `fa-building` |
| Rata-rata Kehadiran | `bg-purple-600` | `fa-calendar-check` |
| Pendaftaran Pending | border merah | `fa-hourglass-half` |

### Koordinator

| Card | Warna | Ikon FA |
|------|-------|---------|
| Mahasiswa Terdaftar | `bg-cyan-700` | `fa-user-graduate` |
| Check-in Hari Ini | `bg-emerald-500` | `fa-fingerprint` |
| Laporan Selesai | `bg-amber-500` | `fa-file-alt` |
| Total Sanksi | `bg-rose-600` | `fa-gavel` |

### Dosen

| Card | Warna | Ikon FA |
|------|-------|---------|
| Mahasiswa Bimbingan | `bg-blue-600` | `fa-chalkboard-user` |
| Perlu Bimbingan | `bg-orange-500` | `fa-pen-ruler` |
| Seminar Terdekat | `bg-indigo-500` | `fa-calendar-day` |

### Mahasiswa

| Card | Warna background/teks | Ikon FA |
|------|----------------------|---------|
| Hero Check-in | Gradien hijau | `fa-location-dot` |
| Hari Hadir | `bg-sky-100 text-sky-800` | `fa-calendar-check` |
| Total Sanksi | `bg-rose-100 text-rose-800` | `fa-exclamation-triangle` |
| Deadline Terdekat | `bg-amber-100 text-amber-800` | `fa-hourglass-start` |
| Progres Laporan | putih + progress bar | `fa-book-open` |

---

## 9. Alur Pengguna (User Journey)

### Admin
1. Login → Dashboard statistik → Lihat pendaftaran pending → Validasi → Set dosen pembimbing → Enrollment aktif.
2. Atur periode baru: Manajemen Periode → Tambah → Isi konfigurasi jam & deadline → Aktifkan.
3. Lihat laporan → Rekap Monitoring → Filter → Ekspor PDF.

### Koordinator
1. Login → Dashboard (hanya periode/prodi tugas) → Validasi pendaftaran mahasiswa di prodi-nya.
2. Pantau progres laporan → Beri catatan jika ada yang terlambat.
3. Lihat peta monitoring.

### Dosen Pembimbing
1. Login → Dashboard daftar mahasiswa bimbingan → Klik detail → Beri catatan unggahan → Setujui/revisi.
2. Setelah semua bab selesai → Input nilai laporan & seminar → Simpan.
3. Peta monitoring.

### Mahasiswa
1. Login → Lengkapi profil → Daftar PKL (pilih periode, tempat).
2. Setelah disetujui → Dashboard tampilkan tombol check-in.
3. Setiap hari: Halaman Check-in → Klik “Masuk” pada jam kerja → Sore klik “Pulang”.
4. Buka Progres Laporan → Upload Bab sesuai deadline → Terima catatan dosen → Revisi.
5. Setelah semua selesai → Laporan Saya → Cetak Laporan.

---

## 10. Implementasi Tailwind – Tips Praktis

- **Instalasi** Tailwind via npm (postcss, autoprefixer) atau CDN untuk prototyping. Untuk produksi gunakan build.
- **Konfigurasi** `tailwind.config.js` – tambahkan warna kustom jika perlu.
- **Plugin yang berguna**: `@tailwindcss/forms`, `@tailwindcss/typography`, `@tailwindcss/aspect-ratio`.
- **JIT mode** aktifkan untuk kecepatan.
- **Dark mode** opsional, gunakan `dark:` prefix.
- **Integrasi dengan Laravel**: komponen Blade dapat menggunakan `@class` directive untuk conditional classes.
- **Leaflet** – pastikan map container memiliki `z-index` rendah agar tidak menutup modal.
- **Font Awesome** – CDN atau self-host, gunakan versi 6 untuk ikon terbaru.

---

## 11. Tahapan Implementasi Redesain UI

Tahapan berikut digunakan untuk menerapkan redesain UI sekaligus memastikan rebranding dari **Mon PKL** menjadi **Monitoring MBKM dan Kerja Praktik (SiLAT)** konsisten di seluruh aplikasi.

### Tahap 1 - Audit UI dan Inventarisasi Halaman
- **Status implementasi:** selesai pada dokumen audit `docs/ui-audit-stage-1.md`.
- Pastikan master **Program Kegiatan** masuk ke audit sebagai data dasar baru.
- Inventarisasi seluruh halaman Blade berdasarkan role: admin, koordinator, dosen pembimbing, dan mahasiswa.
- Catat komponen yang sudah ada: layout, navigation, card statistik, tabel, form, modal, toast, badge status, peta, dan halaman laporan.
- Tandai teks, judul, menu, route label, empty state, dan pesan validasi yang masih memakai istilah **Mon PKL**.
- Petakan halaman prioritas tinggi: dashboard, pendaftaran, check-in, monitoring peta, progres laporan, validasi pendaftaran, dan laporan rekap.

### Tahap 2 - Fondasi Rebranding SiLAT
- Tambahkan istilah **Program Kegiatan** sebagai master nama program, misalnya Kerja Praktik, Magang, Riset, dan program MBKM lain.
- Tetapkan nama aplikasi utama menjadi **Monitoring MBKM dan Kerja Praktik** dengan singkatan **SiLAT (Sistem Laporan Aktivitas Terpadu MBKM & Kerja Praktik)**.
- Perbarui identitas di layout utama: logo teks, title aplikasi, sidebar, navbar, footer, meta title, dan halaman autentikasi.
- Selaraskan istilah fitur: gunakan **MBKM/Kerja Praktik**, **tempat MBKM/KP**, **periode MBKM/KP**, dan **monitoring** secara konsisten.
- Pastikan konfigurasi aplikasi, environment label, dan dokumen internal tidak lagi menampilkan nama lama kecuali pada catatan migrasi.

### Tahap 3 - Standarisasi Design System
- Buat atau rapikan komponen Blade reusable untuk tombol, input, select, textarea, badge, alert, modal, card, tabel, pagination, tabs, dan empty state.
- Terapkan token visual dasar: warna brand biru tua, aksen biru, status sukses/peringatan/bahaya, radius, shadow, spacing, dan tipografi.
- Standarkan ikon Font Awesome 6 untuk navigasi dan aksi agar tiap role memakai bahasa visual yang sama.
- Pastikan state interaksi tersedia: hover, focus, disabled, loading, error, success, dan empty.

### Tahap 4 - Redesain Layout Utama dan Navigasi
- Tambahkan menu master program untuk admin, lalu tampilkan filter program pada halaman validasi, monitoring, dan laporan.
- Implementasikan layout responsif dengan sidebar desktop, navbar atas, dan menu mobile yang mudah diakses.
- Kelompokkan menu berdasarkan workflow: dashboard, manajemen periode/tempat, validasi, monitoring, laporan, konfigurasi, dan profil.
- Terapkan active state menu berdasarkan route saat ini.
- Pastikan navigasi tidak berubah drastis antar role, tetapi tetap menampilkan menu sesuai hak akses.

### Tahap 5 - Redesain Dashboard per Role
- Admin: tampilkan statistik utama, pendaftaran pending, ringkasan periode aktif, dan shortcut validasi.
- Koordinator: fokus pada mahasiswa per periode/prodi, check-in hari ini, progres laporan, dan sanksi.
- Dosen pembimbing: fokus pada mahasiswa bimbingan, unggahan yang perlu ditinjau, nilai, dan peta monitoring.
- Mahasiswa: fokus pada status pendaftaran, check-in hari ini, progres laporan, deadline, sanksi, dan cetak laporan.

### Tahap 6 - Redesain Workflow Utama
- **Status implementasi:** selesai pada dokumen `docs/ui-workflow-stage-6.md`; workflow pendaftaran, check-in, validasi pendaftaran, laporan rekap, dan laporan mahasiswa sudah dirapikan dengan design system dan mengikuti rancangan bagian 1-10.
- Rule program: seluruh program sementara memakai rule Kerja Praktik, tetapi desain form dan label harus siap untuk rule berbeda per program pada fase berikutnya.
- Pendaftaran MBKM/KP: ubah menjadi alur bertahap dengan validasi jelas, ringkasan akhir, dan status pengajuan.
- Check-in: prioritaskan peta, jarak, status lokasi, kamera/foto, catatan, dan tombol aksi yang tidak membingungkan.
- Progres laporan: tampilkan deadline, status unggah, catatan dosen, revisi, dan aksi upload dalam satu alur.
- Validasi pendaftaran: sediakan filter, aksi setujui/revisi/tolak, penetapan dosen, dan pesan status yang eksplisit.
- Laporan: rapikan filter, ringkasan, tabel rekap, dan tombol ekspor/cetak.

### Tahap 7 - Integrasi Peta dan Media
- **Status implementasi:** selesai pada dokumen `docs/ui-map-media-stage-7.md`; halaman `maps/monitoring` default ke periode aktif, memiliki filter periode/tanggal/Hari Ini untuk semua role termasuk mahasiswa, dan tabel data marker yang tersinkronisasi dengan peta.
- Standarkan tampilan Leaflet untuk halaman monitoring dan check-in.
- Pastikan marker mahasiswa, tempat MBKM/KP, polyline jarak, popup, dan legenda konsisten.
- Perbaiki state izin lokasi/kamera: belum diizinkan, ditolak, loading, gagal membaca posisi, dan sukses.
- Optimalkan tampilan mobile agar peta dan form tidak saling menutupi.

### Tahap 8 - Responsivitas dan Aksesibilitas
- Uji breakpoint `sm`, `md`, `lg`, dan `xl` untuk dashboard, tabel, form, modal, dan peta.
- Pastikan tabel penting tetap dapat discroll horizontal di layar kecil.
- Tambahkan label form, focus ring, kontras warna yang cukup, dan struktur heading yang runtut.
- Hindari teks terpotong pada tombol, badge, kartu statistik, dan menu sidebar.

### Tahap 9 - Migrasi Bertahap di Kode
- Mulai dari layout dan komponen dasar agar perubahan visual bisa diwariskan ke banyak halaman.
- Lanjutkan ke dashboard dan halaman workflow prioritas tinggi.
- Refactor halaman lama secara bertahap tanpa mengubah logika bisnis yang tidak terkait UI.
- Simpan perubahan per modul agar mudah diuji dan ditinjau.

### Tahap 10 - QA, UAT, dan Rollout
- Jalankan build asset dan test fitur yang terdampak.
- Lakukan checklist visual per role: desktop, tablet, mobile, empty state, error state, dan data penuh.
- Minta validasi pengguna internal untuk alur mahasiswa, dosen, koordinator, dan admin.
- Setelah stabil, tetapkan rebranding SiLAT sebagai baseline UI baru dan dokumentasikan halaman yang masih perlu penyempurnaan.

---

**Dokumen ini menjadi panduan desain UI/UX untuk pengembangan Sistem Monitoring MBKM dan Kerja Praktik (SiLAT) berbasis Tailwind CSS, memastikan konsistensi antar role dan kemudahan penggunaan sesuai kebutuhan akademik Universitas Lampung.**
