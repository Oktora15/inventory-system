@extends('layouts.app')

@section('title', 'Lupa Kata Sandi')

@section('content')
    <section class="auth-card">
        <p class="eyebrow">PEMULIHAN AKUN</p>
        <h1>Lupa kata sandi?</h1>
        <p class="muted auth-intro">Masukkan email akun aktif untuk menerima tautan pengaturan ulang.</p>

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       autocomplete="email" required autofocus aria-describedby="email-error">
                @error('email')
                    <p class="field-error" id="email-error">{{ $message }}</p>
                @enderror
            </div>
            <button class="button button-primary auth-submit" type="submit">Kirim tautan</button>
        </form>

        <p class="auth-footer"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
    </section>
@endsection
