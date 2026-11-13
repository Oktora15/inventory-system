<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $search = $validated['search'] ?? null;
        $categoryId = $validated['category_id'] ?? null;

        $products = Product::query()
            ->with('category')
            ->when($categoryId, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('products.index', compact('products', 'search', 'categories', 'categoryId'));
    }

    public function create(): View
    {
        return view('products.create', [
            'product' => new Product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        $product = Product::create($validated);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Produk berhasil ditambahkan. Stok awal produk adalah 0.');
    }

    public function show(Product $product): View
    {
        $product->load('category');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validateProduct($request, $product));

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->stockTransactions()->exists()) {
            return redirect()
                ->route('products.show', $product)
                ->with('error', 'Produk tidak dapat dihapus karena sudah memiliki riwayat stok.');
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:5000'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
        ]);
    }
}
