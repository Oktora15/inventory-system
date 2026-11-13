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
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->after('id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->after('product_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('type', 3)->after('user_id');
            $table->unsignedInteger('quantity')->after('type');
            $table->unsignedInteger('stock_before')->after('quantity');
            $table->unsignedInteger('stock_after')->after('stock_before');
            $table->text('notes')->nullable()->after('stock_after');
            $table->index(['product_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['product_id', 'created_at']);
            $table->dropIndex(['type', 'created_at']);
            $table->dropColumn([
                'product_id',
                'user_id',
                'type',
                'quantity',
                'stock_before',
                'stock_after',
                'notes',
            ]);
        });
    }
};
