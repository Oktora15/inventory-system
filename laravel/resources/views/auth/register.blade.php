@extends('layouts.app')

@section('title', 'Daftar Akun')

@section('content')
    <section class="auth-card">
        <p class="eyebrow">AKUN PENGGUNA</p>
        <h1>Buat akun Kasir</h1>
        <p class="muted auth-intro">Pendaftaran mandiri membuat akun Kasir. Akses tambahan hanya dapat diberikan Admin.</p>

        <form action="{{ route('register.store') }}" method="POST">
            @csrf
            <div class="form-field">
                <label for="name">Nama</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                       maxlength="255" autocomplete="name" required autofocus aria-describedby="name-error">
                @error('name')
                    <p class="field-error" id="name-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       maxlength="255" autocomplete="email" required aria-describedby="email-error">
                @error('email')
                    <p class="field-error" id="email-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-field">
                <label for="password">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="new-password"
                       minlength="8" required aria-describedby="password-error">
                @error('password')
                    <p class="field-error" id="password-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-field">
                <label for="password_confirmation">Konfirmasi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       autocomplete="new-password" minlength="8" required>
            </div>
            <button class="button button-primary auth-submit" type="submit">Daftar</button>
        </form>

        <p class="auth-footer">Sudah memiliki akun? <a href="{{ route('login') }}">Masuk</a></p>
    </section>
@endsection
