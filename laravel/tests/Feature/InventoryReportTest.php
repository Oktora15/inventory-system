<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class InventoryReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_report_shows_inventory_value_low_stock_and_movement_totals(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        $inStock = $this->createProduct($category, 'ELEK-001', 'Keyboard', 5, 100000, 3);
        $lowStock = $this->createProduct($category, 'ELEK-002', 'Mouse', 2, 50000, 5);

        $this->createTransaction($inStock, StockTransaction::TYPE_IN, 8, 0, 8, '2026-11-10 10:00:00');
        $this->createTransaction($inStock, StockTransaction::TYPE_OUT, 3, 8, 5, '2026-11-11 10:00:00');
        $this->createTransaction($lowStock, StockTransaction::TYPE_IN, 2, 0, 2, '2026-11-12 10:00:00');

        $response = $this->get(route('reports.inventory'))->assertOk();
        $inventorySummary = $response->viewData('inventorySummary');
        $movementSummary = $response->viewData('movementSummary');

        $this->assertSame(2, (int) $inventorySummary->product_count);
        $this->assertSame(7, (int) $inventorySummary->total_stock);
        $this->assertSame(600000.0, (float) $inventorySummary->inventory_value);
        $this->assertSame(1, (int) $inventorySummary->low_stock_count);
        $this->assertSame(10, (int) $movementSummary->total_in);
        $this->assertSame(3, (int) $movementSummary->total_out);
        $this->assertSame(3, (int) $movementSummary->transaction_count);
        $response->assertSee('Keyboard')->assertSee('Mouse');
    }

    public function test_report_filters_inventory_and_movements_by_category_search_and_date_range(): void
    {
        $electronics = Category::create(['name' => 'Elektronik']);
        $furniture = Category::create(['name' => 'Furnitur']);
        $keyboard = $this->createProduct($electronics, 'ELEK-001', 'Keyboard', 4, 100000, 2);
        $chair = $this->createProduct($furniture, 'FURN-001', 'Kursi', 10, 250000, 3);

        $this->createTransaction($keyboard, StockTransaction::TYPE_IN, 6, 0, 6, '2026-11-10 10:00:00');
        $this->createTransaction($keyboard, StockTransaction::TYPE_OUT, 2, 6, 4, '2026-11-12 10:00:00');
        $this->createTransaction($chair, StockTransaction::TYPE_IN, 5, 0, 5, '2026-11-12 10:00:00');

        $response = $this->get(route('reports.inventory', [
            'search' => 'ELEK',
            'category_id' => $electronics->id,
            'date_from' => '2026-11-12',
            'date_to' => '2026-11-12',
        ]))->assertOk();

        $this->assertSame(1, $response->viewData('products')->total());
        $this->assertSame($keyboard->id, $response->viewData('products')->first()->id);
        $this->assertSame(4, (int) $response->viewData('inventorySummary')->total_stock);
        $movementSummary = $response->viewData('movementSummary');
        $this->assertSame(0, (int) $movementSummary->total_in);
        $this->assertSame(2, (int) $movementSummary->total_out);
        $this->assertSame(1, (int) $movementSummary->transaction_count);
        $this->assertSame(1, $response->viewData('transactions')->total());
        $this->assertSame($keyboard->id, $response->viewData('transactions')->first()->product_id);
        $response->assertSee('Keyboard')->assertDontSee('Kursi');
    }

    public function test_report_requires_both_dates_and_rejects_reversed_periods(): void
    {
        $this->from(route('reports.inventory'))
            ->get(route('reports.inventory', ['date_from' => '2026-11-12']))
            ->assertSessionHasErrors('date_to');

        $this->from(route('reports.inventory'))
            ->get(route('reports.inventory', [
                'date_from' => '2026-11-12',
                'date_to' => '2026-11-10',
            ]))
            ->assertSessionHasErrors(['date_from', 'date_to']);
    }

    public function test_inventory_csv_exports_all_matching_rows_and_neutralizes_spreadsheet_formulas(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        $product = $this->createProduct($category, 'FORM-001', '=HYPERLINK("https://example.test")', 2, 100, 0);
        for ($number = 2; $number <= 18; $number++) {
            $this->createProduct($category, sprintf('FORM-%03d', $number), "Produk {$number}", 1, 100, 0);
        }

        $response = $this->get(route('reports.inventory.products-csv', ['category_id' => $category->id]))
            ->assertDownload();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('laporan-stok-', $response->headers->get('content-disposition'));
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('FORM-001', $csv);
        $this->assertStringContainsString('FORM-018', $csv);
        $this->assertStringContainsString((string) $product->stock, $csv);
        $this->assertStringContainsString('Satuan', $csv);
        $this->assertStringContainsString('unit', $csv);
    }

    public function test_movement_csv_and_printable_pdf_honor_report_filters(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        $product = $this->createProduct($category, 'ELEK-003', 'Monitor', 4, 200000, 1);
        $this->createTransaction($product, StockTransaction::TYPE_IN, 4, 0, 4, '2026-11-12 10:00:00');
        $filters = [
            'search' => 'ELEK-003',
            'category_id' => $category->id,
            'date_from' => '2026-11-12',
            'date_to' => '2026-11-12',
        ];

        $csvResponse = $this->get(route('reports.inventory.movements-csv', $filters))
            ->assertDownload();

        $this->assertStringContainsString('ELEK-003', $csvResponse->streamedContent());

        $this->get(route('reports.inventory.print', $filters))
            ->assertOk()
            ->assertSee('Monitor')
            ->assertSee('ELEK-003')
            ->assertSee('Pergerakan stok');

        $pdf = $this->get(route('reports.inventory.pdf', $filters))
            ->assertDownload()
            ->getContent();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_excel_export_is_a_valid_workbook_with_filtered_inventory_and_movements(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        $product = $this->createProduct($category, 'XLSX-001', 'Keyboard', 5, 100000, 2);
        $this->createTransaction($product, StockTransaction::TYPE_IN, 5, 0, 5, '2026-11-12 10:00:00');

        $response = $this->get(route('reports.inventory.excel', [
            'search' => 'XLSX-001',
            'category_id' => $category->id,
            'date_from' => '2026-11-12',
            'date_to' => '2026-11-12',
        ]))->assertDownload();

        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type'),
        );

        $path = $response->baseResponse->getFile()->getPathname();
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path) === true);
        $this->assertNotFalse($archive->getFromName('xl/workbook.xml'));
        $this->assertStringContainsString('Ringkasan', $archive->getFromName('xl/workbook.xml'));
        $productsSheet = $archive->getFromName('xl/worksheets/sheet2.xml');
        $movementsSheet = $archive->getFromName('xl/worksheets/sheet3.xml');
        $this->assertStringContainsString('XLSX-001', $productsSheet);
        $this->assertStringContainsString('Keyboard', $productsSheet);
        $this->assertStringContainsString('2026-11-12T10:00:00', $movementsSheet);
        $archive->close();
        unlink($path);
    }

    public function test_report_movements_are_ordered_by_transaction_date_not_creation_time(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        $product = $this->createProduct($category, 'DATE-001', 'Monitor', 2, 200000, 1);
        $olderMovement = $this->createTransaction(
            $product,
            StockTransaction::TYPE_IN,
            1,
            0,
            1,
            '2026-11-10 10:00:00',
        );
        $newerMovement = $this->createTransaction(
            $product,
            StockTransaction::TYPE_OUT,
            1,
            1,
            0,
            '2026-11-12 10:00:00',
        );
        $olderMovement->forceFill(['created_at' => '2026-11-13 10:00:00'])->save();
        $newerMovement->forceFill(['created_at' => '2026-11-10 10:00:00'])->save();

        $this->get(route('reports.inventory'))
            ->assertOk()
            ->assertViewHas('transactions', fn ($transactions) => $transactions->first()->id === $newerMovement->id);

        $csv = $this->get(route('reports.inventory.movements-csv'))->streamedContent();
        $this->assertLessThan(
            strpos($csv, '2026-11-12T10:00:00+00:00'),
            strpos($csv, '2026-11-10T10:00:00+00:00'),
        );
    }

    public function test_guest_cannot_access_inventory_report(): void
    {
        auth()->logout();

        $this->get(route('reports.inventory'))
            ->assertRedirect(route('login'));
    }

    private function createProduct(
        Category $category,
        string $sku,
        string $name,
        int $stock,
        int $purchasePrice,
        int $minimumStock,
    ): Product {
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => $sku,
            'name' => $name,
            'purchase_price' => $purchasePrice,
            'selling_price' => $purchasePrice * 2,
            'minimum_stock' => $minimumStock,
        ]);
        $product->forceFill(['stock' => $stock])->save();

        return $product;
    }

    private function createTransaction(
        Product $product,
        string $type,
        int $quantity,
        int $stockBefore,
        int $stockAfter,
        string $createdAt,
    ): StockTransaction {
        $transaction = StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
        ]);

        $transaction->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'transaction_date' => $createdAt,
        ])->save();

        return $transaction;
    }
}
