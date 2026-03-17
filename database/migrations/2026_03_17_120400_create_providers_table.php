<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('service_type')->index();
            $table->json('contact_info')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'service_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
