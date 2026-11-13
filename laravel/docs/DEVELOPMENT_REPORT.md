# Laporan Pengembangan

## Ringkasan

Aplikasi Sistem Informasi Inventory dibuat bertahap dengan Laravel 13, MVC,
Eloquent, migration, route, Blade, dan SQLite. Antarmuka menggunakan CSS
responsif dan Vite. Proses pengembangan dibantu GitHub Copilot; kode dan
perubahan tetap diverifikasi dengan test, formatter, dan build.

## Modul yang sudah diimplementasikan

| Modul | Hasil |
|---|---|
| Autentikasi dan role | Landing page, registrasi publik hanya role Kasir, login/logout, role Admin/Warehouse/Kasir, pembatasan akses per role, akun Admin awal melalui prompt kata sandi rahasia, reset kata sandi tanpa kewajiban verifikasi email, dan perlindungan sesi akun nonaktif. |
| Profil pengguna | Pengubahan nama/email dan kata sandi sendiri dengan verifikasi kata sandi saat ini. |
| Manajemen pengguna | CRUD identitas dan role, aktivasi/nonaktivasi, tautan setup/reset kata sandi, perlindungan Admin aktif terakhir, dan penghapusan akun tanpa histori transaksi. |
| Dashboard | Ringkasan stok, produk, kategori, jumlah transaksi masuk/keluar, grafik pergerakan tujuh hari, produk stok minimum, dan transaksi terbaru. |
| Kategori | CRUD Admin, nama kategori unik, pencarian, dan perlindungan penghapusan kategori yang masih dipakai. |
| Produk | CRUD Admin/Warehouse, SKU unik, kategori, deskripsi, satuan, harga, batas stok minimum, pencarian/filter/paginasi, dan stok awal nol. |
| Transaksi stok | Barang masuk/keluar, pembaruan stok dan histori dalam transaksi database, validasi stok keluar, snapshot sebelum/sesudah, serta pembatalan Admin melalui transaksi reversal immutable dengan alasan wajib dan satu reversal per transaksi asal. |
| Riwayat | Daftar pergerakan dengan filter tipe, produk, pencarian nama/SKU, dan tanggal; Kasir hanya melihat transaksi keluar. |
| Laporan | Posisi stok, nilai persediaan, status stok minimum, ringkasan pergerakan menurut tanggal transaksi, filter pencarian/kategori/tanggal, CSV penuh, XLSX dengan tiga sheet, PDF server-side, serta tampilan print browser. |
| Barcode/QR | Barcode CODE128 berdasarkan SKU dan QR Code menuju detail produk. |
| Label | Pratinjau cetak satu atau banyak produk dengan pengaturan jumlah salinan, layout A4 dua kolom (94 × 52 mm). Batas 50 produk dan 100 label per proses. |
| Scanner | Input manual/scanner USB dan pemindaian kamera melalui ZXing di browser yang mendukung; scan Kasir membuka transaksi keluar dengan produk terpilih. |

## Struktur implementasi

- Model dan relasi Eloquent: `User`, `Category`, `Product`, dan
  `StockTransaction`.
- Kontrol akses menggunakan middleware `auth`, `guest`, dan middleware role.
- Migration tambahan memperluas tabel kategori, pengguna, produk, dan
  transaksi tanpa mengganti migration tabel yang sudah tersedia; pembatalan
  memakai foreign key unik ke transaksi asal.
- Antarmuka disusun dalam Blade untuk autentikasi, master data, dashboard,
  transaksi, laporan, label, dan scanner.
- Operasi stok dilakukan secara transaksional; stok barang keluar menggunakan
  kondisi agar stok tidak menjadi negatif.

## Verifikasi dan batasan

- Verifikasi terakhir: `php artisan test` **76 test lulus, 406 assertions**;
  `vendor/bin/pint --test app routes tests` lulus; `npm.cmd run build` berhasil.
- Landing page diperiksa pada desktop (1366 px) dan viewport mobile (375 px);
  tidak ditemukan overflow horizontal pada halaman tersebut. Pemeriksaan ini
  bukan pengganti pengujian visual seluruh halaman dan perangkat.
- Seluruh migration tercatat `Ran`; dua migration finalisasi diterapkan dengan
  `php artisan migrate --force` tanpa reset atau penghapusan data.
- Test otomatis mencakup autentikasi/role, reset kata sandi, manajemen akun,
  transaksi/reversal, laporan/ekspor, produk/kategori, label, dan scanner.
- Email memerlukan mailer yang disiapkan operator. Belum disimpan kredensial
  email pada source code atau dokumentasi.
- PDF dibuat dengan Dompdf pada server; tampilan cetak browser tetap tersedia
  sebagai alternatif.
- XLSX dibentuk sebagai Open XML workbook dengan ekstensi `ZipArchive` yang
  telah tersedia di lingkungan; tidak memerlukan paket Composer tambahan.
- Pemindaian kamera fisik belum dapat dikonfirmasi oleh test otomatis; uji pada
  browser/perangkat dengan kamera, izin, dan secure context yang sesuai.
- QR Code mengarah ke URL detail produk pada host aplikasi saat ini.
- Layout A4 perlu divalidasi pada browser, printer, jenis kertas, dan skala
  aktual.
- Proyek disiapkan untuk penggunaan lokal/demo. Checklist produksi ada di
  [README](../README.md); deployment tetap membutuhkan konfigurasi, backup,
  pemantauan, pengujian operasional, dan audit keamanan yang sesuai.

## Dokumentasi terkait

- [README proyek dan cara menjalankan](../README.md)
- [Panduan Penggunaan](USER_GUIDE.md)
- [Dokumentasi Database](DATABASE.md)
