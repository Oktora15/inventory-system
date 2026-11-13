# Panduan Penggunaan

Panduan ini menjelaskan penggunaan aplikasi inventory pada peran Admin,
Warehouse, dan Kasir. Pastikan aplikasi dan database sudah disiapkan mengikuti
[README proyek](../README.md).

## 1. Menjalankan dan masuk

1. Jalankan `php artisan serve` dan `npm run dev` pada terminal terpisah, atau
   gunakan `composer run dev`.
2. Buka alamat lokal aplikasi.
3. Pengguna yang belum masuk akan diarahkan ke halaman login.
4. Login dengan akun yang sudah dibuat Admin.
5. Tautan reset kata sandi tersedia melalui **Lupa kata sandi?**. Verifikasi
   email tidak diwajibkan.

### Registrasi Kasir

Pengunjung dapat memilih **Daftar** untuk membuat akun mandiri. Form meminta
nama, email unik, dan kata sandi. Role selalu ditetapkan sebagai Kasir oleh
server, akun langsung aktif dan masuk setelah pendaftaran. Role Admin atau
Warehouse tidak dapat dipilih melalui registrasi publik.

### Menyiapkan Admin pertama

Setelah migration selesai, jalankan perintah berikut dari terminal interaktif.
Kata sandi diminta dengan input tersembunyi dan tidak boleh diberikan sebagai
argumen:

```powershell
php artisan app:create-admin "Nama Admin" admin@example.com
```

Perintah hanya dapat digunakan bila belum ada Admin aktif. Akun dapat langsung
digunakan untuk masuk tanpa verifikasi email. Admin yang sudah masuk dapat
membuat akun selanjutnya dari menu **Pengguna**.

### Manajemen akun dan reset kata sandi

Admin dapat membuat akun, memilih role Admin/Warehouse/Kasir, mengubah identitas,
dan mengaktifkan atau menonaktifkan akun. Pengguna baru menerima tautan untuk
mengatur kata sandi. Pengguna dapat mengubah identitas dan kata sandi sendiri
melalui menu **Profil**; kata sandi saat ini wajib dimasukkan untuk menyimpan
perubahan.
Sesi yang masih aktif akan dikeluarkan jika akunnya dinonaktifkan.

Form **Lupa kata sandi?** memberi respons generik untuk menghindari pengungkapan
apakah suatu alamat terdaftar. Tautan hanya dikirim untuk akun aktif.

## 2. Dashboard

Dashboard menampilkan total unit stok, jumlah produk, jumlah kategori,
transaksi masuk/keluar, grafik pergerakan tujuh hari, daftar produk dengan stok
pada atau di bawah batas minimum, dan transaksi terbaru. Tombol tambah produk
tersedia untuk Admin dan Warehouse.

## 3. Kategori

Semua pengguna yang sudah login dapat melihat daftar kategori. Admin dapat:

1. Memilih **Tambah kategori**.
2. Mengisi nama kategori; nama harus unik.
3. Mengisi deskripsi bila diperlukan.
4. Menggunakan aksi **Ubah** atau **Hapus** pada daftar.

Kategori yang masih dipakai produk tidak dapat dihapus. Hapus atau pindahkan
produk terkait lebih dahulu.

## 4. Produk

Admin dan Warehouse dapat membuka menu Produk dan menambah produk dengan
mengisi nama, SKU unik, kategori, harga beli, harga jual, satuan, dan batas
minimum stok. Deskripsi bersifat opsional.

Stok produk dimulai dari nol. Stok tidak diubah saat mengedit data produk;
gunakan transaksi stok agar setiap perubahan memiliki histori. Pengguna
lainnya dapat mencari, melihat daftar, dan membuka detail produk.

## 5. Barang masuk dan keluar

1. Buka **Riwayat stok**, lalu pilih **Barang masuk** atau **Barang keluar**.
2. Pilih produk, tanggal transaksi, dan isi jumlah unit minimal satu.
3. Tambahkan keterangan bila dibutuhkan, lalu simpan.
4. Periksa stok sebelum/sesudah dan pengguna pencatat di riwayat.

