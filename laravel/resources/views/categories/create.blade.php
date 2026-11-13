@extends('layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">MASTER DATA</p>
            <h1>Tambah kategori</h1>
            <p class="muted">Isi informasi kategori barang baru.</p>
        </div>
        <a class="button button-secondary" href="{{ route('categories.index') }}">Kembali</a>
    </div>

    @include('categories.form')
@endsection
