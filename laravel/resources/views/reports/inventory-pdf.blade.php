<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 12mm; }
        body { color: #17212b; font-family: DejaVu Sans, sans-serif; font-size: 8pt; }
        h1 { margin: 0 0 4px; font-size: 17pt; }
        h2 { margin: 18px 0 7px; font-size: 11pt; }
        p { margin: 3px 0; color: #536171; }
        .summary { width: 100%; margin: 14px 0; border-collapse: separate; border-spacing: 5px; }
        .summary td { width: 25%; padding: 8px; border: 1px solid #d5dbe2; }
        .summary span, .summary strong { display: block; }
        .summary span { margin-bottom: 4px; color: #536171; font-size: 7pt; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th, .data td { padding: 5px; border: 1px solid #d5dbe2; text-align: left; vertical-align: top; }
        .data th { background: #f0f3f6; }
        .data tr { page-break-inside: avoid; }
        .muted { color: #536171; }
    </style>
</head>
<body>
    <header>
        <h1>Laporan inventory</h1>
        <p>Dibuat {{ $generatedAt->format('d-m-Y H:i') }}</p>
        @if ($filters['search'] ?? false)
            <p>Pencarian: {{ $filters['search'] }}</p>
        @endif
        @if ($categoryName)
            <p>Kategori: {{ $categoryName }}</p>
        @endif
        @if ($filters['date_from'] ?? false)
            <p>Periode transaksi: {{ $filters['date_from'] }} s.d. {{ $filters['date_to'] }}</p>
        @endif
    </header>

    <table class="summary">
        <tr>
            <td><span>Produk</span><strong>{{ number_format((int) $inventorySummary->product_count, 0, ',', '.') }}</strong></td>
            <td><span>Total stok</span><strong>{{ number_format((int) $inventorySummary->total_stock, 0, ',', '.') }}</strong></td>
            <td><span>Nilai persediaan</span><strong>Rp{{ number_format((float) $inventorySummary->inventory_value, 0, ',', '.') }}</strong></td>
            <td><span>Stok menipis</span><strong>{{ number_format((int) $inventorySummary->low_stock_count, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <h2>Posisi stok terkini</h2>
    <table class="data">
        <thead>
            <tr><th>SKU</th><th>Produk</th><th>Kategori</th><th>Stok</th><th>Satuan</th><th>Minimum</th><th>Nilai stok</th></tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name }}</td>
                    <td>{{ number_format($product->stock, 0, ',', '.') }}</td>
                    <td>{{ $product->unit }}</td>
                    <td>{{ number_format($product->minimum_stock, 0, ',', '.') }} {{ $product->unit }}</td>
                    <td>Rp{{ number_format($product->stock * (float) $product->purchase_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada produk pada filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Pergerakan stok</h2>
    <table class="data">
        <thead>
            <tr><th>Tanggal</th><th>SKU / Produk</th><th>Jenis</th><th>Jumlah</th><th>Sebelum</th><th>Sesudah</th><th>Pengguna</th></tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->transaction_date->format('d-m-Y') }}</td>
                    <td>{{ $transaction->product->sku }}<br>{{ $transaction->product->name }}</td>
                    <td>{{ $transaction->type === 'in' ? 'Masuk' : 'Keluar' }}</td>
                    <td>{{ number_format($transaction->quantity, 0, ',', '.') }}</td>
                    <td>{{ number_format($transaction->stock_before, 0, ',', '.') }}</td>
                    <td>{{ number_format($transaction->stock_after, 0, ',', '.') }}</td>
                    <td>{{ $transaction->user->name }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada transaksi pada filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
