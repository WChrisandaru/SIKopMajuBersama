# Kebutuhan Desain UI Laravel SI Koperasi Maju Bersama

Versi: 1.0 — 2 November 2026  
Tahap: Fase 3 Perancangan Antarmuka  
Dasar: Draft SRS Koperasi Maju Bersama v0.1, berstatus draft untuk validasi mitra.

## 1. Tujuan dan cara menggunakan dokumen

Dokumen ini menjadi acuan membangun rancangan antarmuka SI Koperasi Maju Bersama langsung menggunakan Laravel. Hasil Fase 3 mencakup arsitektur informasi, sitemap, wireframe, dan UI dengan tampilan terperinci. Laravel digunakan sebagai media desain yang dapat dibuka melalui browser pada laptop. Pengujian prototype secara formal dan validasi pengguna tetap menjadi pekerjaan Fase 4.

Gunakan dokumen ini sebagai brief saat memulai pekerjaan di repositori Laravel. Implementasikan halaman dan alur utama menggunakan data contoh yang konsisten. Penggunaan Laravel tidak otomatis memperluas pekerjaan menjadi sistem produksi, migrasi Excel, atau penghitungan keuangan final.

Penanda yang digunakan:

- **SRS:** kebutuhan yang tercantum dalam SRS; prioritas Must, Should, atau Validate tetap mengikuti dokumen sumber.
- **Usulan UI:** keputusan desain untuk membantu implementasi; bukan aturan koperasi yang telah disahkan.
- **Perlu validasi:** aturan, kewenangan, atau proses yang belum final. Tampilkan sebagai rancangan/simulasi apabila diperlukan, tanpa menganggapnya sebagai aturan aktif.

## 2. Konteks dan batasan

Sistem melayani administrasi pengurus koperasi melalui aplikasi web lokal. Tujuan utamanya adalah mempercepat layanan, menyatukan histori anggota, dan mengurangi pencatatan ulang transaksi.

Prinsip utama: **satu transaksi terkonfirmasi menjadi sumber pembaruan tagihan, histori, saldo terkait, Buku Kas, dan laporan**. UI tidak boleh meminta pengurus memasukkan pembayaran yang sama kembali ke Buku Kas.

Dalam cakupan desain:

- Login dan tampilan berdasarkan peran.
- Anggota dan histori keuangan anggota.
- Tagihan, review/finalisasi, pembayaran, dan bukti bayar.
- Simpanan, pinjaman, Arisan, dan Arisan Emas.
- Buku Kas, kategori keuangan, laporan, dan data pendukung SHU.
- Rancangan manajemen pengguna serta fitur yang masih perlu validasi.

Di luar cakupan awal: portal anggota, notulensi Sekretaris, WhatsApp otomatis, payment gateway, integrasi bank otomatis, dan sistem produksi penuh. Pencatatan transfer bukan integrasi bank. Tidak ada kebutuhan wajib melakukan undian arisan secara otomatis.

## 3. Pengguna dan hak akses

| Area | Bendahara | Ketua | Catatan |
| --- | --- | --- | --- |
| Login | Ya | Ya | FR-01–02 |
| Dashboard | Operasional | Monitoring | Konten mengikuti peran |
| Anggota dan histori | Kelola | Lihat | Tidak menghapus histori saat anggota nonaktif |
| Tagihan dan pembayaran | Kelola | Lihat | Ketua tidak melakukan transaksi |
| Simpanan dan pinjaman | Kelola | Lihat | Ketentuan produk belum seluruhnya final |
| Arisan dan Arisan Emas | Kelola | Lihat | Mekanisme perlu validasi |
| Buku Kas | Kelola | Lihat | Transaksi sumber tidak dicatat ulang |
| Laporan | Lihat/generate | Lihat/generate | Menghasilkan laporan tidak mengubah transaksi |
| Manajemen pengguna | Belum ditetapkan | Belum ditetapkan | Jangan otomatis menetapkan Bendahara sebagai administrator |
| Koreksi/pembatalan | Belum ditetapkan | Belum ditetapkan | Jejak perubahan harus dipertahankan |
| Backup/restore | Pengurus berwenang, belum ditetapkan | Belum ditetapkan | FR-37–38 berstatus Validate |