Barang masuk menambah stok. Barang keluar mengurangi stok dan ditolak bila
jumlahnya melebihi stok tersedia. Pembaruan stok dan pencatatan riwayat
dilakukan bersama dalam transaksi database.

Riwayat dapat difilter menurut jenis transaksi, produk, dan rentang tanggal.
Kasir hanya dapat melihat transaksi keluar. Produk dengan
riwayat transaksi tidak dapat dihapus, agar catatan pergerakan tidak terputus.
Admin dapat membatalkan transaksi satu kali dengan memberikan alasan minimal
5 karakter. Sistem menambahkan transaksi kebalikan yang mereferensikan catatan
awal dan tidak mengubah histori sebelumnya. Pembatalan barang masuk ditolak
bila stok yang tersedia sudah kurang dari kuantitas transaksi.

## 6. Laporan inventory

Menu **Laporan** menampilkan posisi stok terkini, nilai persediaan (stok saat
ini dikalikan harga beli), jumlah produk menipis, serta pergerakan masuk/keluar.
Filter kategori dan nama/SKU memengaruhi produk dan transaksi terkait. Rentang
tanggal memfilter transaksi pergerakan; ringkasan posisi stok tetap
mencerminkan keadaan saat ini. **Ekspor stok CSV** berisi semua produk hasil
filter dan **Ekspor pergerakan CSV** memuat semua transaksi pada filter
kategori, pencarian, dan tanggal; ekspor tidak terbatas pada halaman yang
sedang terlihat. CSV mengamankan nilai teks yang dapat ditafsirkan sebagai
formula spreadsheet.

Pilih **Unduh PDF** untuk membuat berkas PDF di server. **Tampilan cetak**
membuka HTML ramah-cetak jika ingin menggunakan pengaturan cetak browser.
Ekspor Excel membuat workbook `.xlsx` dengan sheet ringkasan, posisi stok, dan
pergerakan; semua ekspor mengikuti filter yang sedang dipakai.

## 7. Barcode dan QR Code

Pada detail produk:

- Barcode CODE128 berisi SKU, sehingga dapat dibaca scanner barcode.
- QR Code membuka halaman detail produk pada host aplikasi saat ini.
- **Cetak label produk** membuka pratinjau label produk tersebut.

Menu **Label** memungkinkan pencarian, memilih beberapa produk, dan mengatur
jumlah label per produk. Pilih **Buat label terpilih**, lalu **Cetak label**.
Satu proses menerima maksimum 50 produk dan maksimum 100 label keseluruhan.

Layout label menggunakan A4 portrait, dua kolom, ukuran label 94 × 52 mm, dan
margin halaman 8 mm. Pilih skala 100%/actual size pada dialog cetak dan
lakukan uji cetak sebelum mencetak banyak halaman. Model printer dan browser
dapat menafsirkan margin atau ukuran kertas secara berbeda.

## 8. Pemindaian

### Scanner USB

1. Buka menu **Scan**.
2. Fokuskan input **SKU atau hasil scan**.
3. Scan barcode menggunakan scanner USB. Scanner yang mengirim karakter SKU
   akan mengisi input; kirim/tekan Enter untuk mencari.

### Input manual

Ketik SKU persis seperti yang terdaftar lalu pilih **Cari produk**.

### Kamera perangkat

1. Buka aplikasi melalui `localhost` atau HTTPS.
2. Pilih **Mulai scan kamera** dan izinkan akses kamera di browser.
3. Arahkan kamera ke barcode CODE128 atau QR Code produk.
4. Produk yang cocok akan terbuka otomatis; untuk Kasir, form barang keluar
   terbuka dengan produk terpilih. Pilih **Hentikan kamera** untuk mengakhiri
   pemindaian.

Pemindaian kamera bergantung pada dukungan browser, izin perangkat, pencahayaan,
fokus, dan kualitas kode. Jika tidak tersedia, gunakan scanner USB atau input
SKU. QR Code harus menuju host aplikasi yang sedang dibuka.
