@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">ADMINISTRASI</p>
            <h1>Tambah pengguna</h1>
            <p class="muted">Tautan untuk membuat kata sandi dikirim ke alamat email pengguna.</p>
        </div>
        <a class="button button-secondary" href="{{ route('users.index') }}">Kembali</a>
    </div>

    @include('users.form')
@endsection