Usulan UI: sediakan dua akun demonstrasi atau pemilih skenario peran khusus lingkungan demo. Pada tampilan Ketua, hilangkan tombol tambah, ubah, finalisasi, konfirmasi, dan nonaktifkan. Jika alur sudah menggunakan endpoint yang mengubah data, pembatasan juga harus diterapkan pada server; menyembunyikan tombol saja tidak cukup.

Perbedaan Bendahara 1 dan Bendahara 2 belum ditentukan. Jangan membuat alur persetujuan baru atas nama Ketua tanpa validasi.

## 4. Arsitektur informasi dan sitemap

Struktur berikut merupakan usulan navigasi yang memetakan UI-01 sampai UI-12 dalam SRS.

```text
Login
Aplikasi
├── Dashboard
├── Anggota
│   ├── Daftar anggota
│   ├── Tambah / ubah anggota
│   └── Detail anggota dan histori
├── Tagihan
│   ├── Daftar berdasarkan periode
│   ├── Bentuk draft tagihan
│   ├── Review dan finalisasi
│   └── Detail / cetak tagihan anggota
├── Pembayaran
│   ├── Daftar pembayaran
│   ├── Catat pembayaran
│   └── Detail / bukti pembayaran
├── Simpanan
│   ├── Ringkasan per jenis dan anggota
│   ├── Detail dan histori
│   └── Setoran / pengambilan
├── Pinjaman
│   ├── Daftar Reguler / Sebrakan
│   ├── Form pinjaman
│   └── Detail, jadwal angsuran, dan histori
├── Arisan
├── Arisan Emas
├── Buku Kas
│   ├── Mutasi dan saldo
│   └── Catat pengeluaran langsung
├── Laporan
│   ├── Transaksi dan kas
│   ├── Simpanan dan pinjaman
│   ├── Tagihan dan tunggakan
│   ├── Data pendukung SHU
│   └── Perbandingan / pertumbuhan keuangan
└── Pengaturan
    ├── Pengguna dan peran [kewenangan belum ditetapkan]
    ├── Kategori dan komponen tagihan [usulan UI]
    ├── Jejak perubahan [usulan UI]
    └── Backup / restore [perlu validasi]
```

## 5. Spesifikasi halaman

### 5.1 Login — UI-01, FR-01–02

- Input username dan password, tombol masuk, tampil/sembunyikan password.
- Tampilkan nama koperasi dan identitas aplikasi.
- Siapkan keadaan form kosong, sedang diproses, kredensial salah, dan akses tidak diizinkan.
- Setelah login, arahkan ke dashboard sesuai peran.
- Tidak menyediakan pendaftaran anggota mandiri. Pemulihan password belum ditetapkan dalam SRS.

### 5.2 Dashboard — UI-02, FR-27–28

- Filter periode dan identitas pengguna/peran.
- Ringkasan kas, tagihan/tunggakan, pinjaman, serta transaksi terbaru.
- Daftar anggota melewati jatuh tempo beserta nominal tunggakan, sesuai US-02/PBI-18.
- Bendahara: akses cepat ke pencarian anggota, pembayaran, tagihan, dan Buku Kas.
- Ketua: informasi monitoring, termasuk grafik perbandingan dan pertumbuhan tahunan.
- Kartu dapat menuju daftar yang sudah difilter sesuai konteksnya (usulan UI).
- Definisi metrik, pemisahan kas fisik/bank, dan rumus dana yang dapat dipinjamkan kembali perlu validasi. Jangan menyamakan seluruh saldo kas dengan dana tersedia untuk pinjaman.
- Grafik harus menunjukkan periode dan satuan; tanpa data pembanding tampilkan keterangan, bukan pertumbuhan buatan.

### 5.3 Anggota — UI-03, FR-03–05

Daftar menampilkan nomor anggota, nama, kontak, status, dan aksi detail. Sediakan pencarian nomor/nama, filter status, dan pembagian halaman.

Form bersumber dari atribut SRS: nomor anggota, nama, alamat, kontak, nomor rekening, nama ibu kandung, dan status. Nomor anggota harus unik. Kewajiban pengisian selain identitas utama perlu dicocokkan dengan kebutuhan mitra. Data rekening dan nama ibu kandung tidak perlu muncul pada daftar umum.

Detail anggota memuat profil serta tab kewajiban/tagihan, pembayaran, simpanan, dan pinjaman. Histori menampilkan tanggal, nomor/referensi transaksi, jenis, nominal, status, dan akses detail. Sediakan tombol pembayaran yang membawa identitas anggota ke form pembayaran.

