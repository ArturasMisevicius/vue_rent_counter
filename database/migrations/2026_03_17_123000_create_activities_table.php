<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->string('batch_uuid')->nullable()->index();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['causer_type', 'causer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
