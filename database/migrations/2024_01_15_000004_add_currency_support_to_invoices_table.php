<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('total_amount')->constrained('currencies')->onDelete('set null');
            $table->foreignId('original_currency_id')->nullable()->after('currency_id')->constrained('currencies')->onDelete('set null');
            $table->decimal('exchange_rate', 12, 6)->nullable()->after('original_currency_id');
            $table->date('conversion_date')->nullable()->after('exchange_rate');
            
            $table->index(['currency_id', 'conversion_date']);
            $table->index('original_currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['original_currency_id']);
            $table->dropIndex(['currency_id', 'conversion_date']);
            $table->dropIndex(['original_currency_id']);
            $table->dropColumn(['currency_id', 'original_currency_id', 'exchange_rate', 'conversion_date']);
        });
    }
};