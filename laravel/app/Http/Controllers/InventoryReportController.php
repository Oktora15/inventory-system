<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Support\InventoryExcelExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $productQuery = $this->productQuery($filters);

        $products = (clone $productQuery)
            ->with('category')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $inventorySummary = (clone $productQuery)
            ->selectRaw('COUNT(*) as product_count')
            ->selectRaw('COALESCE(SUM(stock), 0) as total_stock')
            ->selectRaw('COALESCE(SUM(stock * purchase_price), 0) as inventory_value')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock <= minimum_stock THEN 1 ELSE 0 END), 0) as low_stock_count')
            ->first();

        $movementQuery = $this->movementQuery($filters);

        $movementSummary = (clone $movementQuery)
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as total_in', [
                StockTransaction::TYPE_IN,
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as total_out', [
                StockTransaction::TYPE_OUT,
            ])
            ->selectRaw('COUNT(*) as transaction_count')
            ->first();

        $transactions = (clone $movementQuery)
            ->with(['product', 'user'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'transactions_page')
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('reports.inventory', [
            'products' => $products,
            'categories' => $categories,
            'search' => $filters['search'] ?? null,
            'categoryId' => $filters['category_id'] ?? null,
            'dateFrom' => $filters['date_from'] ?? null,
            'dateTo' => $filters['date_to'] ?? null,
            'inventorySummary' => $inventorySummary,
            'movementSummary' => $movementSummary,
            'transactions' => $transactions,
        ]);
    }

    public function productsCsv(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka stream ekspor CSV.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['SKU', 'Produk', 'Kategori', 'Stok', 'Satuan', 'Batas Stok Minimum', 'Harga Beli', 'Nilai Stok', 'Status'], ',', '"', '');

            $this->productQuery($filters)
                ->with('category')
                ->orderBy('id')
                ->chunkById(500, function (Collection $products) use ($output): void {
                    foreach ($products as $product) {
                        fputcsv($output, [
                            $this->safeCsvCell($product->sku),
                            $this->safeCsvCell($product->name),
                            $this->safeCsvCell($product->category->name),
                            $product->stock,
                            $this->safeCsvCell($product->unit),
                            $product->minimum_stock,
                            $product->purchase_price,
                            $product->stock * $product->purchase_price,
                            $product->stock <= $product->minimum_stock ? 'Stok menipis' : 'Aman',
                        ], ',', '"', '');
                    }
                });

            fclose($output);
        }, 'laporan-stok-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function movementsCsv(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka stream ekspor CSV.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Tanggal',
                'SKU',
                'Produk',
                'Jenis',
                'Jumlah',
                'Stok Sebelum',
                'Stok Sesudah',
                'Pengguna',
                'Catatan',
                'Transaksi Asal',
            ], ',', '"', '');

            foreach ($this->movementQuery($filters)
                ->with(['product', 'user'])
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->lazy(500) as $transaction) {
                fputcsv($output, [
                    $transaction->transaction_date->toIso8601String(),
                    $this->safeCsvCell($transaction->product->sku),
                    $this->safeCsvCell($transaction->product->name),
                    $transaction->type === StockTransaction::TYPE_IN ? 'Masuk' : 'Keluar',
                    $transaction->quantity,
                    $transaction->stock_before,
                    $transaction->stock_after,
                    $this->safeCsvCell($transaction->user->name),
                    $this->safeCsvCell($transaction->notes ?? ''),
                    $transaction->reversal_of_id ?? '',
                ], ',', '"', '');
            }

            fclose($output);
        }, 'laporan-pergerakan-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->validatedFilters($request);

        return Pdf::loadView('reports.inventory-pdf', $this->exportData($filters))
            ->setPaper('a4', 'portrait')
            ->download('laporan-inventory-'.now()->format('Ymd-His').'.pdf');
    }

    public function excel(Request $request, InventoryExcelExporter $exporter): BinaryFileResponse
    {
        $data = $this->exportData($this->validatedFilters($request));
        $rows = [
            'Ringkasan' => [
                ['Laporan Inventory', 'Nilai'],
                ['Jumlah produk', (int) $data['inventorySummary']->product_count],
                ['Total stok', (int) $data['inventorySummary']->total_stock],
                ['Nilai persediaan', (float) $data['inventorySummary']->inventory_value],
                ['Produk stok menipis', (int) $data['inventorySummary']->low_stock_count],
            ],
            'Stok Produk' => [
                ['SKU', 'Produk', 'Kategori', 'Stok', 'Satuan', 'Batas Minimum', 'Harga Beli', 'Nilai Stok', 'Status'],
            ],
            'Pergerakan Stok' => [
                ['Tanggal', 'SKU', 'Produk', 'Jenis', 'Jumlah', 'Stok Sebelum', 'Stok Sesudah', 'Pengguna', 'Catatan'],
            ],
        ];

        foreach ($data['products'] as $product) {
            $rows['Stok Produk'][] = [
                $product->sku,
                $product->name,
                $product->category->name,
                $product->stock,
                $product->unit,
                $product->minimum_stock,
                (float) $product->purchase_price,
                $product->stock * (float) $product->purchase_price,
                $product->stock <= $product->minimum_stock ? 'Stok menipis' : 'Aman',
            ];
        }

        foreach ($data['transactions'] as $transaction) {
            $rows['Pergerakan Stok'][] = [
                $transaction->transaction_date->toIso8601String(),
                $transaction->product->sku,
                $transaction->product->name,
                $transaction->type === StockTransaction::TYPE_IN ? 'Masuk' : 'Keluar',
                $transaction->quantity,
                $transaction->stock_before,
                $transaction->stock_after,
                $transaction->user->name,
                $transaction->notes ?? '',
            ];
        }

        return response()->download(
            $exporter->create($rows),
            'laporan-inventory-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    public function print(Request $request): View
    {
        return view('reports.inventory-print', $this->exportData($this->validatedFilters($request)));
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date_from' => ['nullable', 'date', 'required_with:date_to', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'required_with:date_from', 'after_or_equal:date_from'],
        ]);
    }

    private function productQuery(array $filters): Builder
    {
        return Product::query()
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            });
    }

    private function movementQuery(array $filters): Builder
    {
        return StockTransaction::query()
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->whereHas(
                'product',
                fn (Builder $productQuery) => $productQuery->where('category_id', $categoryId),
            ))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->whereHas(
                'product',
                fn (Builder $productQuery) => $productQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"),
            ))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $dateFrom) => $query->whereDate('transaction_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $dateTo) => $query->whereDate('transaction_date', '<=', $dateTo));
    }

    private function exportData(array $filters): array
    {
        $productQuery = $this->productQuery($filters);

        return [
            'products' => (clone $productQuery)->with('category')->orderBy('name')->get(),
            'transactions' => $this->movementQuery($filters)
                ->with(['product', 'user'])
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get(),
            'inventorySummary' => (clone $productQuery)
                ->selectRaw('COUNT(*) as product_count')
                ->selectRaw('COALESCE(SUM(stock), 0) as total_stock')
                ->selectRaw('COALESCE(SUM(stock * purchase_price), 0) as inventory_value')
                ->selectRaw('COALESCE(SUM(CASE WHEN stock <= minimum_stock THEN 1 ELSE 0 END), 0) as low_stock_count')
                ->first(),
            'categoryName' => isset($filters['category_id'])
                ? Category::query()->whereKey($filters['category_id'])->value('name')
                : null,
            'filters' => $filters,
            'generatedAt' => now(),
        ];
    }

    private function safeCsvCell(string $value): string
    {
        return preg_match('/^\s*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
