@extends('layouts.app')

@section('title', 'Ubah Pengguna')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">ADMINISTRASI</p>
            <h1>Ubah pengguna</h1>
            <p class="muted">Perbarui identitas dan role akun.</p>
        </div>
        <a class="button button-secondary" href="{{ route('users.index') }}">Kembali</a>
    </div>

    @include('users.form')
@endsection
