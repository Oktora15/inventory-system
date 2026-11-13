@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <section class="auth-card">
        <p class="eyebrow">SISTEM INFORMASI INVENTORY</p>
        <h1>Selamat datang</h1>
        <p class="muted auth-intro">Masuk untuk melanjutkan ke aplikasi inventory.</p>

        <form action="{{ route('login.store') }}" method="POST">
            @csrf

            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       autocomplete="username" required autofocus aria-describedby="email-error">
                @error('email')
                    <p class="field-error" id="email-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password">Kata sandi</label>
                <input id="password" name="password" type="password"
                       autocomplete="current-password" required aria-describedby="password-error">
                @error('password')
                    <p class="field-error" id="password-error">{{ $message }}</p>
                @enderror
            </div>

            <label class="checkbox-field">
                <input name="remember" type="checkbox" value="1">
                <span>Ingat saya</span>
            </label>

            <button class="button button-primary auth-submit" type="submit">Masuk</button>
        </form>

        <p class="auth-footer"><a href="{{ route('password.request') }}">Lupa kata sandi?</a></p>
    </section>
@endsection
