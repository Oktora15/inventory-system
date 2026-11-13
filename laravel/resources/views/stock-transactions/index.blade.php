@extends('layouts.app')

@section('title', 'Riwayat Stok')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">PERGERAKAN INVENTORY</p>
            <h1>Riwayat stok</h1>
            <p class="muted">Setiap perubahan stok tercatat bersama stok sebelum dan sesudah transaksi.</p>
        </div>
        <div class="heading-actions">
            @if (auth()->user()->role !== \App\Models\User::ROLE_CASHIER)
                <a class="button button-secondary" href="{{ route('stock-transactions.create', ['type' => 'in']) }}">
                    Barang masuk
                </a>
            @endif
            <a class="button button-primary" href="{{ route('stock-transactions.create', ['type' => 'out']) }}">
                Barang keluar
            </a>
        </div>
    </div>

    <section class="panel" aria-label="Daftar riwayat stok">
        <div class="panel-heading">
            <h2>Transaksi stok</h2>
            <span class="count">{{ $transactions->total() }} transaksi</span>
        </div>

        <form class="search-form" method="GET" action="{{ route('stock-transactions.index') }}">
            <label for="type-filter">Jenis transaksi</label>
            <select id="type-filter" name="type">
                @if (auth()->user()->role !== \App\Models\User::ROLE_CASHIER)
                    <option value="">Semua transaksi</option>
                    <option value="in" @selected($type === 'in')>Barang masuk</option>
                @endif
                <option value="out" @selected($type === 'out')>Barang keluar</option>
            </select>
            <label for="product-filter">Produk</label>
            <select id="product-filter" name="product_id">
                <option value="">Semua produk</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) $productId === (string) $product->id)>
                        {{ $product->name }} ({{ $product->sku }})
                    </option>
                @endforeach
            </select>
            <label for="movement-search">Cari nama atau SKU</label>
            <input id="movement-search" name="search" type="search" value="{{ $search }}"
                   maxlength="255" placeholder="Cari produk...">
            <label for="date-from-filter">Dari</label>
            <input id="date-from-filter" name="date_from" type="date" value="{{ $dateFrom }}">
            <label for="date-to-filter">Sampai</label>
            <input id="date-to-filter" name="date_to" type="date" value="{{ $dateTo }}">
            <button class="button button-secondary" type="submit">Filter</button>
            @if ($type || $productId || $search || $dateFrom || $dateTo)
                <a class="button button-secondary" href="{{ route('stock-transactions.index') }}">Reset</a>
            @endif
        </form>

        @if ($transactions->isEmpty())
            <div class="empty-state">
                <h3>Belum ada transaksi stok</h3>
                <p class="muted">Transaksi barang masuk atau keluar akan tercatat di sini.</p>
                @if ($type !== 'out' && auth()->user()->role !== \App\Models\User::ROLE_CASHIER)
                    <a class="button button-secondary" href="{{ route('stock-transactions.create', ['type' => 'in']) }}">
                        Catat barang masuk
                    </a>
                @endif
                @if ($type !== 'in')
                    <a class="button button-primary" href="{{ route('stock-transactions.create', ['type' => 'out']) }}">
                        Catat barang keluar
                    </a>
                @endif
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
                            <th scope="col">Catatan</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
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
                                    <span class="table-subtext">{{ $transaction->product->sku }}</span>
                                </td>
                                <td>
                                    @if ($transaction->type === 'in')
                                        <span class="status-badge status-good">Masuk</span>
                                    @else
                                        <span class="status-badge status-warning">Keluar</span>
                                    @endif
                                </td>
                                <td>{{ number_format($transaction->quantity, 0, ',', '.') }} {{ $transaction->product->unit }}</td>
                                <td>{{ number_format($transaction->stock_before, 0, ',', '.') }} {{ $transaction->product->unit }}</td>
                                <td>{{ number_format($transaction->stock_after, 0, ',', '.') }} {{ $transaction->product->unit }}</td>
                                <td>{{ $transaction->user->name }}</td>
                                <td class="description-cell">
                                    {{ $transaction->notes ?: '—' }}
                                    @if ($transaction->reversal_of_id)
                                        <span class="table-subtext">Pembatalan transaksi #{{ $transaction->reversal_of_id }}</span>
                                    @elseif ($transaction->reversal)
                                        <span class="table-subtext">Dibatalkan oleh transaksi #{{ $transaction->reversal->id }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if (auth()->user()->isAdmin() && ! $transaction->reversal_of_id && ! $transaction->reversal)
                                        <details class="reversal-action">
                                            <summary class="button button-danger button-small">Batalkan</summary>
                                            <form action="{{ route('stock-transactions.reverse', $transaction) }}"
                                                  method="POST" class="reversal-form">
                                                @csrf
                                                <label for="reason-{{ $transaction->id }}">Alasan pembatalan</label>
                                                <textarea id="reason-{{ $transaction->id }}" name="reason"
                                                          minlength="5" maxlength="1800" required></textarea>
                                                <button class="button button-danger button-small" type="submit">
                                                    Konfirmasi pembatalan
                                                </button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="pagination">{{ $transactions->links() }}</div>
            @endif
        @endif
    </section>
@endsection
