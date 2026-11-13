@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">RINGKASAN INVENTORY</p>
            <h1>Dashboard</h1>
            <p class="muted">Pantau kondisi produk dan pergerakan stok.</p>
        </div>
        @if (auth()->user()->canManageProducts())
            <a class="button button-primary" href="{{ route('products.create') }}">Tambah produk</a>
        @endif
    </div>

    <section class="summary-grid dashboard-summary" aria-label="Ringkasan inventory">
        <article class="summary-card">
            <span class="summary-label">Total produk</span>
            <strong class="summary-value">{{ number_format($totalProducts, 0, ',', '.') }}</strong>
            <span class="summary-note">Produk terdaftar</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Total kategori</span>
            <strong class="summary-value">{{ number_format($totalCategories, 0, ',', '.') }}</strong>
            <span class="summary-note">Kategori barang terdaftar</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Total stok</span>
            <strong class="summary-value">{{ number_format($totalStock, 0, ',', '.') }}</strong>
            <span class="summary-note">Jumlah seluruh satuan barang</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Transaksi masuk</span>
            <strong class="summary-value">{{ number_format($stockInCount, 0, ',', '.') }}</strong>
            <span class="summary-note">Transaksi sepanjang waktu</span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Transaksi keluar</span>
            <strong class="summary-value">{{ number_format($stockOutCount, 0, ',', '.') }}</strong>
            <span class="summary-note">Transaksi sepanjang waktu</span>
        </article>
        <article class="summary-card {{ $lowStockCount > 0 ? 'summary-warning' : '' }}">
            <span class="summary-label">Stok menipis</span>
            <strong class="summary-value">{{ number_format($lowStockCount, 0, ',', '.') }}</strong>
            <span class="summary-note">Produk pada atau di bawah batas minimum</span>
        </article>
    </section>

    <section class="panel dashboard-panel" aria-label="Grafik pergerakan stok tujuh hari">
        <div class="panel-heading">
            <h2>Barang masuk dan keluar · 7 hari</h2>
            <span class="count">Jumlah unit per tanggal transaksi</span>
        </div>
        @php($chartMax = max(1, (int) $movementChart->max(fn ($day) => max($day['in'], $day['out']))))
        <div class="movement-chart" role="img" aria-label="Grafik jumlah barang masuk dan keluar tujuh hari terakhir">
            @foreach ($movementChart as $day)
                <div class="chart-day" aria-label="{{ $day['label'] }}: masuk {{ $day['in'] }}, keluar {{ $day['out'] }}">
                    <div class="chart-bars">
                        <span class="chart-bar chart-bar-in" style="height: {{ max(2, (int) round($day['in'] / $chartMax * 100)) }}%"
                              title="Masuk: {{ $day['in'] }}"></span>
                        <span class="chart-bar chart-bar-out" style="height: {{ max(2, (int) round($day['out'] / $chartMax * 100)) }}%"
                              title="Keluar: {{ $day['out'] }}"></span>
                    </div>
                    <span class="chart-label">{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="chart-legend">
            <span><i class="legend-dot legend-in"></i>Barang masuk</span>
            <span><i class="legend-dot legend-out"></i>Barang keluar</span>
        </div>
    </section>

    <section class="panel dashboard-panel" aria-label="Produk dengan stok minimum">
        <div class="panel-heading">
            <h2>Perlu restok</h2>
            <a class="text-link" href="{{ route('reports.inventory') }}">Lihat laporan</a>
        </div>
        @if ($lowStockProducts->isEmpty())
            <div class="empty-state">
                <h3>Semua stok aman</h3>
                <p class="muted">Belum ada produk pada atau di bawah batas stok minimum.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Produk</th><th>SKU</th><th>Kategori</th><th>Stok</th><th>Batas minimum</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($lowStockProducts as $product)
                            <tr>
                                <td><a class="text-link" href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->category->name }}</td>
                                <td class="stock-low">{{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit }}</td>
                                <td>{{ number_format($product->minimum_stock, 0, ',', '.') }} {{ $product->unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel dashboard-panel" aria-label="Riwayat transaksi terbaru">
        <div class="panel-heading">
            <h2>Transaksi terbaru</h2>
            <a class="text-link" href="{{ route('stock-transactions.index') }}">Lihat semua</a>
        </div>
        @if ($recentTransactions->isEmpty())
            <div class="empty-state">
                <h3>Belum ada transaksi</h3>
                <p class="muted">Transaksi stok yang dicatat akan tampil di sini.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Tanggal</th><th>Produk</th><th>Jenis</th><th>Jumlah</th><th>Pengguna</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($recentTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td><a class="text-link" href="{{ route('products.show', $transaction->product) }}">{{ $transaction->product->name }}</a></td>
                                <td>
                                    <span class="status-badge {{ $transaction->type === 'in' ? 'status-good' : 'status-warning' }}">
                                        {{ $transaction->type === 'in' ? 'Masuk' : 'Keluar' }}
                                    </span>
                                </td>
                                <td>{{ number_format($transaction->quantity, 0, ',', '.') }} {{ $transaction->product->unit }}</td>
                                <td>{{ $transaction->user->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
