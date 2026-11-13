@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">MASTER DATA</p>
            <h1>Tambah produk</h1>
            <p class="muted">Stok awal produk dibuat 0 dan dicatat melalui transaksi stok.</p>
        </div>
        <a class="button button-secondary" href="{{ route('products.index') }}">Kembali</a>
    </div>

    @include('products.form')
@endsection
