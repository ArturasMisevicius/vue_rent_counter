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
        Schema::create('extra_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('extra_charge_type_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description_for_tenant')->nullable();
            $table->text('internal_note')->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('EUR');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 12, 4);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('status')->index();
            $table->boolean('is_recurring')->default(false);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'tenant_id', 'property_id'], 'extra_charges_org_tenant_property_index');
            $table->index(['organization_id', 'billing_period_id', 'status'], 'extra_charges_org_period_status_index');
            $table->index(['organization_id', 'invoice_id', 'status'], 'extra_charges_org_invoice_status_index');
            $table->index(['starts_at', 'ends_at'], 'extra_charges_date_window_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_charges');
    }
};
