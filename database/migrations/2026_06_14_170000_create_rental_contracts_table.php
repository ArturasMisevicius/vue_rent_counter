<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contract_number');
            $table->string('status')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('signed_date')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->text('termination_reason')->nullable();
            $table->foreignId('renewed_from_contract_id')->nullable()->constrained('rental_contracts')->nullOnDelete();
            $table->decimal('rent_amount', 12, 2)->nullable();
            $table->decimal('deposit_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->boolean('tenant_visible')->default(false);
            $table->text('internal_notes')->nullable();
            $table->text('tenant_visible_notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'contract_number']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'end_date', 'status']);
            $table->index(['organization_id', 'tenant_id', 'status']);
            $table->index(['organization_id', 'property_id', 'status']);
            $table->index(['property_assignment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contracts');
    }
};
