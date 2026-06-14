<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_invitations', function (Blueprint $table): void {
            if (! Schema::hasColumn('organization_invitations', 'invited_by_user_id')) {
                $table->foreignId('invited_by_user_id')
                    ->nullable()
                    ->after('inviter_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('organization_invitations', 'token_hash')) {
                $table->string('token_hash')
                    ->nullable()
                    ->after('token')
                    ->unique();
            }

            if (! Schema::hasColumn('organization_invitations', 'sent_at')) {
                $table->timestamp('sent_at')
                    ->nullable()
                    ->after('full_name')
                    ->index();
            }

            if (! Schema::hasColumn('organization_invitations', 'revoked_at')) {
                $table->timestamp('revoked_at')
                    ->nullable()
                    ->after('accepted_at')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        // The same columns can be introduced by tenant onboarding migrations.
        // Leave them in place on rollback to avoid dropping another feature's schema.
    }
};
