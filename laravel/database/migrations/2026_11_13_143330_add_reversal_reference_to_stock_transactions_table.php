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
            $table->foreignId('reversal_of_id')
                ->nullable()
                ->unique()
                ->constrained('stock_transactions')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropForeign(['reversal_of_id']);
            $table->dropUnique(['reversal_of_id']);
            $table->dropColumn('reversal_of_id');
        });
    }
};
