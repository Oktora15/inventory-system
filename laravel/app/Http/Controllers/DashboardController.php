<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalProducts = Product::query()->count();
        $totalCategories = Category::query()->count();
        $totalStock = (int) Product::query()->sum('stock');
        $lowStockCount = Product::query()
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();
        $lowStockProducts = Product::query()
            ->with('category')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderByRaw('(minimum_stock - stock) DESC')
            ->limit(8)
            ->get();
        $stockInCount = StockTransaction::query()->where('type', StockTransaction::TYPE_IN)->count();
        $stockOutCount = StockTransaction::query()->where('type', StockTransaction::TYPE_OUT)->count();
        $recentTransactions = StockTransaction::query()
            ->with(['product', 'user'])
            ->latest('transaction_date')
            ->limit(8)
            ->get();
        $chartStart = CarbonImmutable::today()->subDays(6);
        $chartEnd = CarbonImmutable::today()->endOfDay();
        $movementByDate = StockTransaction::query()
            ->selectRaw('DATE(transaction_date) as movement_day')
            ->selectRaw('SUM(CASE WHEN type = ? THEN quantity ELSE 0 END) as total_in', [StockTransaction::TYPE_IN])
            ->selectRaw('SUM(CASE WHEN type = ? THEN quantity ELSE 0 END) as total_out', [StockTransaction::TYPE_OUT])
            ->whereBetween('transaction_date', [$chartStart, $chartEnd])
            ->groupByRaw('DATE(transaction_date)')
            ->get()
            ->keyBy('movement_day');
        $movementChart = collect(range(0, 6))->map(function (int $offset) use ($chartStart, $movementByDate): array {
            $date = $chartStart->addDays($offset);
            $movement = $movementByDate->get($date->toDateString());

            return [
                'label' => $date->format('D d/m'),
                'in' => (int) ($movement->total_in ?? 0),
                'out' => (int) ($movement->total_out ?? 0),
            ];
        });

        return view('dashboard', compact(
            'totalProducts',
            'totalCategories',
            'totalStock',
            'lowStockCount',
            'lowStockProducts',
            'stockInCount',
            'stockOutCount',
            'recentTransactions',
            'movementChart',
        ));
    }
}
