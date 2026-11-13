@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">PENGATURAN AKUN</p>
            <h1>Profil saya</h1>
            <p class="muted">Ubah nama, email, atau kata sandi akun Anda.</p>
        </div>
    </div>

    <section class="panel form-panel">
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-field">
                <label for="name">Nama</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                       maxlength="255" required autofocus aria-describedby="name-error">
                @error('name')
                    <p class="field-error" id="name-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                       maxlength="255" required aria-describedby="email-error">
                @error('email')
                    <p class="field-error" id="email-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="current_password">Kata sandi saat ini</label>
                <input id="current_password" name="current_password" type="password"
                       autocomplete="current-password" required aria-describedby="current-password-error">
                @error('current_password')
                    <p class="field-error" id="current-password-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password">Kata sandi baru <span class="muted">(opsional)</span></label>
                <input id="password" name="password" type="password" autocomplete="new-password"
                       minlength="8" aria-describedby="password-error">
                @error('password')
                    <p class="field-error" id="password-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       autocomplete="new-password" minlength="8">
            </div>

            <div class="form-actions">
                <button class="button button-primary" type="submit">Simpan profil</button>
            </div>
        </form>
    </section>
@endsection