Gunakan aksi **Nonaktifkan anggota** dengan dialog konfirmasi. Jangan menyediakan penghapusan histori keuangan. Perlakuan terhadap anggota nonaktif yang masih memiliki kewajiban perlu validasi.

### 5.4 Tagihan — UI-04, FR-06–08, FR-11

- Pilih periode bulan/tahun; tampilkan daftar anggota, total kewajiban, pembayaran, sisa, dan status.
- Form pembentukan draft menggunakan anggota dan komponen yang berlaku pada periode terpilih.
- Halaman review memperlihatkan rincian per anggota, komponen, nominal, total, dan masalah data sebelum finalisasi.
- Detail menyediakan pratinjau tagihan per anggota dan cetak/simpan PDF.
- Komponen sumber meliputi Arisan, Arisan Emas, Simpanan Wajib, Sosial, angsuran/bunga Reguler, Sebrakan/bunganya, serta denda jika berlaku. Keaktifan dan nominal masing-masing belum final.
- Usulan UI: pisahkan status dokumen (draft/final) dan status pembayaran (belum dibayar/lunas); jangan mencampurnya menjadi satu indikator.
- Status sebagian dibayar dan aturan finalisasi ulang hanya dirancang sebagai skenario validasi.
- Cegah draft ganda untuk anggota dan periode yang sama. Perilaku revisi tagihan final perlu diputuskan sebelum implementasi operasional.

### 5.5 Pembayaran dan bukti — UI-05, FR-09–13, FR-24

Alur utama:

1. Cari dan pilih anggota.
2. Tampilkan tagihan beserta komponen dan histori relevan.
3. Pilih komponen yang dibayar.
4. Isi tanggal, metode tunai/transfer, nominal, dan rincian alokasi.
5. Tinjau ringkasan anggota, periode, komponen, total, serta petugas.
6. Konfirmasi satu kali.
7. Tampilkan hasil transaksi dan bukti pembayaran dengan akses cetak/PDF.

Total alokasi harus sama dengan nominal pembayaran; nominal harus positif. Cegah pengiriman ganda ketika tombol sedang diproses. Jika penyimpanan gagal, jangan menampilkan bukti keberhasilan atau mengubah saldo sebagian.

Setelah berhasil, tampilan tagihan, histori anggota, saldo terkait, Buku Kas, dan laporan harus mengambil hasil transaksi yang sama. Dalam demo, konsistensi ini dapat disimulasikan dengan satu sumber data contoh.

Bukti pembayaran memuat nama koperasi, nomor transaksi, tanggal, anggota, metode, rincian komponen, total, dan petugas. Usulan tampilan cetak: latar putih, tanpa sidebar, mudah dibaca pada kertas.

Perlu validasi: pembayaran parsial, lebih bayar, lintas periode, pengunggahan bukti transfer, rekening tujuan, tanggal efektif, dan kewenangan verifikasi. SRS mengusulkan status transfer **menunggu verifikasi** pada proses To-Be; jangan memperlakukan transfer tersebut sebagai pembayaran terkonfirmasi sebelum verifikasi.

Koreksi/pembatalan hanya ditampilkan sebagai rancangan sampai otorisasinya ditetapkan. Rancangan memuat alasan, referensi transaksi awal, petugas, waktu, dan riwayat; jangan menghapus transaksi awal.

### 5.6 Simpanan — UI-06, FR-14–17

- Kelompokkan Pokok, Wajib, Sukarela, dan Berjangka.
- Tampilkan saldo per anggota/jenis, histori setoran/pengambilan, tanggal, nominal, dan referensi transaksi.
- Form transaksi memuat anggota, jenis simpanan, jenis transaksi, tanggal, nominal, dan ringkasan sebelum konfirmasi.
- Pembayaran Simpanan Wajib melalui tagihan harus muncul otomatis pada histori simpanan, tanpa input setoran ulang.
- Setoran/pengambilan Sukarela termasuk kebutuhan SRS; ketentuan saldo minimum, otorisasi, dan bunga belum final.
- Jangan mengaktifkan penarikan Pokok/Wajib berdasarkan asumsi. Simpanan Berjangka berstatus Validate, termasuk tenor, bunga, pencairan, dan penalti.

