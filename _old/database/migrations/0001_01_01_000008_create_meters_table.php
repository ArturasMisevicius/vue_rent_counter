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
        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('serial_number')->unique();
            $table->enum('type', ['electricity', 'water_cold', 'water_hot', 'heating']);
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->date('installation_date');
            $table->boolean('supports_zones')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};
