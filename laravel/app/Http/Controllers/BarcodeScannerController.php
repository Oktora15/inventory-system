<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarcodeScannerController extends Controller
{
    public function index(): View
    {
        return view('scanner.index');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:2048'],
        ]);
        $value = trim($validated['barcode']);
        $product = null;

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = parse_url($value, PHP_URL_PATH);
            $host = parse_url($value, PHP_URL_HOST);

            if (
                is_string($path)
                && is_string($host)
                && strcasecmp($host, $request->getHost()) === 0
                && preg_match('#^/products/([1-9][0-9]*)/?$#', $path, $matches)
            ) {
                $product = Product::query()->find($matches[1]);
            }
        } else {
            $product = Product::query()->where('sku', $value)->first();
        }

        if ($product === null) {
            return back()
                ->withErrors(['barcode' => 'Barcode atau QR Code tidak cocok dengan produk yang terdaftar.'])
                ->withInput();
        }

        if ($request->user()->role === User::ROLE_CASHIER) {
            return redirect()->route('stock-transactions.create', [
                'type' => StockTransaction::TYPE_OUT,
                'product_id' => $product->id,
            ]);
        }

        return redirect()->route('products.show', $product);
    }
}
