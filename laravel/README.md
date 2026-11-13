# Sistem Informasi Inventory

Aplikasi inventory berbasis Laravel 13, Blade, dan SQLite. Fitur yang sudah
tersedia meliputi landing page, registrasi Kasir, autentikasi, profil, role
Admin/Warehouse/Kasir, dashboard, kategori, produk, transaksi stok, laporan,
barcode, QR Code, cetak label, dan pemindaian.

## Persyaratan

- PHP 8.3 atau lebih baru dengan ekstensi PHP yang dibutuhkan Laravel dan SQLite.
- Composer.
- Node.js dan npm.

## Menyiapkan aplikasi

Jalankan perintah dari folder proyek Laravel (`laravel/`):

```powershell
composer install
npm.cmd install
```

Pastikan file `.env` lokal sudah tersedia. Jika belum, salin `.env.example`
menjadi `.env` tanpa menimpa file `.env` yang sudah ada, lalu buat application
key jika belum tersedia:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Konfigurasi database aplikasi menggunakan SQLite. Jangan mengubah konfigurasi
database lokal tanpa memastikan kebutuhan lingkungan. Bila berkas database pada
lokasi yang dikonfigurasi belum ada, buat berkas SQLite kosong di lokasi
tersebut, lalu jalankan migration:

```powershell
php artisan migrate
```

Migration menyiapkan tabel pengguna, kategori, produk, dan riwayat transaksi.
Perintah `migrate` tidak sama dengan `migrate:fresh`: jangan gunakan perintah
yang menghapus tabel pada database yang berisi data.

## Menjalankan aplikasi

Untuk development, jalankan server dan Vite:

```powershell
php artisan serve
```

Di terminal lain:

```powershell
npm.cmd run dev
```

Buka alamat lokal yang ditampilkan Artisan, biasanya `http://127.0.0.1:8000`.
Alternatifnya, `composer run dev` menjalankan server, queue worker, log viewer,
dan Vite secara bersamaan.

## Akun, role, dan email

Pendaftaran publik ditutup. Buat Admin pertama melalui terminal interaktif:

```powershell
php artisan app:create-admin "Nama Admin" admin@example.com
```

Kata sandi diminta melalui prompt tersembunyi dan konfirmasi; jangan menaruhnya
di argumen perintah atau membagikannya. Verifikasi email tidak diwajibkan untuk
masuk ke aplikasi.

Pengunjung dapat mendaftar akun Kasir. Pendaftaran publik tidak menyediakan
pilihan role; role Admin dan Warehouse hanya diberikan Admin melalui menu
**Pengguna**.
Admin juga dapat membuat akun Admin, Warehouse, atau Kasir melalui menu **Pengguna**.
Warehouse dapat mengelola produk dan mencatat stok masuk/keluar. Kasir hanya
dapat melihat produk dan mencatat/melihat transaksi barang keluar. Sistem mengirim
tautan untuk membuat kata sandi. Admin dapat mengaktifkan/nonaktifkan akun,
mengubah role, dan mengirim ulang tautan reset.
Satu Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan rolenya.

Fitur **Lupa kata sandi** mengirim tautan reset untuk akun aktif. Fitur ini
memerlukan mailer yang berfungsi.

## Penggunaan

- **Kategori:** buka menu Kategori untuk melihat data. Admin dapat menambah,
  mengubah, dan menghapus kategori yang belum dipakai produk.
- **Produk:** Admin dan Warehouse dapat mengelola produk, menetapkan SKU unik,
  kategori, satuan, deskripsi, harga, stok, dan batas stok minimum. Stok awal
  selalu nol dan tidak dapat diubah dari form produk.
- **Transaksi:** catat barang masuk atau keluar melalui menu Riwayat stok.
  Catat tanggal transaksi, kuantitas, dan keterangan. Stok diperbarui bersama
  pencatatan transaksi; barang keluar ditolak jika stok tidak mencukupi. Admin
  dapat membatalkan transaksi dengan alasan wajib.
  Pembatalan membuat transaksi pembalik yang terhubung; catatan awal tidak
  diubah. Pembatalan barang masuk ditolak bila stok sekarang tidak cukup.
- **Laporan:** lihat posisi stok terkini dan ringkasan pergerakan transaksi.
  Filter mencakup nama/SKU, kategori, dan rentang tanggal untuk pergerakan.
- **Ekspor:** unduh CSV posisi stok, CSV pergerakan, Excel `.xlsx` dengan sheet
  ringkasan/stok/pergerakan, atau PDF server-side. Tampilan cetak browser juga
  tersedia. Ekspor mengikuti filter saat ini.
- **Barcode dan QR:** halaman produk menampilkan barcode CODE128 berbasis SKU
  dan QR Code menuju detail produk. Halaman Label mendukung pencetakan beberapa
  produk sekaligus; proses cetak dibatasi 50 produk dan 100 label.
- **Label:** layout cetak A4 dua kolom (94 × 52 mm per label). Uji kertas,
  margin, dan skala printer sebelum mencetak banyak label.
- **Scanner:** gunakan scanner USB atau input SKU di menu Scan. Kamera dapat
  digunakan jika browser mendukung akses kamera, pengguna memberi izin, dan
  aplikasi diakses melalui localhost atau HTTPS. Hasil scan Kasir langsung
  membuka form transaksi barang keluar dengan produk terpilih.

Panduan langkah demi langkah tersedia di [Panduan Penggunaan](docs/USER_GUIDE.md).
Relasi, tabel, kolom, dan foreign key dijelaskan di
[Dokumentasi Database](docs/DATABASE.md).

## Pengujian dan build

```powershell
php artisan test
vendor/bin/pint --test
npm.cmd run build
```

Laporan tahapan implementasi dan ruang lingkup fitur tersedia di
[Laporan Pengembangan](docs/DEVELOPMENT_REPORT.md).

## Checklist sebelum produksi

- Atur `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, dan application key
  unik melalui pengelolaan rahasia deployment; jangan masukkan rahasia ke Git.
- Siapkan mailer untuk reset dan tautan pengaturan kata sandi, lalu uji
  pengiriman ke alamat yang benar.
- Pastikan SQLite berada pada storage persisten dengan hak akses minimum,
  backup terjadwal, dan uji pemulihan backup. Jangan menyalin database produksi
  ke repositori.
- Jalankan migration dengan proses deployment terencana setelah backup;
  gunakan `php artisan migrate --force`, bukan `migrate:fresh`.
- Buat Admin awal menggunakan prompt aman dan siapkan lebih dari satu Admin
  tepercaya untuk pemulihan akun.
- Jalankan test, Pint, build aset, dan pemeriksaan `php artisan migrate:status`.
- Aktifkan monitoring/log rotation, HTTPS, kebijakan backup dan retensi,
  pembaruan dependensi, serta uji alur scanner/printer pada perangkat sasaran.
- Uji beban dan operasional pada data realistis sebelum membuka aplikasi.
  Aplikasi ini disiapkan untuk lokal/demo; checklist bukan pengganti audit
  keamanan dan kesiapan deployment produksi.

## Bantuan coding

GitHub Copilot dapat membantu eksplorasi dan penulisan kode. Perubahan yang
dihasilkan tetap perlu ditinjau, diuji, dan disesuaikan dengan pola Laravel
proyek sebelum diterapkan.
