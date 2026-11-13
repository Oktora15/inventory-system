<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLabelAndScannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_product_detail_displays_sku_barcode_and_product_qr_code(): void
    {
        $product = $this->createProduct();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Barcode (SKU)')
            ->assertSee('QR Code detail produk')
            ->assertSee('data-barcode-value="'.$product->sku.'"', false)
            ->assertSee('data-qr-value="'.route('products.show', $product).'"', false);
    }

    public function test_user_can_select_products_and_print_multiple_label_copies(): void
    {
        $keyboard = $this->createProduct('KEYB-001', 'Keyboard');
        $mouse = $this->createProduct('MOUSE-001', 'Mouse');

        $this->get(route('labels.index'))
            ->assertOk()
            ->assertSee($keyboard->name)
            ->assertSee($mouse->name);

        $this->post(route('labels.print'), [
            'product_ids' => [$keyboard->id, $mouse->id],
            'copies' => [
                $keyboard->id => 2,
                $mouse->id => 3,
            ],
        ])
            ->assertOk()
            ->assertViewHas('labels', fn ($labels) => $labels->count() === 5
                && $labels->where('id', $keyboard->id)->count() === 2
                && $labels->where('id', $mouse->id)->count() === 3)
            ->assertSee('data-barcode-value="KEYB-001"', false)
            ->assertSee('data-qr-value="'.route('products.show', $keyboard).'"', false)
            ->assertSee('Cetak label');
    }

    public function test_label_preview_requires_existing_unique_product_ids(): void
    {
        $product = $this->createProduct();

        $this->from(route('labels.index'))
            ->post(route('labels.print'), [
                'product_ids' => [$product->id, $product->id],
            ])
            ->assertSessionHasErrors('product_ids.1');

        $this->from(route('labels.index'))
            ->post(route('labels.print'), [
                'product_ids' => [999],
            ])
            ->assertSessionHasErrors('product_ids.0');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_label_preview_rejects_more_than_one_hundred_labels(): void
    {
        $products = [];

        for ($index = 1; $index <= 6; $index++) {
            $products[] = $this->createProduct(
                sprintf('SKU-%03d', $index),
                "Produk {$index}",
            )->id;
        }

        $this->from(route('labels.index'))
            ->post(route('labels.print'), [
                'product_ids' => $products,
                'copies' => array_fill_keys($products, 20),
            ])
            ->assertSessionHasErrors('copies');
    }

    public function test_scanner_accepts_a_product_sku_and_a_product_detail_qr_url(): void
    {
        $product = $this->createProduct();

        $this->get(route('scanner.index'))
            ->assertOk()
            ->assertSee('Mulai scan kamera')
            ->assertSee('scanner USB');

        $this->post(route('scanner.lookup'), ['barcode' => $product->sku])
            ->assertRedirect(route('products.show', $product));

        $this->post(route('scanner.lookup'), [
            'barcode' => route('products.show', $product),
        ])->assertRedirect(route('products.show', $product));
    }

    public function test_scanner_rejects_unknown_codes_and_qr_urls_from_untrusted_hosts(): void
    {
        $product = $this->createProduct();

        $this->from(route('scanner.index'))
            ->post(route('scanner.lookup'), ['barcode' => 'UNKNOWN-001'])
            ->assertSessionHasErrors('barcode');

        $this->from(route('scanner.index'))
            ->post(route('scanner.lookup'), ['barcode' => 'https://example.com/products/'.$product->id])
            ->assertSessionHasErrors('barcode');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_guests_cannot_access_label_or_scanner_tools(): void
    {
        auth()->logout();

        $this->get(route('labels.index'))->assertRedirect(route('login'));
        $this->get(route('scanner.index'))->assertRedirect(route('login'));
    }

    private function createProduct(string $sku = 'KEYB-001', string $name = 'Keyboard'): Product
    {
        $category = Category::firstOrCreate(['name' => 'Periferal']);

        return Product::create([
            'category_id' => $category->id,
            'sku' => $sku,
            'name' => $name,
            'purchase_price' => 100000,
            'selling_price' => 150000,
            'minimum_stock' => 5,
        ]);
    }
}
