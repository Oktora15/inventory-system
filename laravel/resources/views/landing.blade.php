@extends('layouts.app')

@section('title', 'Sistem Informasi Inventory')

@section('content')
    <section class="landing-hero">
        <div class="landing-copy">
            <p class="eyebrow">MANAJEMEN STOK TERINTEGRASI</p>
            <h1>Kelola persediaan dengan lebih teratur.</h1>
            <p class="muted">
                Pantau produk, catat barang masuk dan keluar, serta telusuri riwayat
                pergerakan stok dari satu aplikasi inventory.
            </p>
            <div class="landing-actions">
                <a class="button button-primary" href="{{ route('login') }}">Masuk ke aplikasi</a>
                <a class="button button-secondary" href="{{ route('register') }}">Daftar sebagai Kasir</a>
            </div>
        </div>
        <div class="landing-features" aria-label="Fitur aplikasi">
            <article class="landing-feature">
                <strong>Data stok terpusat</strong>
                <span>Produk, kategori, satuan, dan batas minimum dalam satu tempat.</span>
            </article>
            <article class="landing-feature">
                <strong>Pergerakan tercatat</strong>
                <span>Setiap transaksi menyimpan tanggal serta snapshot stok.</span>
            </article>
            <article class="landing-feature">
                <strong>Laporan dan barcode</strong>
                <span>Telusuri inventory, cetak label, dan cari produk melalui scan.</span>
            </article>
        </div>
    </section>
@endsection
