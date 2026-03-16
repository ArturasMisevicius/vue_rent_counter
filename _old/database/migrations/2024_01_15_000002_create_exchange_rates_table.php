<?php

declare(strict_types=1);

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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->foreignId('to_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('rate', 15, 8)->comment('Exchange rate from base currency to target currency');
            $table->date('effective_date')->comment('Date when this rate becomes effective');
            $table->string('source', 50)->default('manual')->comment('Source of the exchange rate');
            $table->boolean('is_active')->default(true)->comment('Whether this rate is active');
            $table->timestamps();

            // Indexes
            $table->unique(['from_currency_id', 'to_currency_id', 'effective_date'], 'unique_currency_rate_date');
            $table->index(['effective_date', 'is_active']);
            $table->index(['from_currency_id', 'effective_date']);
            $table->index(['to_currency_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};