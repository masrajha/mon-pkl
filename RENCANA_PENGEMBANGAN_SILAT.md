# Rencana Pengembangan SiLAT

## 1. Visi Pengembangan

SiLAT dapat dikembangkan dari sistem monitoring MBKM/Kerja Praktik menjadi platform layanan akademik mahasiswa yang lebih luas. Arah pengembangannya bukan hanya menambah menu baru, tetapi membangun fondasi workflow akademik yang dapat dikonfigurasi untuk berbagai proses seperti kerja praktik, magang, skripsi/tugas akhir, seminar, ujian akhir, layanan surat, yudisium, dan pelaporan akademik.

Tujuan utama pengembangan:

- Menyediakan satu pintu layanan akademik mahasiswa.
- Mengurangi proses manual berbasis berkas dan pesan pribadi.
- Menyeragamkan alur persetujuan, review, penilaian, dan arsip dokumen.
- Menyediakan dashboard progres akademik untuk prodi, jurusan, fakultas, dan universitas.
- Mendukung kebutuhan audit, akreditasi, dan pelaporan operasional.

## 2. Prinsip Desain Sistem

Pengembangan sebaiknya memakai pendekatan workflow generik. Artinya, proses seperti KP/PKL, skripsi, seminar proposal, seminar hasil, dan ujian akhir dipandang sebagai variasi dari pola yang sama:

- Ada peserta atau mahasiswa.
- Ada periode atau konteks akademik.
- Ada tahapan proses.
- Ada dokumen yang diunggah.
- Ada reviewer atau approver.
- Ada status, catatan revisi, dan riwayat keputusan.
- Ada penilaian dan berita acara.
- Ada notifikasi dan batas waktu.
- Ada arsip akhir.

Dengan pola ini, penambahan layanan akademik baru tidak perlu selalu membuat modul dari nol.

## 3. Modul Prioritas Pengembangan

### 3.1 Skripsi / Tugas Akhir

Modul skripsi menjadi kandidat pengembangan utama karena alurnya mirip dengan KP/PKL, tetapi lebih kompleks dan bernilai tinggi bagi prodi.

Alur yang disarankan:

1. Pengusulan tema penelitian oleh mahasiswa.
2. Review tema oleh prodi atau koordinator skripsi.
3. Penetapan dosen pembimbing.
4. Bimbingan skripsi secara berkala.
5. Pengajuan seminar proposal.
6. Penjadwalan seminar proposal.
7. Penilaian seminar proposal.
8. Pengajuan seminar hasil.
9. Penjadwalan seminar hasil.
10. Penilaian seminar hasil.
11. Pengajuan ujian akhir/sidang.
12. Penjadwalan ujian akhir.
13. Penilaian akhir.
14. Revisi pasca ujian.
15. Pengesahan dan arsip dokumen final.

### 3.2 Seminar Akademik

Seminar dapat dibuat sebagai subworkflow yang dapat dipakai oleh beberapa layanan:

- Seminar proposal.
- Seminar hasil.
- Seminar KP/PKL.
- Seminar magang.
- Ujian akhir/sidang.

Komponen yang dibutuhkan:

- Pengajuan jadwal.
- Validasi syarat seminar.
- Penentuan penguji.
- Undangan dan notifikasi.
- Presensi seminar.
- Form penilaian.
- Berita acara.
- Revisi dan pengesahan.

### 3.3 Bimbingan Akademik

Modul ini dapat digunakan untuk mencatat interaksi mahasiswa dengan dosen pembimbing akademik atau pembimbing tugas akhir.

Fitur utama:

- Catatan bimbingan.
- Topik pembahasan.
- Rekomendasi dosen.
- Status tindak lanjut.
- Lampiran dokumen.
- Riwayat bimbingan per semester.

### 3.4 Layanan Surat Akademik

Layanan surat dapat menjadi modul generik berbasis template.

Contoh layanan:

- Surat aktif kuliah.
- Surat pengantar penelitian.
- Surat pengantar magang.
- Surat keterangan lulus.
- Surat bebas laboratorium.
- Surat bebas administrasi.

Fitur utama:

- Form pengajuan.
- Validasi persyaratan.
- Approval berjenjang.
- Nomor surat.
- Tanda tangan digital atau QR verification.
- Arsip surat.

### 3.5 Dashboard Akademik dan Pelaporan

Dashboard perlu dikembangkan untuk beberapa level organisasi:

- Program studi.
- Jurusan.
- Fakultas.
- Universitas.

Data yang dapat dipantau:

- Jumlah mahasiswa aktif dalam setiap workflow.
- Progres per tahapan.
- Mahasiswa terlambat.
- Dokumen pending review.
- Beban review dosen.
- Rekap seminar.
- Rekap nilai.
- Risiko keterlambatan penyelesaian studi.
- Export data untuk akreditasi.

## 4. Fondasi Data yang Disarankan

Untuk mendukung perluasan layanan, sistem membutuhkan struktur data yang lebih generik.

Tabel konseptual yang disarankan:

| Entitas | Fungsi |
| --- | --- |
| `academic_workflows` | Mendefinisikan jenis layanan seperti KP, Magang, Skripsi, Seminar, Surat Akademik. |
| `workflow_stages` | Mendefinisikan tahapan dalam setiap workflow. |
| `workflow_participants` | Menyimpan peserta workflow, biasanya mahasiswa. |
| `workflow_submissions` | Menyimpan pengajuan dokumen atau form pada tahap tertentu. |
| `workflow_reviews` | Menyimpan review, persetujuan, revisi, atau penolakan. |
| `workflow_documents` | Menyimpan metadata dokumen dan lampiran. |
| `workflow_assessments` | Menyimpan nilai berbasis komponen/rubrik. |
| `workflow_events` | Menyimpan jadwal seminar, ujian, pembekalan, atau kegiatan terkait. |
| `workflow_signatures` | Menyimpan validasi dokumen, QR verification, dan status tanda tangan digital. |

