@extends('layouts.app')

@section('title', 'Produk')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">MASTER DATA</p>
            <h1>Produk</h1>
            <p class="muted">Lihat dan kelola data produk inventory.</p>
        </div>
        @if (auth()->user()->canManageProducts())
            <div class="heading-actions">
                <a class="button button-secondary" href="{{ route('labels.index') }}">Cetak label</a>
                <a class="button button-primary" href="{{ route('products.create') }}">Tambah produk</a>
            </div>
        @else
            <a class="button button-secondary" href="{{ route('labels.index') }}">Cetak label</a>
        @endif
    </div>

    <section class="panel" aria-label="Daftar produk">
        <div class="panel-heading">
            <h2>Daftar produk</h2>
            <span class="count">{{ $products->total() }} produk</span>
        </div>

        <form class="search-form" method="GET" action="{{ route('products.index') }}">
            <label class="sr-only" for="search">Cari nama atau SKU produk</label>
            <input id="search" name="search" type="search" value="{{ $search }}"
                   placeholder="Cari nama atau SKU produk..." maxlength="255">
            <label class="sr-only" for="category-filter">Filter kategori</label>
            <select id="category-filter" name="category_id">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <button class="button button-secondary" type="submit">Cari</button>
            @if ($search || $categoryId)
                <a class="button button-secondary" href="{{ route('products.index') }}">Reset</a>
            @endif
        </form>

        @if ($products->isEmpty())
            <div class="empty-state">
                <h3>{{ $search ? 'Produk tidak ditemukan' : 'Belum ada produk' }}</h3>
                <p class="muted">
                    {{ $search ? 'Coba kata kunci nama atau SKU yang lain.' : 'Produk yang tersedia akan tampil di sini.' }}
                </p>
                @if (! $search && ! $categoryId && auth()->user()->canManageProducts())
                    <a class="button button-primary" href="{{ route('products.create') }}">Tambah produk</a>
                @endif
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Produk</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Harga jual</th>
                            <th scope="col">Stok</th>
                            <th scope="col">Satuan</th>
                            <th scope="col">Status stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td class="category-name">
                                    <a class="text-link" href="{{ route('products.show', $product) }}">
                                        {{ $product->name }}
                                    </a>
                                </td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->category->name }}</td>
                                <td>Rp{{ number_format((float) $product->selling_price, 0, ',', '.') }}</td>
                                <td>{{ number_format($product->stock, 0, ',', '.') }}</td>
                                <td>{{ $product->unit }}</td>
                                <td>
                                    @if ($product->stock <= $product->minimum_stock)
                                        <span class="status-badge status-warning">Menipis</span>
                                    @else
                                        <span class="status-badge status-good">Tersedia</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="pagination">{{ $products->links() }}</div>
            @endif
        @endif
    </section>
@endsection
