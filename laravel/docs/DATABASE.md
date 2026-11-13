# Dokumentasi Database

Database aplikasi menggunakan SQLite sesuai konfigurasi lokal. Struktur di
bawah ini berasal dari migration yang ada; jangan menjalankan `migrate:fresh`
atau menghapus database untuk menerapkan perubahan aplikasi.

## Relasi utama

```text
categories 1 ─── * products 1 ─── * stock_transactions * ─── 1 users
                                      │
                                      └── reversal_of_id ── stock_transactions.id
```

- Satu kategori mempunyai banyak produk. Produk tidak dapat menghapus kategori
  yang masih dipakai.
- Satu produk mempunyai banyak transaksi pergerakan stok.
- Satu pengguna dapat mencatat banyak transaksi; pengguna dengan histori tidak
  dapat dihapus.
- Transaksi reversal menunjuk transaksi asal. Kolom tersebut unik sehingga
  satu transaksi asal hanya dapat dibatalkan satu kali.

## Tabel aplikasi

### `users`

| Kolom | Keterangan |
|---|---|
| `id` | Primary key. |
| `name`, `email`, `password` | Identitas dan kredensial; `email` unik, kata sandi disimpan dalam hash. |
| `email_verified_at` | Kolom bawaan Laravel, tetap nullable untuk kompatibilitas; verifikasi email tidak diwajibkan aplikasi. |
| `role` | `admin`, `warehouse`, atau `cashier`; default migration adalah `cashier`. |
| `is_active` | Status akun yang diperiksa middleware dan proses login. |
| `remember_token`, `created_at`, `updated_at` | Token sesi ingat dan timestamps Laravel. |

### `categories`

| Kolom | Keterangan |
|---|---|
| `id` | Primary key. |
| `name` | Nama kategori unik. |
| `description` | Deskripsi opsional. |
| `created_at`, `updated_at` | Timestamps Laravel. |

### `products`

| Kolom | Keterangan |
|---|---|
| `id` | Primary key. |
| `category_id` | Foreign key ke `categories.id`, delete dibatasi. |
| `sku` | Kode produk unik; digunakan sebagai nilai barcode. |
| `name`, `description` | Nama dan deskripsi produk. |
| `purchase_price`, `selling_price` | Harga desimal dua digit. |
| `stock` | Stok saat ini, bilangan unsigned, default 0. Diubah lewat transaksi stok. |
| `minimum_stock` | Batas stok minimum, default 5. |
| `unit` | Satuan barang, default `unit`. |
| `created_at`, `updated_at` | Timestamps Laravel. |

### `stock_transactions`

| Kolom | Keterangan |
|---|---|
| `id` | Primary key. |
| `product_id` | Foreign key ke `products.id`, delete dibatasi. |
| `user_id` | Foreign key ke `users.id`, delete dibatasi. |
| `type` | `in` untuk barang masuk atau `out` untuk barang keluar. |
| `quantity` | Kuantitas pergerakan, bilangan unsigned. |
| `stock_before`, `stock_after` | Snapshot stok sebelum dan sesudah perubahan. |
| `transaction_date` | Tanggal transaksi inventory; data lama diisi dari `created_at` saat migration. |
| `notes` | Keterangan opsional. |
| `reversal_of_id` | Foreign key nullable ke transaksi asal; unik dan delete dibatasi. |
| `created_at`, `updated_at` | Waktu pencatatan sistem. |

Index tersedia untuk pencarian produk/tanggal, tipe/tanggal, serta
`transaction_date`/tipe.

## Tabel pendukung Laravel

- `password_reset_tokens`: token pengaturan ulang kata sandi.
- `sessions`: sesi, bila session driver menggunakannya.
- `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`: infrastruktur
  cache dan queue sesuai driver yang dikonfigurasi.

## Menerapkan migration

Periksa status migration terlebih dahulu:

```powershell
php artisan migrate:status
```

Pada deployment terencana setelah backup, migration baru dapat diterapkan tanpa
reset data:

```powershell
php artisan migrate --force
```

Jangan gunakan `migrate:fresh`, `migrate:refresh`, atau menghapus file SQLite
untuk deployment yang memuat data.
