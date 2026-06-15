<?php

use App\Enums\TenantKycProfileStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table): void {
            $table->boolean('kyc_required')->default(false)->after('notification_preferences');
            $table->json('required_document_types')->nullable()->after('kyc_required');
            $table->boolean('require_expiry_date')->default(false)->after('required_document_types');
            $table->boolean('block_portal_until_verified')->default(false)->after('require_expiry_date');
            $table->boolean('block_invoice_download_until_verified')->default(false)->after('block_portal_until_verified');
            $table->boolean('block_reading_submission_until_verified')->default(false)->after('block_invoice_download_until_verified');
        });

        Schema::create('tenant_kyc_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default(TenantKycProfileStatus::NOT_STARTED->value);
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'tenant_id'], 'tenant_kyc_profiles_unique_tenant');
            $table->index(['organization_id', 'status'], 'tenant_kyc_profiles_status_index');
            $table->index(['organization_id', 'expires_at'], 'tenant_kyc_profiles_expiry_index');
        });

        Schema::create('tenant_kyc_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kyc_profile_id')->constrained('tenant_kyc_profiles')->cascadeOnDelete();
            $table->string('document_type')->index();
            $table->text('document_number_encrypted')->nullable();
            $table->string('issued_country')->nullable();
            $table->date('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->index();
            $table->foreignId('file_document_id')->constrained('tenant_documents')->restrictOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('internal_note')->nullable();
            $table->foreignId('replaced_by_document_id')->nullable()->constrained('tenant_kyc_documents')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'tenant_id', 'document_type'], 'tenant_kyc_documents_tenant_type_index');
            $table->index(['organization_id', 'status', 'expires_at'], 'tenant_kyc_documents_status_expiry_index');
            $table->index(['kyc_profile_id', 'status'], 'tenant_kyc_documents_profile_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_kyc_documents');
        Schema::dropIfExists('tenant_kyc_profiles');

        Schema::table('organization_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'kyc_required',
                'required_document_types',
                'require_expiry_date',
                'block_portal_until_verified',
                'block_invoice_download_until_verified',
                'block_reading_submission_until_verified',
            ]);
        });
    }
};
