@extends('layouts.app')

@section('title', 'Scan Barcode')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">PEMINDAIAN PRODUK</p>
            <h1>Scan barcode atau QR Code</h1>
            <p class="muted">Gunakan scanner USB, ketik SKU, atau pindai dengan kamera perangkat.</p>
        </div>
    </div>

    <section class="panel scanner-panel">
        <form action="{{ route('scanner.lookup') }}" method="POST" data-scanner-form>
            @csrf
            <div class="form-field">
                <label for="barcode">SKU atau hasil scan <span class="required">*</span></label>
                <input id="barcode" name="barcode" type="text" value="{{ old('barcode') }}"
                       maxlength="2048" placeholder="Fokuskan scanner USB di sini atau masukkan SKU"
                       autocomplete="off" required autofocus aria-describedby="barcode-error">
                @error('barcode')
                    <p class="field-error" id="barcode-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="scanner-actions">
                <button class="button button-primary" type="submit">Cari produk</button>
                <button class="button button-secondary" type="button" data-camera-start>Mulai scan kamera</button>
                <button class="button button-secondary" type="button" data-camera-stop hidden>Hentikan kamera</button>
            </div>
        </form>

        <p class="scanner-status muted" data-camera-status role="status" aria-live="polite">
            Untuk pemindaian kamera, izinkan akses kamera dan gunakan halaman melalui localhost atau HTTPS.
        </p>
        <video class="scanner-video" data-camera-video muted playsinline hidden></video>
    </section>
@endsection
