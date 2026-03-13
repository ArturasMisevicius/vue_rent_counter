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
        Schema::create('invoice_generation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_amount', 10, 2);
            $table->integer('items_count');
            $table->json('metadata')->nullable();
            $table->float('execution_time_ms')->nullable();
            $table->integer('query_count')->nullable();
            $table->timestamp('created_at');

            // Indexes for performance
            $table->index('invoice_id');
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_generation_audits');
    }
};
