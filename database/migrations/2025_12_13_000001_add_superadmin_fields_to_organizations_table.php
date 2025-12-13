<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Subscription and billing fields
            $table->string('subscription_plan')->default('starter')->after('plan');
            $table->integer('max_storage_gb')->default(1)->after('max_users');
            $table->integer('max_api_calls_per_month')->default(1000)->after('max_storage_gb');
            $table->integer('current_users')->default(0)->after('max_api_calls_per_month');
            $table->decimal('current_storage_gb', 8, 2)->default(0)->after('current_users');
            $table->integer('current_api_calls')->default(0)->after('current_storage_gb');
            
            // Billing information
            $table->string('billing_email')->nullable()->after('primary_contact_email');
            $table->string('billing_name')->nullable()->after('billing_email');
            $table->text('billing_address')->nullable()->after('billing_name');
            $table->decimal('monthly_price', 10, 2)->nullable()->after('billing_address');
            $table->decimal('setup_fee', 10, 2)->default(0)->after('monthly_price');
            $table->string('billing_cycle')->default('monthly')->after('setup_fee');
            $table->date('next_billing_date')->nullable()->after('billing_cycle');
            $table->boolean('auto_billing')->default(true)->after('next_billing_date');
            
            // Quota management
            $table->boolean('enforce_quotas')->default(true)->after('auto_billing');
            $table->boolean('quota_notifications')->default(true)->after('enforce_quotas');
            
            // Tenant settings
            $table->boolean('allow_registration')->default(true)->after('quota_notifications');
            $table->boolean('require_email_verification')->default(true)->after('allow_registration');
            $table->boolean('maintenance_mode')->default(false)->after('require_email_verification');
            $table->boolean('api_access_enabled')->default(true)->after('maintenance_mode');
            
            // Add indexes for performance
            $table->index(['subscription_plan']);
            $table->index(['auto_billing']);
            $table->index(['maintenance_mode']);
            $table->index(['enforce_quotas']);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'max_storage_gb',
                'max_api_calls_per_month',
                'current_users',
                'current_storage_gb',
                'current_api_calls',
                'billing_email',
                'billing_name',
                'billing_address',
                'monthly_price',
                'setup_fee',
                'billing_cycle',
                'next_billing_date',
                'auto_billing',
                'enforce_quotas',
                'quota_notifications',
                'allow_registration',
                'require_email_verification',
                'maintenance_mode',
                'api_access_enabled',
            ]);
        });
    }
};