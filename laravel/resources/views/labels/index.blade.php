@extends('layouts.app')

@section('title', 'Cetak Label')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">BARCODE & QR CODE</p>
            <h1>Cetak label produk</h1>
            <p class="muted">Pilih produk dan jumlah label, lalu cetak sekaligus.</p>
        </div>
    </div>

    <section class="panel" aria-label="Pilih produk untuk dicetak">
        <div class="panel-heading">
            <h2>Pilih produk</h2>
            <span class="count">{{ $products->total() }} produk</span>
        </div>

        <form class="search-form no-print" method="GET" action="{{ route('labels.index') }}">
            <label class="sr-only" for="label-search">Cari nama atau SKU produk</label>
            <input id="label-search" name="search" type="search" value="{{ $search }}"
                   placeholder="Cari nama atau SKU produk..." maxlength="255">
            <button class="button button-secondary" type="submit">Cari</button>
            @if ($search)
                <a class="button button-secondary" href="{{ route('labels.index') }}">Reset</a>
            @endif
        </form>

        @if ($products->isEmpty())
            <div class="empty-state">
                <h3>Belum ada produk</h3>
                <p class="muted">Tambahkan produk sebelum mencetak label.</p>
            </div>
        @else
            <form action="{{ route('labels.print') }}" method="POST">
                @csrf
                @error('product_ids')
                    <p class="field-error label-error">{{ $message }}</p>
                @enderror
                @error('product_ids.*')
                    <p class="field-error label-error">{{ $message }}</p>
                @enderror
                @error('copies')
                    <p class="field-error label-error">{{ $message }}</p>
                @enderror

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col"><span class="sr-only">Pilih</span></th>
                                <th scope="col">Produk</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Kategori</th>
                                <th scope="col">Stok</th>
                                <th scope="col">Jumlah label</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td>
                                        <input class="label-checkbox" type="checkbox" name="product_ids[]"
                                               value="{{ $product->id }}"
                                               aria-label="Pilih {{ $product->name }}">
                                    </td>
                                    <td class="category-name">
                                        <a class="text-link" href="{{ route('products.show', $product) }}">
                                            {{ $product->name }}
                                        </a>
                                    </td>
                                    <td>{{ $product->sku }}</td>
                                    <td>{{ $product->category->name }}</td>
                                    <td>{{ number_format($product->stock, 0, ',', '.') }}</td>
                                    <td>
                                        <label class="sr-only" for="copies-{{ $product->id }}">
                                            Jumlah label {{ $product->name }}
                                        </label>
                                        <input id="copies-{{ $product->id }}" class="label-copy-input"
                                               type="number" name="copies[{{ $product->id }}]"
                                               min="1" max="20" value="1">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($products->hasPages())
                    <div class="pagination no-print">{{ $products->links() }}</div>
                @endif

                <div class="label-form-actions no-print">
                    <p class="form-help">Maksimal 50 produk dan 100 label per proses cetak.</p>
                    <button class="button button-primary" type="submit">Buat label terpilih</button>
                </div>
            </form>
        @endif
    </section>
@endsection
