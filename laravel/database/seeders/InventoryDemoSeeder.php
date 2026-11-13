<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $user = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('id')
                ->firstOrFail();

            $catalog = [
                [
                    'category' => 'Alat Tulis',
                    'description' => 'Alat tulis untuk kebutuhan sekolah dan kantor.',
                    'products' => [
                        ['ATK-PUL-001', 'Pulpen Biru', 'pcs', 'Pulpen tinta biru 0,5 mm.', 2500, 4000, 10, 4],
                        ['ATK-BKU-001', 'Buku Tulis 38 Lembar', 'pcs', 'Buku tulis bergaris 38 lembar.', 3200, 5000, 8, 24],
                        ['ATK-SPD-001', 'Spidol Whiteboard Hitam', 'pcs', 'Spidol untuk papan tulis putih.', 5500, 8000, 5, 16],
                    ],
                ],
                [
                    'category' => 'Elektronik',
                    'description' => 'Perangkat elektronik dan periferal komputer.',
                    'products' => [
                        ['ELK-MOU-001', 'Mouse USB Optik', 'pcs', 'Mouse kabel USB dengan sensor optik.', 45000, 65000, 5, 3],
                        ['ELK-KBD-001', 'Keyboard USB', 'pcs', 'Keyboard kabel USB layout standar.', 80000, 115000, 4, 12],
                        ['ELK-FD-032', 'Flashdisk 32 GB', 'pcs', 'Media penyimpanan USB kapasitas 32 GB.', 60000, 85000, 5, 9],
                    ],
                ],
                [
                    'category' => 'Aksesori Komputer',
                    'description' => 'Aksesori pendukung komputer dan perangkat kerja.',
                    'products' => [
                        ['AKS-MPD-001', 'Mousepad', 'pcs', 'Alas mouse ukuran standar.', 12000, 20000, 5, 18],
                        ['AKS-KBL-001', 'Kabel USB 1 Meter', 'pcs', 'Kabel data dan pengisian daya USB.', 15000, 25000, 5, 14],
                        ['AKS-HDS-001', 'Headset Kabel', 'pcs', 'Headset kabel dengan mikrofon.', 55000, 79000, 4, 7],
                    ],
                ],
                [
                    'category' => 'Perlengkapan Kantor',
                    'description' => 'Kertas dan perlengkapan operasional kantor.',
                    'products' => [
                        ['KTR-KRT-001', 'Kertas A4 80 GSM', 'rim', 'Satu rim kertas A4 80 GSM.', 48000, 58000, 5, 20],
                        ['KTR-TNT-001', 'Tinta Printer Hitam', 'botol', 'Tinta printer warna hitam.', 65000, 85000, 4, 8],
                        ['KTR-STP-001', 'Stapler No. 10', 'pcs', 'Stapler kecil untuk kebutuhan kantor.', 18000, 28000, 3, 11],
                    ],
                ],
                [
                    'category' => 'Perlengkapan Kebersihan',
                    'description' => 'Barang kebersihan untuk area kerja.',
                    'products' => [
                        ['KBR-TIS-001', 'Tisu Kotak', 'kotak', 'Tisu wajah kemasan kotak.', 9000, 14000, 6, 25],
                        ['KBR-SBN-001', 'Sabun Cuci Tangan', 'botol', 'Sabun cuci tangan kemasan botol.', 12000, 18000, 5, 13],
                        ['KBR-CLN-001', 'Pembersih Lantai 1 Liter', 'botol', 'Cairan pembersih lantai kemasan 1 liter.', 17000, 25000, 4, 10],
                    ],
                ],
            ];

            foreach ($catalog as $entry) {
                $category = Category::query()->firstOrCreate(
                    ['name' => $entry['category']],
                    ['description' => $entry['description']],
                );

                foreach ($entry['products'] as [
                    $sku,
                    $name,
                    $unit,
                    $description,
                    $purchasePrice,
                    $sellingPrice,
                    $minimumStock,
                    $openingStock,
                ]) {
                    $product = Product::query()->firstOrCreate(
                        ['sku' => $sku],
                        [
                            'category_id' => $category->id,
                            'name' => $name,
                            'unit' => $unit,
                            'description' => $description,
                            'purchase_price' => $purchasePrice,
                            'selling_price' => $sellingPrice,
                            'minimum_stock' => $minimumStock,
                        ],
                    );

                    if (! $product->wasRecentlyCreated) {
                        continue;
                    }

                    $stockBefore = (int) $product->stock;
                    $product->increment('stock', $openingStock);

                    StockTransaction::query()->create([
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'type' => StockTransaction::TYPE_IN,
                        'quantity' => $openingStock,
                        'transaction_date' => now(),
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore + $openingStock,
                        'notes' => 'Stok awal data contoh aplikasi.',
                    ]);
                }
            }
        });
    }
}
