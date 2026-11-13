<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dateTime('transaction_date')->nullable();
        });

        DB::table('stock_transactions')
            ->whereNull('transaction_date')
            ->update(['transaction_date' => DB::raw('created_at')]);

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->index(['transaction_date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_date', 'type']);
            $table->dropColumn('transaction_date');
        });
    }
};
