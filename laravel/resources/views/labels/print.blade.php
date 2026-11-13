@extends('layouts.app')

@section('title', 'Pratinjau Label')

@section('content')
    <div class="page-heading no-print">
        <div>
            <p class="eyebrow">BARCODE & QR CODE</p>
            <h1>Pratinjau label</h1>
            <p class="muted">{{ $labels->count() }} label siap dicetak.</p>
        </div>
        <div class="heading-actions">
            <button class="button button-primary" type="button" data-print-action>Cetak label</button>
            <a class="button button-secondary" href="{{ route('labels.index') }}">Pilih produk lain</a>
        </div>
    </div>

    <div class="label-print-grid" aria-label="Pratinjau label produk">
        @foreach ($labels as $product)
            <article class="print-label">
                <strong class="print-label-name">{{ $product->name }}</strong>
                <svg class="label-barcode" data-barcode-value="{{ $product->sku }}"></svg>
                <span class="print-label-sku">{{ $product->sku }}</span>
                <canvas class="label-qr" data-qr-value="{{ route('products.show', $product) }}"></canvas>
                <span class="print-label-hint">Scan QR untuk detail produk</span>
                <p class="code-error" data-code-error role="status"></p>
            </article>
        @endforeach
    </div>
@endsection
