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
        Schema::create('extra_charge_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('tenant_visible_by_default')->default(true);
            $table->boolean('requires_comment')->default(false);
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'name'], 'extra_charge_types_org_name_unique');
            $table->index(['organization_id', 'type', 'is_active'], 'extra_charge_types_org_type_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_charge_types');
    }
};
