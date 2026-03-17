<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('to_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->decimal('rate', 15, 8);
            $table->date('effective_date');
            $table->string('source', 50)->default('manual');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['from_currency_id', 'to_currency_id', 'effective_date'],
                'exchange_rates_currency_pair_effective_unique',
            );
            $table->index(['effective_date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
