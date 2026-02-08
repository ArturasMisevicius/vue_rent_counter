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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique()->comment('ISO 4217 currency code');
            $table->string('name')->comment('Full currency name');
            $table->string('symbol', 10)->comment('Currency symbol');
            $table->unsignedTinyInteger('decimal_places')->default(2)->comment('Number of decimal places');
            $table->boolean('is_active')->default(true)->comment('Whether currency is active');
            $table->boolean('is_default')->default(false)->comment('Whether this is the default currency');
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'code']);
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
