@extends('layouts.app')

@section('title', 'Ubah Produk')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">MASTER DATA</p>
            <h1>Ubah produk</h1>
            <p class="muted">Perbarui data dasar produk. Perubahan stok dilakukan lewat transaksi.</p>
        </div>
        <a class="button button-secondary" href="{{ route('products.show', $product) }}">Kembali</a>
    </div>

    @include('products.form')
@endsection
