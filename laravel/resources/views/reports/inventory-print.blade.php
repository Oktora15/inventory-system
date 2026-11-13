<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Inventory {{ $generatedAt->format('Y-m-d') }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        * { box-sizing: border-box; }
        body { color: #17212b; font: 10pt Arial, sans-serif; }
        h1 { margin: 0 0 5px; font-size: 20pt; }
        h2 { margin: 22px 0 8px; font-size: 14pt; }
        p { margin: 3px 0; color: #536171; }
        .toolbar { display: flex; justify-content: space-between; margin-bottom: 20px; }
        button { padding: 9px 14px; cursor: pointer; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 18px 0; }
        .summary div { padding: 10px; border: 1px solid #d5dbe2; }
        .summary strong, .summary span { display: block; }
        .summary span { margin-bottom: 5px; color: #536171; font-size: 8pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; border: 1px solid #d5dbe2; text-align: left; vertical-align: top; }
        th { background: #f0f3f6; }
        tr { break-inside: avoid; }
        .muted { color: #536171; font-size: 8pt; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    </div>

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

    <section class="summary">
        <div><span>Produk</span><strong>{{ number_format((int) $inventorySummary->product_count, 0, ',', '.') }}</strong></div>
        <div><span>Total stok</span><strong>{{ number_format((int) $inventorySummary->total_stock, 0, ',', '.') }}</strong></div>
        <div><span>Nilai persediaan</span><strong>Rp{{ number_format((float) $inventorySummary->inventory_value, 0, ',', '.') }}</strong></div>
        <div><span>Stok menipis</span><strong>{{ number_format((int) $inventorySummary->low_stock_count, 0, ',', '.') }}</strong></div>
    </section>

    <h2>Posisi stok terkini</h2>
    <table>
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
    <table>
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
