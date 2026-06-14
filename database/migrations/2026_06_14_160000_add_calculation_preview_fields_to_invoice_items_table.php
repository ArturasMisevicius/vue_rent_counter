<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->string('source_type')->nullable()->after('invoice_id')->index();
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->index();
            $table->string('title')->nullable()->after('source_id');
            $table->text('description_for_tenant')->nullable()->after('description');
            $table->text('internal_note')->nullable()->after('description_for_tenant');
            $table->decimal('subtotal', 12, 2)->default(0)->after('unit_price');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('tax_amount');
            $table->char('currency', 3)->default('EUR')->after('total');
            $table->string('formula_label')->nullable()->after('currency');
            $table->json('calculation_snapshot')->nullable()->after('formula_label');
            $table->boolean('tenant_visible')->default(true)->after('calculation_snapshot');
            $table->unsignedInteger('sort_order')->default(0)->after('tenant_visible');

            $table->index(['invoice_id', 'source_type', 'source_id']);
            $table->unique(['invoice_id', 'source_type', 'source_id'], 'invoice_items_invoice_source_unique');
            $table->index(['invoice_id', 'tenant_visible', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropIndex(['invoice_id', 'tenant_visible', 'sort_order']);
            $table->dropUnique('invoice_items_invoice_source_unique');
            $table->dropIndex(['invoice_id', 'source_type', 'source_id']);
            $table->dropColumn([
                'source_type',
                'source_id',
                'title',
                'description_for_tenant',
                'internal_note',
                'subtotal',
                'tax_amount',
                'discount_amount',
                'currency',
                'formula_label',
                'calculation_snapshot',
                'tenant_visible',
                'sort_order',
            ]);
        });
    }
};
