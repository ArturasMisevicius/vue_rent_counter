<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_kyc_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('full_legal_name');
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->text('tax_id_number')->nullable();
            $table->text('social_security_number')->nullable();
            $table->boolean('facial_recognition_consent')->default(false);
            $table->string('secondary_contact_name')->nullable();
            $table->string('secondary_contact_relationship')->nullable();
            $table->string('secondary_contact_phone')->nullable();
            $table->string('secondary_contact_email')->nullable();
            $table->string('tertiary_contact_name')->nullable();
            $table->string('tertiary_contact_relationship')->nullable();
            $table->string('tertiary_contact_phone')->nullable();
            $table->string('tertiary_contact_email')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employment_position')->nullable();
            $table->string('employment_contract_type')->nullable();
            $table->string('monthly_income_range')->nullable();
            $table->text('iban')->nullable();
            $table->text('swift_bic')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_holder_name')->nullable();
            $table->unsignedSmallInteger('payment_history_score')->nullable();
            $table->string('external_credit_bureau_reference')->nullable();
            $table->unsignedSmallInteger('internal_credit_score')->nullable();
            $table->boolean('blacklist_status')->default(false);
            $table->string('verification_status')->default('unverified')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_kyc_profiles');
    }
};
