<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Polymorphic file attachments - can attach files to any model
     */
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->morphs('attachable'); // Creates attachable_id, attachable_type, and index
            $table->unsignedBigInteger('uploaded_by')->index();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // in bytes
            $table->string('disk')->default('local');
            $table->string('path');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional file metadata
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');

            // Note: morphs() already creates index on (attachable_type, attachable_id)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
