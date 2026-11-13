@extends('layouts.app')

@section('title', 'Laporan Inventory')

@section('content')
    <div class="page-heading report-heading">
        <div>
            <p class="eyebrow">LAPORAN</p>
            <h1>Laporan inventory</h1>
            <p class="muted">Ringkasan stok terkini dan pergerakan barang pada periode yang dipilih.</p>
        </div>
        <div class="heading-actions no-print">
            <a class="button button-secondary" href="{{ route('reports.inventory.products-csv', request()->query()) }}">
                Ekspor stok CSV
            </a>
            <a class="button button-secondary" href="{{ route('reports.inventory.movements-csv', request()->query()) }}">
                Ekspor pergerakan CSV
            </a>
            <a class="button button-secondary" href="{{ route('reports.inventory.excel', request()->query()) }}">
                Ekspor Excel
            </a>
            <a class="button button-primary" href="{{ route('reports.inventory.pdf', request()->query()) }}">
                Unduh PDF
            </a>
            <a class="button button-secondary" href="{{ route('reports.inventory.print', request()->query()) }}" target="_blank" rel="noopener">
                Tampilan cetak
            </a>
        </div>
    </div>

    <form class="panel report-filters no-print" method="GET" action="{{ route('reports.inventory') }}">
        <div class="report-filter-field">
            <label for="search">Nama produk atau SKU</label>
            <input id="search" name="search" type="search" value="{{ $search }}"
                   maxlength="255" placeholder="Cari produk...">
        </div>

        <div class="report-filter-field">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-field">
            <label for="date_from">Transaksi dari tanggal</label>
            <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}"
                   aria-describedby="date-from-error">
            @error('date_from')
                <p class="field-error" id="date-from-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="report-filter-field">
            <label for="date_to">Sampai tanggal</label>
            <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}"
                   aria-describedby="date-to-error">
            @error('date_to')
                <p class="field-error" id="date-to-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="report-filter-actions">
            <button class="button button-primary" type="submit">Terapkan filter</button>
            <a class="button button-secondary" href="{{ route('reports.inventory') }}">Reset</a>
        </div>
    </form>

    <section class="summary-grid report-summary" aria-label="Ringkasan laporan inventory">
        <article class="summary-card">
            <span class="summary-label">Produk</span>
            <strong class="summary-value">{{ number_format((int) $inventorySummary->product_count, 0, ',', '.') }}</strong>
            <span class="summary-note">Sesuai kategori dan pencarian</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Total stok</span>
            <strong class="summary-value">{{ number_format((int) $inventorySummary->total_stock, 0, ',', '.') }}</strong>
            <span class="summary-note">Unit tersedia saat ini</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Nilai persediaan</span>
            <strong class="summary-value summary-currency">
                Rp{{ number_format((float) $inventorySummary->inventory_value, 0, ',', '.') }}
            </strong>
            <span class="summary-note">Stok saat ini × harga beli</span>
        </article>
        <article class="summary-card {{ (int) $inventorySummary->low_stock_count > 0 ? 'summary-warning' : '' }}">
            <span class="summary-label">Stok menipis</span>
            <strong class="summary-value">{{ number_format((int) $inventorySummary->low_stock_count, 0, ',', '.') }}</strong>
            <span class="summary-note">Produk pada atau di bawah batas minimum</span>
        </article>
    </section>

    <section class="summary-grid movement-summary" aria-label="Ringkasan pergerakan stok">
        <article class="summary-card movement-card">
            <span class="summary-label">Barang masuk</span>
            <strong class="summary-value stock-ok">
                {{ number_format((int) $movementSummary->total_in, 0, ',', '.') }}
            </strong>
            <span class="summary-note">Unit pada periode/filter transaksi</span>
        </article>
        <article class="summary-card movement-card">
            <span class="summary-label">Barang keluar</span>
            <strong class="summary-value stock-low">
                {{ number_format((int) $movementSummary->total_out, 0, ',', '.') }}
            </strong>
            <span class="summary-note">Unit pada periode/filter transaksi</span>
        </article>
        <article class="summary-card movement-card">
            <span class="summary-label">Transaksi</span>
            <strong class="summary-value">{{ number_format((int) $movementSummary->transaction_count, 0, ',', '.') }}</strong>
            <span class="summary-note">Jumlah pergerakan stok tercatat</span>
        </article>
    </section>

    <section class="panel report-panel" aria-label="Daftar stok produk">
        <div class="panel-heading">
            <h2>Posisi stok terkini</h2>
            <span class="count">{{ $products->total() }} produk</span>
        </div>

        @if ($products->isEmpty())
            <div class="empty-state">
                <h3>Tidak ada produk untuk ditampilkan</h3>
                <p class="muted">Ubah filter atau tambahkan produk ke inventory.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Produk</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Stok</th>
                            <th scope="col">Satuan</th>
                            <th scope="col">Batas minimum</th>
                            <th scope="col">Nilai stok</th>
                            <th scope="col">Status</th>
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
                                <td>{{ number_format($product->stock, 0, ',', '.') }}</td>
                                <td>{{ $product->unit }}</td>
                                <td>{{ number_format($product->minimum_stock, 0, ',', '.') }}</td>
                                <td>Rp{{ number_format($product->stock * (float) $product->purchase_price, 0, ',', '.') }}</td>
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
                <div class="pagination no-print">{{ $products->links() }}</div>
            @endif
        @endif
    </section>

    <section class="panel report-panel" aria-label="Pergerakan stok dalam periode">
        <div class="panel-heading">
            <h2>Pergerakan stok</h2>
            <span class="count">{{ $transactions->total() }} transaksi</span>
        </div>

        @if ($transactions->isEmpty())
            <div class="empty-state">
                <h3>Tidak ada transaksi pada filter ini</h3>
                <p class="muted">Coba rentang tanggal atau kategori yang berbeda.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Produk</th>
                            <th scope="col">Jenis</th>
                            <th scope="col">Jumlah</th>
                            <th scope="col">Stok sebelum</th>
                            <th scope="col">Stok sesudah</th>
                            <th scope="col">Pengguna</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td class="muted">{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td class="category-name">
                                    <a class="text-link" href="{{ route('products.show', $transaction->product) }}">
                                        {{ $transaction->product->name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="status-badge {{ $transaction->type === 'in' ? 'status-good' : 'status-warning' }}">
                                        {{ $transaction->type === 'in' ? 'Masuk' : 'Keluar' }}
                                    </span>
                                </td>
                                <td>{{ number_format($transaction->quantity, 0, ',', '.') }}</td>
                                <td>{{ number_format($transaction->stock_before, 0, ',', '.') }}</td>
                                <td>{{ number_format($transaction->stock_after, 0, ',', '.') }}</td>
                                <td>{{ $transaction->user->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="pagination no-print">{{ $transactions->links() }}</div>
            @endif
        @endif
    </section>
@endsection
