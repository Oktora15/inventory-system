<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in([StockTransaction::TYPE_IN, StockTransaction::TYPE_OUT])],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date', 'required_with:date_to', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'required_with:date_from', 'after_or_equal:date_from'],
        ]);

        $type = $validated['type'] ?? null;
        $productId = $validated['product_id'] ?? null;
        $search = $validated['search'] ?? null;
        $isCashier = $request->user()->role === User::ROLE_CASHIER;

        abort_if($isCashier && $type === StockTransaction::TYPE_IN, 403);
        $type = $isCashier ? StockTransaction::TYPE_OUT : $type;

        $transactions = StockTransaction::query()
            ->with(['product', 'user', 'reversal', 'reversalOf'])
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->when($productId, fn (Builder $query) => $query->where('product_id', $productId))
            ->when($search, fn (Builder $query, string $term) => $query->whereHas(
                'product',
                fn (Builder $productQuery) => $productQuery
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%"),
            ))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '<=', $date))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('stock-transactions.index', [
            'transactions' => $transactions,
            'type' => $type,
            'productId' => $productId,
            'search' => $search,
            'products' => $products,
            'dateFrom' => $validated['date_from'] ?? null,
            'dateTo' => $validated['date_to'] ?? null,
        ]);
    }

    public function create(Request $request): View
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in([StockTransaction::TYPE_IN, StockTransaction::TYPE_OUT])],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'sku' => ['nullable', 'string', 'max:100'],
        ]);
        abort_if($request->user()->role === User::ROLE_CASHIER && $validated['type'] !== StockTransaction::TYPE_OUT, 403);

        $selectedProductId = $validated['product_id'] ?? null;
        if (! empty($validated['sku'])) {
            $scannedProduct = Product::query()->where('sku', trim($validated['sku']))->first();

            if ($scannedProduct === null) {
                return redirect()->route('stock-transactions.create', [
                    'type' => $validated['type'],
                ])->withErrors(['sku' => 'SKU tidak ditemukan.'])
                    ->withInput(['sku' => $validated['sku']]);
            }

            $selectedProductId = $scannedProduct->id;
        }

        $products = Product::query()->orderBy('name')->get();

        return view('stock-transactions.create', [
            'type' => $validated['type'],
            'products' => $products,
            'selectedProductId' => $selectedProductId,
            'selectedSku' => $validated['sku'] ?? null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', Rule::in([StockTransaction::TYPE_IN, StockTransaction::TYPE_OUT])],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        abort_if($request->user()->role === User::ROLE_CASHIER && $validated['type'] !== StockTransaction::TYPE_OUT, 403);

        $transaction = DB::transaction(function () use ($validated, $request): StockTransaction {
            $product = Product::query()
                ->whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) $validated['quantity'];

            if ($validated['type'] === StockTransaction::TYPE_IN) {
                $updatedRows = Product::query()
                    ->whereKey($product->id)
                    ->increment('stock', $quantity);
            } else {
                $updatedRows = Product::query()
                    ->whereKey($product->id)
                    ->where('stock', '>=', $quantity)
                    ->decrement('stock', $quantity);
            }

            if ($updatedRows !== 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok tidak mencukupi untuk transaksi barang keluar.',
                ]);
            }

            $product->refresh();
            $stockAfter = (int) $product->stock;
            $stockBefore = $validated['type'] === StockTransaction::TYPE_IN
                ? $stockAfter - $quantity
                : $stockAfter + $quantity;

            return StockTransaction::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
            ]);
        }, attempts: 5);

        return redirect()
            ->route('stock-transactions.index')
            ->with('success', $transaction->type === StockTransaction::TYPE_IN
                ? 'Transaksi barang masuk berhasil dicatat.'
                : 'Transaksi barang keluar berhasil dicatat.');
    }

    public function reverse(Request $request, StockTransaction $stockTransaction): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1800'],
        ]);

        $reversal = DB::transaction(function () use ($request, $stockTransaction, $validated): StockTransaction {
            $original = StockTransaction::query()
                ->whereKey($stockTransaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($original->reversal_of_id !== null || $original->reversal()->exists()) {
                throw ValidationException::withMessages([
                    'reason' => 'Transaksi ini sudah dibatalkan atau merupakan transaksi pembatalan.',
                ]);
            }

            $product = Product::query()
                ->whereKey($original->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) $original->quantity;
            $reversalType = $original->type === StockTransaction::TYPE_IN
                ? StockTransaction::TYPE_OUT
                : StockTransaction::TYPE_IN;

            if ($reversalType === StockTransaction::TYPE_IN) {
                $updatedRows = Product::query()
                    ->whereKey($product->id)
                    ->increment('stock', $quantity);
            } else {
                $updatedRows = Product::query()
                    ->whereKey($product->id)
                    ->where('stock', '>=', $quantity)
                    ->decrement('stock', $quantity);
            }

            if ($updatedRows !== 1) {
                throw ValidationException::withMessages([
                    'reason' => 'Stok saat ini tidak mencukupi untuk membatalkan transaksi barang masuk.',
                ]);
            }

            $product->refresh();
            $stockAfter = (int) $product->stock;

            return StockTransaction::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => $reversalType,
                'quantity' => $quantity,
                'stock_before' => $reversalType === StockTransaction::TYPE_IN
                    ? $stockAfter - $quantity
                    : $stockAfter + $quantity,
                'stock_after' => $stockAfter,
                'transaction_date' => now(),
                'notes' => "Pembatalan transaksi #{$original->id}: {$validated['reason']}",
                'reversal_of_id' => $original->id,
            ]);
        }, attempts: 5);

        return redirect()
            ->route('stock-transactions.index')
            ->with('success', "Transaksi #{$stockTransaction->id} dibatalkan melalui transaksi #{$reversal->id}.");
    }
}
