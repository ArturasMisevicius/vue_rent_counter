<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('billing_contact_name')->nullable();
            $table->string('billing_contact_email')->nullable();
            $table->string('billing_contact_phone')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};
