# Rekomendasi Style Tabel, Search, Filter, dan Sort Data

## Sistem Monitoring MBKM dan Kerja Praktik (SiLAT)

Berdasarkan rancangan UI/UX dengan Tailwind CSS dan Font Awesome, tabel merupakan komponen utama untuk menampilkan data seperti daftar mahasiswa, penempatan PKL, validasi pendaftaran, progres laporan, dan rekap monitoring. Berikut rekomendasi desain tabel yang konsisten, fungsional, dan ramah pengguna.

---

## 1. Prinsip Desain Tabel

| Prinsip | Penerapan |
|---------|------------|
| **Keterbacaan** | Gunakan warna baris berselang (zebra) dan batas kolom tipis. |
| **Responsif** | Pada layar <768px, tabel dapat di-scroll horizontal (`overflow-x-auto`). |
| **Konsistensi** | Setiap tabel memiliki header dengan latar abu-abu muda, teks tebal. |
| **Aksi yang jelas** | Tombol aksi (Edit, Hapus, Lihat) disatukan di kolom paling kanan. |
| **Indikator status** | Gunakan badge berwarna untuk status (aktif, pending, selesai, revisi). |

---

## 2. Struktur Tabel Dasar dengan Tailwind CSS

```html
<div class="bg-white rounded-lg shadow overflow-hidden">
  <div class="px-5 py-3 border-b flex justify-between items-center">
    <h3 class="font-semibold text-gray-700">Daftar Mahasiswa</h3>
    <!-- area search & filter akan ditempatkan di sini -->
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NPM</th>
          <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
          <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr class="hover:bg-gray-50">
          <td class="px-5 py-4 text-sm">1917051001</td>
          <td class="px-5 py-4 text-sm">Angga Pratama</td>
          <td class="px-5 py-4"><span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full">Aktif</span></td>
          <td class="px-5 py-4 text-right"><button class="text-blue-600"><i class="fas fa-edit"></i></button></td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="px-5 py-3 border-t flex justify-between items-center">
    <!-- pagination di sini -->
  </div>
</div>
```

---

## 3. Komponen Search

### 3.1 Desain Search Box
- **Posisi:** Di kanan atas tabel (sejajar dengan judul).
- **Ikon:** Font Awesome `fa-search` di dalam input.
- **Placeholder:** “Cari NPM, nama, atau tempat PKL...”
- **Ukuran:** `w-full md:w-80` (responsif).

### 3.2 Implementasi

```html
<div class="relative">
  <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
  <input type="text" id="searchInput" placeholder="Cari NPM, nama, atau tempat PKL..." 
         class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-full md:w-80 focus:outline-none focus:ring-2 focus:ring-blue-500">
</div>
```

### 3.3 Perilaku
- **Live search** (typing) dengan debounce 300ms.
- Tabel langsung menyaring baris yang cocok (client‑side) atau memanggil API (server‑side). Untuk data besar (>200 baris), gunakan server‑side dengan indikator loading.

---

## 4. Komponen Filter

### 4.1 Desain Tombol Filter
- Tombol dengan ikon `fa-filter` di samping search box.
- Saat diklik, tampilkan *dropdown panel* filter (multi‑kriteria).

```html
<button id="filterButton" class="ml-2 px-3 py-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50">
  <i class="fas fa-filter text-gray-500"></i> Filter
</button>
```

### 4.2 Filter Panel (Dropdown)
- Muncul di bawah tombol, berisi opsi filter.
- Gunakan `absolute`, `z-10`, `bg-white`, `shadow-lg`, `rounded-md`, `p-4`, `w-64`.

```html
<div id="filterPanel" class="absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 p-4 hidden">
  <div class="mb-3">
    <label class="block text-sm font-medium text-gray-700">Status</label>
    <select class="mt-1 w-full border-gray-300 rounded-md">
      <option>Semua</option><option>Aktif</option><option>Pending</option><option>Selesai</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="block text-sm font-medium text-gray-700">Prodi</label>
    <select class="mt-1 w-full border-gray-300 rounded-md">...</select>
  </div>
  <div class="flex justify-end gap-2">
    <button class="text-sm text-gray-500">Reset</button>
    <button class="text-sm bg-blue-600 text-white px-3 py-1 rounded">Terapkan</button>
  </div>
</div>
```

### 4.3 Jenis Filter yang Umum
| Modul | Opsi Filter |
|-------|--------------|
| Mahasiswa | Prodi, Status enrollment, Periode |
| Tempat MBKM | Kota, Status aktif, Jumlah peserta (range) |
| Validasi Pendaftaran | Status (pending/revisi/ditolak), Periode, Prodi |
| Rekap Monitoring | Periode, Prodi, Rentang tanggal, Hari libur (checkbox) |

---

## 5. Komponen Sort (Pengurutan)

