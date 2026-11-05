# SI Koperasi Maju Bersama

Implementasi awal Fase 3 memakai Laravel 13, Blade, CSS lokal, dan SQLite. Data fiktif disimpan secara persisten. Tidak membutuhkan CDN, layanan bank, atau layanan luar saat dijalankan.

## Jalankan

Dari direktori proyek:

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Untuk instalasi yang sudah disiapkan dalam workspace ini, cukup perintah `php artisan serve` terakhir. Jangan menimpa `.env` yang sudah tersedia. Database SQLite disiapkan oleh instalasi Laravel; pada checkout baru buat file kosong `database/database.sqlite` sebelum migrasi jika belum tersedia. PHP 8.3 atau lebih baru dibutuhkan oleh Laravel 13; lingkungan pembuatan memakai PHP 8.5.5.

Buka http://127.0.0.1:8000. Asset tampilan tidak memerlukan build Node.

| Peran | Email | Password demo |
| --- | --- | --- |
| Bendahara | bendahara@koperasi.test | KoperasiDemo2026! |
| Ketua | ketua@koperasi.test | KoperasiDemo2026! |

Akun ini hanya untuk lingkungan demo lokal; bukan pengaturan untuk publikasi produksi.

## Sudah berfungsi

- Login/logout, pembatasan mutasi di server berdasarkan peran, validasi form, perlindungan CSRF Laravel.
- Dashboard, pencarian anggota, tambah/ubah/nonaktif anggota, detail dan histori.
- Pembentukan tagihan unik per anggota/periode, review, finalisasi.
- Pelunasan satu komponen Simpanan Wajib lewat tunai atau transfer yang sudah diverifikasi manual.
- Pembayaran memperbarui tagihan, simpanan, Buku Kas dan audit secara atomik; penyerahan ulang transaksi ditolak.
- Bukti pembayaran dengan tampilan cetak / simpan PDF melalui browser.
- Buku Kas, pengeluaran langsung, laporan kas per periode.
- Tampilan saldo simpanan, monitoring pinjaman dan akses Ketua tanpa mutasi.
- Halaman Arisan, Arisan Emas, pengguna, audit dan pengaturan dengan status batasan yang terlihat.

## Belum selesai untuk cakupan penuh SRS

Implementasi ini adalah fondasi UI, bukan seluruh sistem produksi. Berikut masih perlu pengembangan atau validasi:

- Tagihan multikomponen, alokasi parsial, lebih bayar, transfer menunggu verifikasi, dokumen tagihan individual.
- Setoran/pengambilan simpanan di luar pembayaran tagihan; transaksi Berjangka.
- Form dan keputusan pinjaman, pencairan, jadwal/angsuran, bunga/denda.
- Proses Arisan/Arisan Emas, formula SHU, laporan neraca/laba-rugi/RAT dan pertumbuhan tahunan.
- Kelola akun, koreksi/pembatalan transaksi, backup/restore dan konfigurasi kategori lengkap.
- Atribut rekening/nama ibu kandung, pagination, filter data lanjutan dan histori lintas periode yang lengkap.
- Pengujian pengguna mitra, pemeriksaan keamanan produksi dan migrasi data asli.

Batasan ini mengikuti status draft SRS dan juga menandai pekerjaan implementasi yang belum dibuat; tidak seluruh fitur di daftar ini menunggu validasi bisnis.

## Pengujian

```sh
php artisan test
```

Pengujian mencakup semua halaman untuk kedua peran, login/logout, hak akses perubahan, konsistensi pembayaran, pencegahan pembayaran ulang, nominal salah, tagihan draft, dan pembentukan periode tanpa duplikasi. Database pengujian terpisah (SQLite memori) dari data demo.

Satu pembayaran Anindya Putri sebesar Rp100.000 dilakukan melalui browser sebagai verifikasi alur demo. Seluruh nama, kontak, saldo awal, pinjaman dan nominal adalah ilustrasi, bukan data koperasi.

## Acuan

Lihat `docs/KEBUTUHAN_UI_LARAVEL_KOPERASI.md`. Sumber utama adalah SRS v0.1 di folder Capstone pengguna. Semua dokumen sumber dipertahankan tanpa perubahan.
