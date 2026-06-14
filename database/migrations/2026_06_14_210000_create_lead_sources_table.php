<?php

use App\Enums\LeadSourceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default(LeadSourceType::ARUODAS_CSV->value)->index();
            $table->text('description')->nullable();
            $table->string('source_url')->nullable();
            $table->text('privacy_note')->nullable();
            $table->unsignedInteger('retention_days')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
            $table->index(['organization_id', 'type']);
            $table->index(['created_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
    }
};
