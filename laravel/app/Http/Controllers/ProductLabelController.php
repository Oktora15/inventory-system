<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductLabelController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = $validated['search'] ?? null;

        $products = Product::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('labels.index', compact('products', 'search'));
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:50'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'copies' => ['nullable', 'array'],
            'copies.*' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $products = Product::query()
            ->whereIn('id', $validated['product_ids'])
            ->orderBy('name')
            ->get();

        $labels = new Collection;
        $copies = $validated['copies'] ?? [];

        foreach ($products as $product) {
            $count = (int) ($copies[$product->id] ?? 1);
            $labels->push(...array_fill(0, $count, $product));
        }

        if ($labels->count() > 100) {
            throw ValidationException::withMessages([
                'copies' => 'Jumlah keseluruhan label dalam satu cetak maksimal 100.',
            ]);
        }

        return view('labels.print', compact('labels'));
    }
}
