<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->after('id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('minimum_stock')->default(5);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropUnique(['sku']);
            $table->dropColumn([
                'category_id',
                'sku',
                'name',
                'description',
                'purchase_price',
                'selling_price',
                'stock',
                'minimum_stock',
            ]);
        });
    }
};
