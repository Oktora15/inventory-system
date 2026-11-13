@extends('layouts.app')

@section('title', 'Ubah Kategori')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">MASTER DATA</p>
            <h1>Ubah kategori</h1>
            <p class="muted">Perbarui informasi kategori barang.</p>
        </div>
        <a class="button button-secondary" href="{{ route('categories.index') }}">Kembali</a>
    </div>

    @include('categories.form')
@endsection