Struktur yang sudah ada saat ini tetap dapat dipertahankan untuk KP/PKL. Perluasan dapat dilakukan bertahap dengan membuat lapisan workflow generik, lalu memigrasikan modul lama secara selektif jika sudah stabil.

## 5. Role dan Scope Akses

Pengembangan layanan akademik harus mempertahankan konsep scope organisasi yang sudah mulai dibangun.

Role yang disarankan:

- Mahasiswa.
- Dosen.
- Pembimbing lapangan.
- Koordinator program/periode.
- Koordinator skripsi.
- Ketua program studi.
- Ketua jurusan.
- Admin fakultas.
- Admin universitas.
- Viewer laporan.
- Super admin.

Prinsip akses:

- Dosen dapat memiliki lebih dari satu peran.
- Scope akses mengikuti organisasi: prodi, jurusan, fakultas, universitas.
- Role operasional memiliki aksi workflow.
- Role viewer hanya melihat laporan tanpa aksi operasional.
- Semua aksi penting harus tercatat di audit log.

## 6. Notifikasi dan Reminder

Sistem notifikasi perlu menjadi fondasi lintas modul.

Kanal notifikasi:

- Email.
- Web browser notification / push notification.
- Notifikasi dalam aplikasi.

Contoh notifikasi lintas layanan:

- Pengajuan baru masuk.
- Dokumen perlu direview.
- Dokumen diminta revisi.
- Deadline mendekat.
- Jadwal seminar/ujian ditetapkan.
- Nilai sudah tersedia.
- Berita acara sudah dapat dicetak.
- Proses sudah selesai.

Notifikasi browser sebaiknya menjadi reminder real-time tambahan, bukan pengganti email.

## 7. Dokumen Cetak dan Verifikasi

Setiap layanan akademik yang menghasilkan dokumen resmi perlu memakai template cetak yang terkonfigurasi.

Fitur yang disarankan:

- Template header dan logo per organisasi.
- Nomor dokumen.
- QR code verifikasi.
- Data penandatangan sesuai scope organisasi.
- Tanggal berbasis event, bukan tanggal cetak.
- Riwayat revisi dokumen.
- Halaman verifikasi publik terbatas.

Dokumen yang dapat didukung:

- Berita acara seminar.
- Berita acara ujian.
- Lembar penilaian.
- Surat keputusan pembimbing.
- Surat pengantar.
- Surat keterangan.
- Rekap nilai.

## 8. Rencana Implementasi Bertahap

### Tahap 1: Penguatan Fondasi

- Rapikan dokumentasi SRS dan manual sesuai state sistem terbaru.
- Pastikan audit log mencakup semua aksi penting.
- Stabilkan notifikasi email dan browser notification.
- Standarkan status workflow dan pesan validasi.
- Lengkapi backup dan restore database.
- Perketat akses file unggahan.

### Tahap 2: Abstraksi Workflow Akademik

- Buat model workflow generik.
- Buat konfigurasi tahapan workflow.
- Buat komponen dokumen, review, approval, dan assessment generik.
- Buat dashboard progres workflow.
- Pertahankan modul KP/PKL sebagai implementasi referensi.

### Tahap 3: Modul Skripsi Minimum

- Pengusulan tema penelitian.
- Review dan persetujuan tema.
- Penetapan pembimbing.
- Catatan bimbingan.
- Pengajuan seminar proposal.
- Review syarat seminar proposal.

### Tahap 4: Seminar dan Ujian

- Penjadwalan seminar proposal.
- Penjadwalan seminar hasil.
- Penjadwalan ujian akhir.
- Penentuan penguji.
- Form penilaian.
- Berita acara.
- Revisi dan pengesahan.

### Tahap 5: Dashboard Pimpinan dan Akreditasi

- Dashboard prodi, jurusan, fakultas, dan universitas.
- Funnel progres mahasiswa.
- Rekap beban dosen.
- Rekap masa penyelesaian.
- Export data akreditasi.
- Drill-down dari chart ke daftar mahasiswa.

### Tahap 6: Layanan Akademik Lanjutan

- Layanan surat akademik.
- Bebas laboratorium/perpustakaan.
- Yudisium dan wisuda.
- Integrasi data akademik eksternal jika tersedia.

## 9. Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Sistem menjadi terlalu besar dan sulit dirawat. | Gunakan workflow generik dan modul bertahap. |
| Status antar layanan tidak konsisten. | Standarkan status dan transisi workflow. |
| Hak akses menjadi kompleks. | Gunakan scope organisasi dan policy terpusat. |
| Dokumen resmi tidak seragam. | Gunakan template dokumen terkonfigurasi. |
| Notifikasi terlalu banyak. | Gunakan preferensi notifikasi dan digest. |
| Data lama sulit disesuaikan. | Lakukan migrasi bertahap dan pertahankan kompatibilitas modul lama. |

## 10. Rekomendasi Awal

Prioritas terbaik adalah tidak langsung membuat semua layanan baru, tetapi mengekstrak pola yang sudah matang dari KP/PKL menjadi fondasi workflow akademik. Setelah itu, modul skripsi dapat dibangun sebagai pembuktian bahwa SiLAT mampu menangani layanan akademik yang lebih kompleks.

Dengan pendekatan ini, SiLAT dapat tumbuh menjadi platform layanan akademik terpadu tanpa kehilangan stabilitas dari fitur yang sudah berjalan saat ini.
