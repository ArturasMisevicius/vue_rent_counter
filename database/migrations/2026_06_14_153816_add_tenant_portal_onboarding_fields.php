<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('tenant_status')
                ->nullable()
                ->after('status')
                ->default(TenantStatus::DRAFT->value)
                ->index();
            $table->boolean('portal_access_enabled')
                ->after('tenant_status')
                ->default(false)
                ->index();
        });

        Schema::table('organization_invitations', function (Blueprint $table): void {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('organization_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('invited_by_user_id')
                ->nullable()
                ->after('inviter_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('token_hash')
                ->nullable()
                ->after('token')
                ->unique();
            $table->timestamp('sent_at')
                ->nullable()
                ->after('full_name')
                ->index();
            $table->timestamp('revoked_at')
                ->nullable()
                ->after('accepted_at')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_invitations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('invited_by_user_id');
            $table->dropUnique(['token_hash']);
            $table->dropColumn([
                'token_hash',
                'sent_at',
                'revoked_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'tenant_status',
                'portal_access_enabled',
            ]);
        });
    }
};