### 5.7 Pinjaman — UI-07, FR-18–20

- Daftar: anggota, jenis Reguler/Sebrakan, pokok, saldo, jatuh tempo, dan status.
- Filter jenis/status dan pencarian anggota.
- Form/detail: anggota, jenis, plafon/pokok, tenor, bunga, tanggal pencairan, jatuh tempo, hasil musyawarah, dan status.
- Detail menampilkan jadwal angsuran: urutan, jatuh tempo, pokok, bunga, denda jika berlaku, status, serta histori pembayaran.
- Angsuran yang dibayar melalui alur pembayaran tidak dimasukkan lagi secara terpisah.
- Formula bunga, denda, plafon, pelunasan awal, pencairan, dan persetujuan perlu validasi. Angka historis 1% Reguler, 2,5% Sebrakan, dan batas Sebrakan 3 bulan tidak boleh dikunci sebagai aturan final.
- Gunakan nilai contoh berlabel simulasi; jangan membangun kalkulator seolah rumus telah disahkan.

### 5.8 Arisan dan Arisan Emas — UI-08–09, FR-21–22

Siapkan halaman terpisah yang memuat peserta, periode, kewajiban/nominal, transaksi, dan status. Detail dan form hasil penerimaan hanya menjadi rancangan sampai mekanisme divalidasi. To-Be mengusulkan pengocokan manual dengan pencatatan hasil pada sistem.

Gunakan istilah **Arisan Emas** mengikuti SRS. Deskripsi Capstone memakai istilah Tabungan Emas; kesetaraan istilah dan proses harus dikonfirmasi. Jangan mengasumsikan saldo gram, harga emas langsung, konversi rupiah ke gram, atau perdagangan emas.

### 5.9 Buku Kas — UI-10, FR-23–26

- Filter rentang tanggal/periode, kategori, dan arah kas.
- Tampilkan tanggal, nomor transaksi, kategori, keterangan, kas masuk, kas keluar, saldo berjalan, referensi, dan petugas.
- Ringkasan saldo awal, total masuk, total keluar, dan saldo akhir mengikuti filter yang jelas.
- Transaksi dari pembayaran/simpanan/pinjaman memiliki tautan ke sumber; jangan menyediakan input ulang untuk sumber tersebut.
- Form pengeluaran langsung: tanggal, kategori, nominal, keterangan, referensi bila ada, dan petugas.
- Kategori harus membedakan pokok pinjaman, bunga, denda, administrasi, biaya, simpanan, dan kelompok lain yang berlaku. Kas masuk tidak otomatis berarti pendapatan.
- Format kas wajib, saldo awal, pemisahan rekening, dan rekonsiliasi perlu validasi.

### 5.10 Laporan — UI-11, FR-29–36

| Jenis laporan | Isi minimum rancangan |
| --- | --- |
| Transaksi periodik | Tanggal, anggota/referensi, jenis, nominal, metode, status |
| Simpanan | Anggota, jenis simpanan, mutasi dan saldo |
| Pinjaman | Anggota, jenis, saldo, jatuh tempo, status pembayaran |
| Tagihan/tunggakan | Periode, anggota, kewajiban, terbayar, sisa |
| Kas | Saldo awal, kas masuk/keluar, saldo akhir dan rincian |
| Pendukung SHU | Periode dan data sumber yang relevan; formula pembagian belum final |
| Pertumbuhan keuangan | Perbandingan antarperiode/tahun dan grafik; definisi indikator perlu validasi |

Setiap halaman menyediakan filter periode, pratinjau, ringkasan, dan cetak/PDF. Ketua dapat melihat dan menghasilkan laporan. Tampilkan filter aktif pada hasil cetak agar cakupan data jelas.

FR-34 mewajibkan data pendukung SHU. FR-35 tentang perhitungan otomatis masih Validate: jangan menghasilkan pembagian SHU final tanpa formula disahkan. Format laporan RAT, neraca, dan laba rugi harus disepakati sebelum dinyatakan sebagai format resmi.

### 5.11 Pengguna dan pengaturan — UI-12, FR-02, FR-26, FR-37–38

