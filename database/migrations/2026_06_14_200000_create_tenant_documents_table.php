<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('related');
            $table->string('document_type')->index();
            $table->string('title');
            $table->text('description_for_tenant')->nullable();
            $table->text('internal_note')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('status')->index();
            $table->boolean('tenant_visible')->default(false);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'tenant_id', 'tenant_visible'], 'tenant_documents_tenant_visible_index');
            $table->index(['organization_id', 'status', 'document_type'], 'tenant_documents_status_type_index');
            $table->index(['organization_id', 'expires_at', 'status'], 'tenant_documents_expiry_index');
            $table->index(['organization_id', 'property_id', 'document_type'], 'tenant_documents_property_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_documents');
    }
};
