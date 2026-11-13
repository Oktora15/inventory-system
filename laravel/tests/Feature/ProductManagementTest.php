<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_admin_can_view_dashboard_and_stock_summary(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        $headphone = Product::create([
            'category_id' => $category->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 5,
        ]);
        $headphone->forceFill(['stock' => 8])->save();

        $speaker = Product::create([
            'category_id' => $category->id,
            'sku' => 'ELEK-002',
            'name' => 'Speaker',
            'purchase_price' => 20000,
            'selling_price' => 30000,
            'minimum_stock' => 5,
        ]);
        $speaker->forceFill(['stock' => 3])->save();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('totalStock', 11)
            ->assertViewHas('totalProducts', 2)
            ->assertViewHas('totalCategories', 1)
            ->assertViewHas('lowStockCount', 1)
            ->assertSee('Speaker')
            ->assertDontSee('Headphone');

        StockTransaction::create([
            'product_id' => $headphone->id,
            'user_id' => auth()->id(),
            'type' => StockTransaction::TYPE_IN,
            'quantity' => 8,
            'stock_before' => 0,
            'stock_after' => 8,
            'transaction_date' => now(),
        ]);
        StockTransaction::create([
            'product_id' => $speaker->id,
            'user_id' => auth()->id(),
            'type' => StockTransaction::TYPE_OUT,
            'quantity' => 1,
            'stock_before' => 4,
            'stock_after' => 3,
            'transaction_date' => now(),
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('stockInCount', 1)
            ->assertViewHas('stockOutCount', 1)
            ->assertViewHas('movementChart', fn ($chart) => $chart->sum('in') === 8 && $chart->sum('out') === 1)
            ->assertSee('Transaksi terbaru')
            ->assertSee('Perlu restok');
    }

    public function test_admin_can_create_product_with_zero_initial_stock(): void
    {
        $category = Category::create(['name' => 'Elektronik']);

        $this->post(route('products.store'), [
            'category_id' => $category->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone',
            'description' => 'Headphone nirkabel.',
            'purchase_price' => '10000.00',
            'selling_price' => '15000.00',
            'minimum_stock' => 5,
            'unit' => 'pcs',
            'stock' => 50,
        ])->assertRedirect(route('products.show', Product::query()->first()))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone',
            'stock' => 0,
            'minimum_stock' => 5,
            'unit' => 'pcs',
        ]);
    }

    public function test_product_creation_requires_valid_category_and_unique_sku(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        Product::create([
            'category_id' => $category->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 5,
        ]);

        $this->from(route('products.create'))
            ->post(route('products.store'), [
                'category_id' => 999,
                'sku' => 'ELEK-001',
                'name' => '',
                'purchase_price' => -1,
                'selling_price' => 15000,
                'minimum_stock' => -1,
                'unit' => 'pcs',
            ])
            ->assertSessionHasErrors(['category_id', 'sku', 'name', 'purchase_price', 'minimum_stock']);
    }

    public function test_admin_can_update_and_delete_product_without_editing_stock(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 5,
            'unit' => 'pcs',
        ]);
        $product->forceFill(['stock' => 7])->save();

        $this->put(route('products.update', $product), [
            'category_id' => $category->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone Pro',
            'purchase_price' => 12000,
            'selling_price' => 18000,
            'minimum_stock' => 4,
            'unit' => 'pcs',
            'stock' => 99,
        ])->assertRedirect(route('products.show', $product))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Headphone Pro',
            'stock' => 7,
        ]);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_list_can_be_searched_by_name_or_sku(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        Product::create([
            'category_id' => $category->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 5,
            'unit' => 'pcs',
        ]);
        Product::create([
            'category_id' => $category->id,
            'sku' => 'ELEK-002',
            'name' => 'Speaker',
            'purchase_price' => 20000,
            'selling_price' => 30000,
            'minimum_stock' => 5,
            'unit' => 'pcs',
        ]);

        $this->get(route('products.index', ['search' => 'ELEK-001']))
            ->assertOk()
            ->assertSee('Headphone')
            ->assertDontSee('Speaker');
    }

    public function test_cashier_can_view_products_but_cannot_manage_them(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 5,
        ]);

        $this->actingAs(User::factory()->create());

        $this->get(route('products.index'))->assertOk();
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Catat barang keluar')
            ->assertDontSee('Catat barang masuk')
            ->assertDontSee('Cetak label produk');
        $this->get(route('products.create'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login_from_dashboard_and_products(): void
    {
        auth()->logout();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }

    public function test_product_list_can_filter_by_category_and_paginates_results(): void
    {
        $selectedCategory = Category::create(['name' => 'Elektronik']);
        $otherCategory = Category::create(['name' => 'Peralatan']);
        Product::create([
            'category_id' => $selectedCategory->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 5,
            'unit' => 'pcs',
        ]);
        Product::create([
            'category_id' => $otherCategory->id,
            'sku' => 'ALAT-001',
            'name' => 'Hammer',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 2,
            'unit' => 'pcs',
        ]);

        $this->get(route('products.index', ['category_id' => $selectedCategory->id]))
            ->assertOk()
            ->assertSee('Headphone')
            ->assertDontSee('Hammer');
    }

    public function test_product_unit_is_validated_and_displayed(): void
    {
        $category = Category::create(['name' => 'Elektronik']);

        $this->from(route('products.create'))
            ->post(route('products.store'), [
                'category_id' => $category->id,
                'sku' => 'ELEK-UNIT',
                'name' => 'Kabel',
                'purchase_price' => 1000,
                'selling_price' => 2000,
                'minimum_stock' => 1,
            ])->assertSessionHasErrors('unit');

        $this->post(route('products.store'), [
            'category_id' => $category->id,
            'sku' => 'ELEK-UNIT',
            'name' => 'Kabel',
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'minimum_stock' => 1,
            'unit' => 'meter',
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['sku' => 'ELEK-UNIT', 'unit' => 'meter']);
    }

    public function test_warehouse_can_create_and_edit_products(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_WAREHOUSE]));

        $this->get(route('products.create'))->assertOk();
        $this->post(route('products.store'), [
            'category_id' => $category->id,
            'sku' => 'WARE-001',
            'name' => 'Box',
            'purchase_price' => 1000,
            'selling_price' => 1500,
            'minimum_stock' => 2,
            'unit' => 'box',
        ])->assertRedirect();

        $product = Product::query()->where('sku', 'WARE-001')->firstOrFail();
        $this->get(route('products.edit', $product))->assertOk();
        $this->put(route('products.update', $product), [
            'category_id' => $category->id,
            'sku' => 'WARE-001',
            'name' => 'Box Besar',
            'purchase_price' => 1200,
            'selling_price' => 1800,
            'minimum_stock' => 3,
            'unit' => 'box',
        ])->assertRedirect(route('products.show', $product));
    }
}
