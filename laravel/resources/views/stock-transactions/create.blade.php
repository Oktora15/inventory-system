@extends('layouts.app')

@section('title', $type === 'in' ? 'Barang Masuk' : 'Barang Keluar')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">PERGERAKAN INVENTORY</p>
            <h1>{{ $type === 'in' ? 'Catat barang masuk' : 'Catat barang keluar' }}</h1>
            <p class="muted">Stok produk diperbarui otomatis dan riwayat transaksi dicatat.</p>
        </div>
        <a class="button button-secondary" href="{{ route('stock-transactions.index') }}">Kembali</a>
    </div>

    <section class="panel form-panel">
        @if ($products->isEmpty())
            <div class="alert-warning" role="alert">
                Belum ada produk. Tambahkan produk terlebih dahulu sebelum mencatat transaksi stok.
            </div>
        @endif

        <form class="search-form transaction-sku-search" method="GET" action="{{ route('stock-transactions.create') }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <label for="sku">Scan / masukkan SKU</label>
            <input id="sku" name="sku" type="search" value="{{ $selectedSku ?? '' }}"
                   placeholder="SKU dari scanner..." maxlength="100">
            <button class="button button-secondary" type="submit">Cari produk</button>
            @error('sku')
                <p class="field-error" role="alert">{{ $message }}</p>
            @enderror
        </form>

        <form action="{{ route('stock-transactions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="form-field">
                <label for="product_id">Produk <span class="required">*</span></label>
                <select id="product_id" name="product_id" required aria-describedby="product-error">
                    <option value="">Pilih produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                            @selected((string) old('product_id', $selectedProductId) === (string) $product->id)>
                            {{ $product->name }} ({{ $product->sku }}) — stok {{ $product->stock }} {{ $product->unit }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="field-error" id="product-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="transaction_date">Tanggal transaksi <span class="required">*</span></label>
                <input id="transaction_date" name="transaction_date" type="date"
                       value="{{ old('transaction_date', now()->toDateString()) }}"
                       max="{{ now()->toDateString() }}" required aria-describedby="transaction-date-error">
                @error('transaction_date')
                    <p class="field-error" id="transaction-date-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="quantity">Jumlah ({{ $selectedProductId ? $products->firstWhere('id', $selectedProductId)?->unit ?? 'unit' : 'unit' }}) <span class="required">*</span></label>
                <input id="quantity" name="quantity" type="number" value="{{ old('quantity') }}"
                       min="1" max="2147483647" step="1" required autofocus aria-describedby="quantity-error">
                @error('quantity')
                    <p class="field-error" id="quantity-error">{{ $message }}</p>
                @enderror
                @if ($type === 'out')
                    <p class="form-help">Jumlah barang keluar tidak boleh melebihi stok tersedia.</p>
                @endif
            </div>

            <div class="form-field">
                <label for="notes">Catatan <span class="muted">(opsional)</span></label>
                <textarea id="notes" name="notes" rows="4" maxlength="2000"
                          aria-describedby="notes-error">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="field-error" id="notes-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('stock-transactions.index') }}">Batal</a>
                <button class="button {{ $type === 'in' ? 'button-primary' : 'button-danger' }}"
                        type="submit" @disabled($products->isEmpty())>
                    Simpan barang {{ $type === 'in' ? 'masuk' : 'keluar' }}
                </button>
            </div>
        </form>
    </section>
@endsection
