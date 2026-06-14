<?php

use App\Enums\LeadImportBatchStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->string('filename');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->unsignedInteger('rows_duplicates')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->string('status')->default(LeadImportBatchStatus::PREVIEWED->value)->index();
            $table->json('mapping_config')->nullable();
            $table->json('error_summary')->nullable();
            $table->timestamps();
            $table->timestamp('finished_at')->nullable();

            $table->index(['organization_id', 'status']);
            $table->index(['lead_source_id']);
            $table->index(['uploaded_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_import_batches');
    }
};
