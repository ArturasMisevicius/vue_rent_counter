<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tags system with morph-to-many relationship
     * Tags can be attached to multiple model types
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable(); // Hex color for UI
            $table->text('description')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tag_id')->index();
            $table->morphs('taggable'); // Creates taggable_id and taggable_type
            $table->unsignedBigInteger('tagged_by')->nullable();
            $table->timestamps();

            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->foreign('tagged_by')->references('id')->on('users')->onDelete('set null');

            // Prevent duplicate tags on same model
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
