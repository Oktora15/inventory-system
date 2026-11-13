@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">DETAIL PRODUK</p>
            <h1>{{ $product->name }}</h1>
            <p class="muted">SKU {{ $product->sku }}</p>
        </div>
        <div class="heading-actions">
            @if (auth()->user()->canManageProducts())
                <a class="button button-primary" href="{{ route('products.edit', $product) }}">Ubah produk</a>
            @endif
            <a class="button button-secondary" href="{{ route('products.index') }}">Kembali</a>
        </div>
    </div>

    <section class="panel product-detail">
        <dl class="detail-grid">
            <div>
                <dt>Kategori</dt>
                <dd>{{ $product->category->name }}</dd>
            </div>
            <div>
                <dt>Stok saat ini</dt>
                <dd>
                    <span class="{{ $product->stock <= $product->minimum_stock ? 'stock-low' : 'stock-ok' }}">
                        {{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit }}
                    </span>
                </dd>
            </div>
            <div>
                <dt>Harga beli</dt>
                <dd>Rp{{ number_format((float) $product->purchase_price, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt>Harga jual</dt>
                <dd>Rp{{ number_format((float) $product->selling_price, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt>Batas minimum stok</dt>
                <dd>{{ number_format($product->minimum_stock, 0, ',', '.') }} {{ $product->unit }}</dd>
            </div>
            <div>
                <dt>Diperbarui</dt>
                <dd>{{ $product->updated_at->format('d M Y H:i') }}</dd>
            </div>
            <div class="detail-wide">
                <dt>Transaksi stok</dt>
                <dd class="heading-actions">
                    @if (auth()->user()->role !== \App\Models\User::ROLE_CASHIER)
                        <a class="button button-secondary button-small"
                           href="{{ route('stock-transactions.create', ['type' => 'in', 'product_id' => $product->id]) }}">
                            Catat barang masuk
                        </a>
                    @endif
                    <a class="button button-primary button-small"
                       href="{{ route('stock-transactions.create', ['type' => 'out', 'product_id' => $product->id]) }}">
                        Catat barang keluar
                    </a>
                    <a class="button button-secondary button-small"
                       href="{{ route('stock-transactions.index', ['product_id' => $product->id]) }}">
                        Lihat riwayat
                    </a>
                </dd>
            </div>
            <div class="detail-wide">
                <dt>Barcode & QR Code</dt>
                <dd class="product-codes">
                    <div class="product-code-card">
                        <span class="product-code-title">Barcode (SKU)</span>
                        <svg data-barcode-value="{{ $product->sku }}"></svg>
                    </div>
                    <div class="product-code-card">
                        <span class="product-code-title">QR Code detail produk</span>
                        <canvas data-qr-value="{{ route('products.show', $product) }}"></canvas>
                    </div>
                    <p class="code-error" data-code-error role="status"></p>
                    @if (auth()->user()->canManageProducts())
                        <form action="{{ route('labels.print') }}" method="POST" class="no-print">
                            @csrf
                            <input type="hidden" name="product_ids[]" value="{{ $product->id }}">
                            <button class="button button-secondary button-small" type="submit">
                                Cetak label produk
                            </button>
                        </form>
                    @endif
                </dd>
            </div>
            <div class="detail-wide">
                <dt>Deskripsi</dt>
                <dd class="detail-description">{{ $product->description ?: 'Belum ada deskripsi.' }}</dd>
            </div>
        </dl>

        @if (auth()->user()->canManageProducts())
            <form class="detail-delete" action="{{ route('products.destroy', $product) }}" method="POST"
                  onsubmit="return confirm('Hapus produk ini?')">
                @csrf
                @method('DELETE')
                <button class="button button-danger" type="submit">Hapus produk</button>
            </form>
        @endif
    </section>
@endsection
