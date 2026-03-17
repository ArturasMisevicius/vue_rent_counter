<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email');
            $table->string('subject')->nullable();
            $table->string('status')->default('sent');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['organization_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_email_logs');
    }
};
