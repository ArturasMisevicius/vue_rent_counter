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
        Schema::create('billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('utility_service_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('consumption', 10, 2)->nullable();
            $table->decimal('rate', 10, 4)->nullable();
            $table->integer('meter_reading_start')->nullable();
            $table->integer('meter_reading_end')->nullable();
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['property_id', 'billing_period_start']);
            $table->index(['tenant_id', 'billing_period_start']);
            $table->index(['utility_service_id', 'billing_period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_records');
    }
};