- Rancangan daftar pengguna: nama, username, peran, status; form tambah/ubah akun disiapkan untuk peninjauan.
- Akses pengelolaan akun belum diberikan kepada peran tertentu sampai validasi.
- Usulan halaman kategori/komponen: nama, kategori, nominal bila relevan, masa berlaku, dan status. Dasarnya NFR-11 dan prinsip konfigurasi aturan.
- Usulan halaman jejak perubahan: waktu, pengguna, aksi, referensi, dan perubahan penting. Ketersediaan audit diwajibkan SRS, sedangkan bentuk halaman dan hak melihatnya perlu disepakati.
- Backup/restore: rancangan informasi cadangan terakhir, lokasi, waktu, petugas, dan hasil proses. Mekanisme, jadwal, dan kewenangan masih Validate; tombol demo tidak boleh melakukan pemulihan data nyata.

## 6. Pedoman visual dan komponen bersama

Seluruh ketentuan visual berikut merupakan usulan UI, bukan ketentuan merek dari SRS.

- Prioritaskan desktop/laptop; gunakan sidebar, header identitas/peran, judul halaman, dan area isi yang lapang.
- Usulan warna utama hijau tua dengan latar netral. Status harus memakai teks/ikon selain warna.
- Gunakan bahasa Indonesia dan istilah familiar: Tagihan, Pembayaran, Simpanan, Pinjaman, Buku Kas, Laporan.
- Nominal menggunakan format rupiah dan rata kanan; tanggal dan periode ditampilkan konsisten.
- Tabel menyediakan pencarian/filter sesuai konteks, judul kolom jelas, total, pagination bila perlu, dan tampilan kosong.
- Form memiliki label, penanda wajib, bantuan singkat, error dekat field, serta nilai yang tetap tersimpan saat validasi gagal.
- Komponen bersama: tombol, input, pilihan anggota, pilihan periode, badge status, kartu ringkasan, tabel, dialog konfirmasi, notifikasi, tab, dan template cetak.
- Sediakan keadaan normal, kosong, pencarian tanpa hasil, sedang memproses, validasi gagal, berhasil, dan akses ditolak.
- Fokus keyboard harus terlihat; label terhubung dengan input; dialog dapat ditutup/dinavigasi tanpa mouse.
- Target pemeriksaan awal pada lebar 1280 dan 1440 piksel adalah usulan desain. Pada layar lebih sempit, tabel dapat digulir tanpa menutupi tombol utama.
- Asset inti termasuk font, ikon, CSS, dan JavaScript tersedia lokal agar fungsi utama tidak bergantung internet (NFR-03).
- Data sensitif anggota tidak ditampilkan pada dashboard atau dokumen cetak yang tidak membutuhkannya (NFR-12).

## 7. Arah implementasi Laravel

Keputusan menggunakan Laravel berasal dari pengguna. SRS tidak menentukan versi Laravel, database, atau pustaka UI.

Usulan awal:

- Gunakan Blade sebagai dasar halaman, layout bersama, dan komponen reusable. Sesuaikan dengan struktur repositori jika sudah ada.
- Tentukan versi framework dan pustaka sesuai lingkungan proyek saat implementasi; dokumen ini tidak mengunci versi.
- Gunakan route bernama dan kelompok halaman berdasarkan modul. Nama path berikut hanya usulan.
- Pisahkan data contoh dan logika tampilan agar seluruh halaman memakai identitas anggota/transaksi yang sama.
- Jika database demo digunakan, isi dengan data fiktif. Jangan otomatis mengimpor data pribadi dari workbook koperasi.
- Tentukan secara eksplisit apakah perubahan demo hanya bertahan selama sesi atau disimpan. Jelaskan perilakunya pada petunjuk demo.
- Gunakan tampilan cetak khusus; penyimpanan sebagai PDF dapat memakai fasilitas cetak browser pada tahap desain. Jangan memberi label ekspor otomatis jika kemampuan itu belum dibuat.

| Modul | Usulan path |
| --- | --- |
| Login dan dashboard | `/login`, `/dashboard` |
| Anggota | `/anggota`, `/anggota/create`, `/anggota/{id}` |
| Tagihan | `/tagihan`, `/tagihan/create`, `/tagihan/{id}`, `/tagihan/{id}/cetak` |
| Pembayaran | `/pembayaran`, `/pembayaran/create`, `/pembayaran/{id}`, `/pembayaran/{id}/bukti` |
| Simpanan dan pinjaman | `/simpanan`, `/simpanan/{id}`, `/pinjaman`, `/pinjaman/{id}` |
| Arisan | `/arisan`, `/arisan-emas` |
| Buku Kas | `/buku-kas`, `/buku-kas/pengeluaran/create` |
| Laporan | `/laporan`, `/laporan/{jenis}` |
| Pengaturan | `/pengaturan/pengguna`, `/pengaturan/komponen`, `/pengaturan/kategori` |

