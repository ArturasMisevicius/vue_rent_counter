<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('utility_service_id')->constrained('utility_services')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('tenant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('consumption', 12, 3)->nullable();
            $table->decimal('rate', 12, 4)->nullable();
            $table->unsignedBigInteger('meter_reading_start')->nullable();
            $table->unsignedBigInteger('meter_reading_end')->nullable();
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'property_id', 'billing_period_start']);
            $table->index(['tenant_user_id', 'billing_period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_records');
    }
};
