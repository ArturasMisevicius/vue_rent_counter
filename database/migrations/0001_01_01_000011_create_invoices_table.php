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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('tenant_renter_id')->constrained('tenants')->onDelete('restrict');
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->dateTime('finalized_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'billing_period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
