<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_incoming_and_outgoing_transactions_update_stock_and_record_snapshots(): void
    {
        $product = $this->createProduct();

        $this->post(route('stock-transactions.store'), [
            'product_id' => $product->id,
            'type' => StockTransaction::TYPE_IN,
            'quantity' => 12,
            'transaction_date' => now()->startOfDay()->toDateTimeString(),
            'notes' => 'Stok awal supplier.',
        ])->assertRedirect(route('stock-transactions.index'))
            ->assertSessionHas('success');

        $this->post(route('stock-transactions.store'), [
            'product_id' => $product->id,
            'type' => StockTransaction::TYPE_OUT,
            'quantity' => 4,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Pemenuhan pesanan.',
        ])->assertRedirect(route('stock-transactions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8,
        ]);
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'user_id' => $this->admin->id,
            'type' => StockTransaction::TYPE_IN,
            'quantity' => 12,
            'stock_before' => 0,
            'stock_after' => 12,
            'notes' => 'Stok awal supplier.',
        ]);
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'user_id' => $this->admin->id,
            'type' => StockTransaction::TYPE_OUT,
            'quantity' => 4,
            'transaction_date' => now()->startOfDay()->toDateTimeString(),
            'stock_before' => 12,
            'stock_after' => 8,
            'notes' => 'Pemenuhan pesanan.',
        ]);
    }

    public function test_outgoing_transaction_cannot_reduce_stock_below_zero(): void
    {
        $product = $this->createProduct();
        $product->forceFill(['stock' => 3])->save();

        $this->from(route('stock-transactions.create', ['type' => 'out']))
            ->post(route('stock-transactions.store'), [
                'product_id' => $product->id,
                'type' => StockTransaction::TYPE_OUT,
                'quantity' => 4,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);
        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_transaction_quantity_and_type_are_validated(): void
    {
        $product = $this->createProduct();

        $this->from(route('stock-transactions.create', ['type' => 'in']))
            ->post(route('stock-transactions.store'), [
                'product_id' => $product->id,
                'type' => 'invalid',
                'quantity' => 0,
            ])
            ->assertSessionHasErrors(['type', 'quantity']);

        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_cashier_can_record_stock_transactions(): void
    {
        $product = $this->createProduct();
        $cashier = User::factory()->create();
        $this->actingAs($cashier);

        $this->get(route('stock-transactions.create', ['type' => 'out']))
            ->assertOk()
            ->assertSee($product->name);

        $product->forceFill(['stock' => 5])->save();
        $this->post(route('stock-transactions.store'), [
            'product_id' => $product->id,
            'type' => StockTransaction::TYPE_OUT,
            'quantity' => 2,
            'transaction_date' => now()->toDateString(),
        ])->assertRedirect(route('stock-transactions.index'));

        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'user_id' => $cashier->id,
            'quantity' => 2,
            'type' => StockTransaction::TYPE_OUT,
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'unit' => 'pcs', 'stock' => 3]);
    }

    public function test_transaction_history_can_be_filtered_by_type_and_product(): void
    {
        $product = $this->createProduct();
        $otherProduct = $this->createProduct('MOUSE-001', 'Mouse');

        $this->recordMovement($product, StockTransaction::TYPE_IN, 5);
        $this->recordMovement($product, StockTransaction::TYPE_OUT, 1);
        $this->recordMovement($otherProduct, StockTransaction::TYPE_IN, 2);

        $this->get(route('stock-transactions.index', [
            'type' => StockTransaction::TYPE_OUT,
            'product_id' => $product->id,
        ]))
            ->assertOk()
            ->assertViewHas('transactions', function ($transactions) use ($product) {
                return $transactions->total() === 1
                    && $transactions->first()->product_id === $product->id
                    && $transactions->first()->type === StockTransaction::TYPE_OUT;
            });
    }

    public function test_cashier_transaction_history_only_shows_outgoing_transactions(): void
    {
        $product = $this->createProduct();
        $this->recordMovement($product, StockTransaction::TYPE_IN, 5);
        $this->recordMovement($product, StockTransaction::TYPE_OUT, 1);
        $this->actingAs(User::factory()->create());

        $this->get(route('stock-transactions.index'))
            ->assertOk()
            ->assertViewHas('type', StockTransaction::TYPE_OUT)
            ->assertViewHas('transactions', fn ($transactions) => $transactions->total() === 1
                && $transactions->first()->type === StockTransaction::TYPE_OUT);

        $this->get(route('stock-transactions.index', ['type' => StockTransaction::TYPE_IN]))
            ->assertForbidden();
    }

    public function test_cashier_barcode_scan_opens_outgoing_transaction_for_matching_product(): void
    {
        $product = $this->createProduct();
        $this->actingAs(User::factory()->create());

        $this->post(route('scanner.lookup'), ['barcode' => $product->sku])
            ->assertRedirect(route('stock-transactions.create', [
                'type' => StockTransaction::TYPE_OUT,
                'product_id' => $product->id,
            ]));
    }

    public function test_products_with_stock_history_cannot_be_deleted(): void
    {
        $product = $this->createProduct();
        $this->recordMovement($product, StockTransaction::TYPE_IN, 1);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_admin_reverses_a_transaction_without_changing_original_history(): void
    {
        $product = $this->createProduct();
        $this->recordMovement($product, StockTransaction::TYPE_IN, 10);
        $original = StockTransaction::query()->firstOrFail();

        $this->post(route('stock-transactions.reverse', $original), [
            'reason' => 'Kesalahan pencatatan barang.',
        ])->assertRedirect(route('stock-transactions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 0]);
        $this->assertDatabaseHas('stock_transactions', [
            'id' => $original->id,
            'type' => StockTransaction::TYPE_IN,
            'quantity' => 10,
            'stock_before' => 0,
            'stock_after' => 10,
            'reversal_of_id' => null,
        ]);
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'type' => StockTransaction::TYPE_OUT,
            'quantity' => 10,
            'stock_before' => 10,
            'stock_after' => 0,
            'reversal_of_id' => $original->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_reversing_outgoing_stock_restores_quantity_as_an_incoming_transaction(): void
    {
        $product = $this->createProduct();
        $product->forceFill(['stock' => 6])->save();
        $this->recordMovement($product, StockTransaction::TYPE_OUT, 2);
        $original = StockTransaction::query()->firstOrFail();

        $this->post(route('stock-transactions.reverse', $original), [
            'reason' => 'Transaksi keluar keliru.',
        ])->assertRedirect(route('stock-transactions.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 6]);
        $this->assertDatabaseHas('stock_transactions', [
            'type' => StockTransaction::TYPE_IN,
            'quantity' => 2,
            'stock_before' => 4,
            'stock_after' => 6,
            'reversal_of_id' => $original->id,
        ]);
    }

    public function test_transaction_can_only_be_reversed_once_and_reason_is_required(): void
    {
        $product = $this->createProduct();
        $this->recordMovement($product, StockTransaction::TYPE_IN, 5);
        $original = StockTransaction::query()->firstOrFail();

        $this->from(route('stock-transactions.index'))
            ->post(route('stock-transactions.reverse', $original), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->post(route('stock-transactions.reverse', $original), [
            'reason' => 'Koreksi transaksi.',
        ])->assertRedirect(route('stock-transactions.index'));

        $this->from(route('stock-transactions.index'))
            ->post(route('stock-transactions.reverse', $original), [
                'reason' => 'Mencoba membatalkan ulang.',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('stock_transactions', 2);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 0]);
    }

    public function test_reversing_incoming_stock_is_rejected_if_stock_was_already_used(): void
    {
        $product = $this->createProduct();
        $this->recordMovement($product, StockTransaction::TYPE_IN, 5);
        $incoming = StockTransaction::query()->latest('id')->firstOrFail();
        $this->recordMovement($product, StockTransaction::TYPE_OUT, 3);

        $this->from(route('stock-transactions.index'))
            ->post(route('stock-transactions.reverse', $incoming), [
                'reason' => 'Batalkan penerimaan.',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
        $this->assertDatabaseCount('stock_transactions', 2);
    }

    public function test_cashier_cannot_reverse_a_stock_transaction(): void
    {
        $product = $this->createProduct();
        $this->recordMovement($product, StockTransaction::TYPE_IN, 2);
        $transaction = StockTransaction::query()->firstOrFail();
        $this->actingAs(User::factory()->create());

        $this->post(route('stock-transactions.reverse', $transaction), [
            'reason' => 'Koreksi transaksi.',
        ])->assertForbidden();

        $this->assertDatabaseCount('stock_transactions', 1);
    }

    public function test_cashier_can_only_record_outgoing_transactions(): void
    {
        $product = $this->createProduct();
        $cashier = User::factory()->create();
        $this->actingAs($cashier);

        $this->get(route('stock-transactions.create', ['type' => StockTransaction::TYPE_IN]))
            ->assertForbidden();

        $this->post(route('stock-transactions.store'), [
            'product_id' => $product->id,
            'type' => StockTransaction::TYPE_IN,
            'quantity' => 1,
            'transaction_date' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_warehouse_can_manage_stock_and_products_but_not_categories_or_users(): void
    {
        $warehouse = User::factory()->create(['role' => User::ROLE_WAREHOUSE]);
        $this->actingAs($warehouse);

        $this->get(route('products.create'))->assertOk();
        $this->get(route('stock-transactions.create', ['type' => StockTransaction::TYPE_IN]))->assertOk();
        $this->get(route('categories.create'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_transaction_can_be_found_by_scanned_sku_and_filtered_by_date(): void
    {
        $product = $this->createProduct();

        $this->get(route('stock-transactions.create', [
            'type' => StockTransaction::TYPE_OUT,
            'sku' => $product->sku,
        ]))->assertOk()
            ->assertViewHas('selectedProductId', $product->id);

        $this->recordMovement($product, StockTransaction::TYPE_IN, 3);

        $this->get(route('stock-transactions.index', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertOk()
            ->assertViewHas('transactions', fn ($transactions) => $transactions->total() === 1);
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
            'unit' => 'pcs',
        ]);
    }

    private function recordMovement(Product $product, string $type, int $quantity): void
    {
        $this->post(route('stock-transactions.store'), [
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'transaction_date' => now()->toDateString(),
        ])->assertRedirect(route('stock-transactions.index'));
    }
}