Path berparameter harus tidak berbenturan dengan path khusus seperti `create`. Aksi yang mengubah data menggunakan metode permintaan yang sesuai dan perlindungan aplikasi Laravel; tautan halaman hanya untuk navigasi.

## 8. Data demonstrasi dan skenario

Siapkan data fiktif untuk anggota aktif/nonaktif, tagihan draft/final, tagihan belum dibayar/lunas, transaksi tunai/transfer, simpanan beberapa jenis, pinjaman Reguler/Sebrakan, tunggakan, dan pengeluaran kas.

Skenario terpisah untuk aturan yang belum disahkan: transfer menunggu verifikasi, pembayaran parsial/lebih bayar, koreksi, Simpanan Berjangka, Arisan, Arisan Emas, dan SHU. Tandai sebagai simulasi; jangan menyamarkannya sebagai hasil keputusan mitra.

Skenario utama yang harus konsisten lintas halaman:

1. Bendahara membuka anggota dan meninjau tagihan.
2. Bendahara mencatat pembayaran sesuai total komponen.
3. Tagihan berubah status; bukti pembayaran tersedia.
4. Histori anggota dan Buku Kas menampilkan referensi pembayaran yang sama.
5. Ringkasan/laporan menggunakan transaksi tersebut tanpa duplikasi.
6. Ketua membuka data yang sama untuk monitoring tanpa tombol mutasi.

## 9. Pemetaan prioritas dan keterlacakan

| Paket desain | Acuan FR | Prioritas SRS | Urutan desain usulan |
| --- | --- | --- | --- |
| Login dan peran | 01–02 | Must | 1 |
| Anggota dan histori | 03–05 | Must | 2 |
| Tagihan, pembayaran, bukti | 06–12 | Must | 3 |
| Koreksi transaksi | 13 | Validate | Rancangan terpisah |
| Pokok, Wajib, Sukarela | 14–16 | Must | 4 |
| Berjangka | 17 | Validate | Rancangan terpisah |
| Reguler dan Sebrakan | 18–19 | Must | 4 |
| Keputusan pinjaman | 20 | Validate | Rancangan terpisah |
| Arisan dan Arisan Emas | 21–22 | Validate | 6, struktur awal |
| Buku Kas dan kategori | 23–26 | Must | 3–4 |
| Dashboard Bendahara | 27 | Should | 5 |
| Dashboard Ketua | 28 | Must | 5 |
| Laporan dan data SHU | 29–34 | Must | 5 |
| Perhitungan SHU | 35 | Validate | Setelah aturan disahkan |
| Ekspor/print umum | 36 | Should | Bersama halaman terkait |
| Backup/restore | 37–38 | Validate | Rancangan terpisah |

Catatan: FR-08 dan FR-12 tetap Must untuk dokumen tagihan dan bukti pembayaran, meskipun ekspor/print umum FR-36 berstatus Should. UI-12 mewajibkan rancangan manajemen pengguna, tetapi kewenangan pengelolaannya masih terbuka.

## 10. Kriteria penerimaan desain

- [ ] Seluruh UI-01–12 memiliki rancangan halaman yang dapat dibuka.
- [ ] Sitemap, wireframe, dan desain terperinci tersedia; wireframe dapat didokumentasikan melalui tangkapan layar tahap awal Laravel.
- [ ] Login dan tampilan Bendahara/Ketua dapat didemonstrasikan (AC-01–02).
- [ ] Anggota dapat dicari dan histori dapat ditelusuri (AC-03, AC-07).
- [ ] Draft tagihan dapat dibentuk/ditinjau pada skenario demo (AC-04).
- [ ] Pembayaran tercatat satu kali dan konsisten dengan tagihan serta kas (AC-05).
- [ ] Bukti pembayaran dapat ditampilkan dan dicetak (AC-06).
- [ ] Buku Kas kronologis dan saldo contoh konsisten (AC-08).
- [ ] Laporan mengikuti periode dan data contoh yang sama (AC-09).
- [ ] Rancangan backup/restore ditandai menunggu kesepakatan; AC-10 belum diklaim terpenuhi secara operasional.
- [ ] Tidak ada tombol utama tanpa hasil atau penjelasan status fitur.
- [ ] Error, data kosong, akses ditolak, dan hasil transaksi memiliki tampilan jelas.
- [ ] Tampilan cetak tidak membawa sidebar dan navigasi aplikasi.
- [ ] Fungsi inti dan asset UI dapat dibuka tanpa internet pada lingkungan lokal.
- [ ] Formula/otorisasi yang belum final tidak diperlakukan sebagai aturan produksi.

