<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->string('plan')->default('basic');
            $table->integer('max_properties')->default(100);
            $table->integer('max_users')->default(10);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->json('features')->nullable();
            $table->string('timezone')->default('Europe/Vilnius');
            $table->string('locale')->default('lt');
            $table->string('currency')->default('EUR');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['is_active', 'subscription_ends_at'], 'organizations_status_subscription_index');
            $table->index('plan', 'organizations_plan_index');
            $table->index('created_by', 'organizations_created_by_index');
        });

        Schema::create('organization_activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->string('resource_type')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at'], 'org_activity_org_created_index');
            $table->index(['user_id', 'created_at'], 'org_activity_user_created_index');
            $table->index('action', 'org_activity_action_index');
        });

        Schema::create('organization_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('email');
            $table->string('role');
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['organization_id', 'email'], 'org_invites_org_email_index');
            $table->index('expires_at', 'org_invites_expires_at_index');
        });

        Schema::table('meter_reading_audits', function (Blueprint $table) {
            $table->index('meter_reading_id', 'meter_reading_audits_meter_index');
            $table->index('changed_by_user_id', 'meter_reading_audits_changed_by_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_reading_audits', function (Blueprint $table) {
            $table->dropIndex('meter_reading_audits_meter_index');
            $table->dropIndex('meter_reading_audits_changed_by_index');
        });

        Schema::dropIfExists('organization_invitations');
        Schema::dropIfExists('organization_activity_log');
        Schema::dropIfExists('organizations');
    }
};
