<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('native_name');
            $table->json('name_translations');
            $table->string('timezone')->index();
            $table->string('postal_code_pattern', 32);
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->boolean('is_capital')->default(false)->index();
            $table->unsignedInteger('population')->default(0)->index();
            $table->timestamps();

            $table->unique(['country_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