Checklist ini memverifikasi kesiapan rancangan. Pengujian kepada mitra dan keputusan penerimaan bisnis dilakukan pada Fase 4.

## 11. Keputusan yang perlu validasi

| Topik | Acuan register SRS | Dampak pada UI |
| --- | --- | --- |
| Bendahara 1/2, finalisasi, koreksi | V-01, V-19 | Tombol, peran, konfirmasi dan jejak perubahan |
| Lokal satu laptop atau LAN | V-02 | Skenario akses dan sesi |
| Komponen/nominal tagihan aktif | V-03–04 | Form, konfigurasi, rincian tagihan |
| Parsial, lebih bayar, lintas periode | V-05 | Alokasi dan status pembayaran |
| Transfer dan verifikasi | V-06 | Field, bukti, status, kewenangan |
| Kas, saldo awal, rekonsiliasi | V-07 | Tabel dan ringkasan kas |
| Ketentuan tiap simpanan | V-08–10 | Form transaksi dan pembatasan penarikan |
| Pinjaman dan musyawarah | V-11–13 | Parameter, jadwal, persetujuan |
| Arisan dan Arisan Emas | V-14–15 | Peserta, hasil, nominal dan pencatatan |
| Kategori keuangan | V-16 | Pilihan kategori dan laporan |
| SHU dan laporan wajib | V-17–18 | Formula, preview dan format cetak |
| Migrasi dan saldo awal | V-20 | Data awal; di luar implementasi desain |
| Backup dan pemulihan | V-21 | Kontrol, lokasi, hasil dan kewenangan |
| Perubahan setelah legalitas | V-22 | Identitas, aturan dan kontrol |

Ketidakkonsistenan sumber yang perlu dicatat: tabel detail To-Be merujuk PBI-29/30 untuk Arisan, sementara daftar Product Backlog yang tersedia berhenti pada PBI-25. Gunakan FR-21–22 dan UI-08–09 sebagai acuan modul tersebut sampai penomoran diperbaiki. Jangan menciptakan isi PBI-29/30 sebagai kebutuhan resmi.

## 12. Brief untuk memulai implementasi

Bangun UI SI Koperasi Maju Bersama menggunakan Laravel berdasarkan dokumen ini. Periksa struktur proyek yang tersedia terlebih dahulu. Mulai dari layout bersama, login/peran, anggota, tagihan, pembayaran, bukti, dan Buku Kas; lanjutkan simpanan, pinjaman, dashboard, laporan, serta rancangan modul yang masih perlu validasi. Gunakan data fiktif yang konsisten. Dokumentasikan wireframe dan keputusan visual sebagai luaran Fase 3. Pertahankan akses Ketua hanya untuk melihat/menghasilkan laporan. Jangan mengunci formula bisnis atau menetapkan kewenangan yang belum disahkan. Jelaskan dengan jujur apakah setiap interaksi masih simulasi atau sudah menyimpan data.

## 13. Sumber

- Sumber utama: `Draft_SRS_Koperasi_Maju_Bersama_v0.1.docx`, folder `09 Capstone/Tugas`; bagian kebutuhan fungsional, nonfungsional, data, antarmuka, aturan bisnis, To-Be, hak akses, kriteria penerimaan, user story, backlog, dan register validasi.
- Batas tahapan: `02 Capstone Dhika.docx`, folder `09 Capstone/Materi`; Fase 3 pertemuan 9–12 dan Fase 4 pertemuan 13–16.
- Arahan pengguna: desain UI dibuat langsung menggunakan Laravel.
- Dokumen ini merupakan turunan kebutuhan untuk desain; tidak mengubah status draft SRS atau menggantikan validasi mitra.