### 5.1 Desain Header yang Dapat Diurutkan
- Kolom yang bisa diurutkan diberi ikon `fa-sort` (atau `fa-arrow-up`, `fa-arrow-down` saat aktif).
- Saat diklik, urutkan data ascending/descending.

```html
<th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
  <div class="flex items-center gap-1">
    NPM
    <i class="fas fa-sort text-gray-400"></i>
  </div>
</th>
```

### 5.2 Logika Sorting
- Default: urut berdasarkan kolom pertama (misal NPM atau tanggal) ascending.
- Klik sekali → ascending (ikon `fa-arrow-up`), klik dua kali → descending (`fa-arrow-down`), klik tiga kali → kembali ke default (ikon `fa-sort`).
- Untuk data server‑side, kirim parameter `sort_by` dan `sort_dir` ke endpoint.

---

## 6. Badge Status & Warna

Gunakan Tailwind classes untuk badge agar mudah dipahami:

| Status | Warna Badge | Contoh |
|--------|-------------|--------|
| Aktif / Active | `bg-green-100 text-green-800` | Aktif |
| Pending / Menunggu | `bg-yellow-100 text-yellow-800` | Menunggu Validasi |
| Selesai / Completed | `bg-blue-100 text-blue-800` | Selesai |
| Revisi / Revision | `bg-orange-100 text-orange-800` | Perlu Revisi |
| Ditolak / Rejected | `bg-red-100 text-red-800` | Ditolak |
| Terkunci / Locked | `bg-gray-100 text-gray-800` | Terkunci |

```html
<span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full">Aktif</span>
```

---

## 7. Pagination

### 7.1 Desain Pagination
- Posisi: di kiri bawah (info jumlah data) dan kanan bawah (navigasi halaman).
- Tombol navigasi: Previous (`fa-chevron-left`), nomor halaman, Next (`fa-chevron-right`).

```html
<div class="flex justify-between items-center">
  <div class="text-sm text-gray-500">Menampilkan 1-10 dari 156 data</div>
  <div class="flex gap-1">
    <button class="px-3 py-1 border rounded-md hover:bg-gray-50"><i class="fas fa-chevron-left"></i></button>
    <button class="px-3 py-1 border rounded-md bg-blue-600 text-white">1</button>
    <button class="px-3 py-1 border rounded-md hover:bg-gray-50">2</button>
    <button class="px-3 py-1 border rounded-md hover:bg-gray-50"><i class="fas fa-chevron-right"></i></button>
  </div>
</div>
```

### 7.2 Pilihan per halaman
Tambahkan dropdown di sebelah kiri: “Tampilkan 10 / 25 / 50 / 100 per halaman”.

---

## 8. Responsif untuk Mobile

- Tabel dibungkus `overflow-x-auto` sehingga pengguna bisa scroll horizontal.
- Kolom aksi (tombol) tetap terlihat di kanan.
- Search dan filter akan ditumpuk secara vertikal jika layar <768px:

```html
<div class="flex flex-col md:flex-row justify-between gap-3">
  <h3>Daftar Mahasiswa</h3>
  <div class="flex flex-col sm:flex-row gap-2">
    <div class="relative">...</div>
    <button>Filter</button>
  </div>
</div>
```

---

## 9. Contoh Implementasi Lengkap (Blade + Alpine.js)

```blade
<div x-data="tableHandler()" class="bg-white rounded-lg shadow">
  <div class="p-4 border-b flex flex-wrap justify-between gap-3">
    <h2 class="font-semibold text-lg">Daftar Mahasiswa</h2>
    <div class="flex gap-2">
      <div class="relative">
        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        <input type="text" x-model="search" placeholder="Cari..." class="pl-9 pr-3 py-1.5 border rounded-md w-64">
      </div>
      <button @click="toggleFilter" class="px-3 py-1.5 border rounded-md"><i class="fas fa-filter"></i></button>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full">
      <thead class="bg-gray-50">
        <tr>
          <th @click="sortBy('npm')" class="px-5 py-3 text-left cursor-pointer">NPM <i :class="sortIcon('npm')"></i></th>
          <th class="px-5 py-3 text-left">Nama</th>
          <th class="px-5 py-3 text-left">Status</th>
          <th class="px-5 py-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="item in filteredData" :key="item.id">
          <tr class="border-t hover:bg-gray-50">
            <td class="px-5 py-3" x-text="item.npm"></td>
            <td class="px-5 py-3" x-text="item.name"></td>
            <td class="px-5 py-3"><span x-html="statusBadge(item.status)"></span></td>
            <td class="px-5 py-3 text-right"><button class="text-blue-600"><i class="fas fa-eye"></i></button></td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
  <div class="p-3 border-t flex justify-between items-center">
    <div class="text-sm">Menampilkan <span x-text="from"></span>-<span x-text="to"></span> dari <span x-text="total"></span></div>
    <div class="flex gap-1" x-html="paginationButtons"></div>
  </div>
</div>
```