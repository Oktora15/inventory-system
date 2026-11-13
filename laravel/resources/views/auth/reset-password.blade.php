@extends('layouts.app')

@section('title', 'Atur Kata Sandi')

@section('content')
    <section class="auth-card">
        <p class="eyebrow">PEMULIHAN AKUN</p>
        <h1>Atur kata sandi baru</h1>
        <p class="muted auth-intro">Tautan ini hanya dapat digunakan sekali dan memiliki masa berlaku.</p>

        <form action="{{ route('password.store') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}"
                       autocomplete="email" required aria-describedby="email-error">
                @error('email')
                    <p class="field-error" id="email-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password">Kata sandi baru</label>
                <input id="password" name="password" type="password" minlength="8"
                       autocomplete="new-password" required aria-describedby="password-error">
                @error('password')
                    <p class="field-error" id="password-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password_confirmation">Konfirmasi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       minlength="8" autocomplete="new-password" required>
            </div>

            <button class="button button-primary auth-submit" type="submit">Simpan kata sandi</button>
        </form>
    </section>
@endsection
