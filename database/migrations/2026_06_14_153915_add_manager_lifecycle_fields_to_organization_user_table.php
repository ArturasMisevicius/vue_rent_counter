<?php

declare(strict_types=1);

use App\Enums\ManagerMembershipStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_user', function (Blueprint $table): void {
            $table->string('status', 32)
                ->default(ManagerMembershipStatus::ACTIVE->value)
                ->after('role')
                ->index();
            $table->string('permissions_preset', 64)
                ->default('read_only')
                ->after('permissions');
            $table->foreignId('invited_by_user_id')
                ->nullable()
                ->after('invited_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('invited_at')
                ->nullable()
                ->after('invited_by_user_id')
                ->index();
            $table->timestamp('accepted_at')
                ->nullable()
                ->after('invited_at');
            $table->timestamp('disabled_at')
                ->nullable()
                ->after('accepted_at')
                ->index();

            $table->index(['organization_id', 'status'], 'organization_user_org_status_index');
            $table->index(['organization_id', 'role', 'status'], 'organization_user_org_role_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table): void {
            $table->dropIndex('organization_user_org_status_index');
            $table->dropIndex('organization_user_org_role_status_index');
            $table->dropIndex(['status']);
            $table->dropIndex(['invited_at']);
            $table->dropIndex(['disabled_at']);
            $table->dropConstrainedForeignId('invited_by_user_id');
            $table->dropColumn([
                'status',
                'permissions_preset',
                'invited_at',
                'accepted_at',
                'disabled_at',
            ]);
        });
    }
};
