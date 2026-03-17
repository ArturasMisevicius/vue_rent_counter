<?php

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('identifier')->unique();
            $table->string('type')->default(MeterType::WATER->value)->index();
            $table->string('status')->default(MeterStatus::ACTIVE->value)->index();
            $table->string('unit', 16);
            $table->date('installed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};
