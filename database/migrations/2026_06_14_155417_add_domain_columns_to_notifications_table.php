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
        Schema::table('notifications', function (Blueprint $table): void {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('notifiable_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('recipient_user_id')
                ->nullable()
                ->after('organization_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('title')->nullable()->after('type');
            $table->text('message')->nullable()->after('title');
            $table->text('action_url')->nullable()->after('message');
            $table->string('dedupe_key')->nullable()->after('action_url');
            $table->timestamp('sent_email_at')->nullable()->after('read_at');

            $table->index(['organization_id', 'recipient_user_id', 'read_at'], 'notifications_recipient_status_index');
            $table->index(['organization_id', 'type', 'created_at'], 'notifications_type_created_index');
            $table->unique(
                ['organization_id', 'recipient_user_id', 'type', 'dedupe_key'],
                'notifications_domain_dedupe_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropUnique('notifications_domain_dedupe_unique');
            $table->dropIndex('notifications_type_created_index');
            $table->dropIndex('notifications_recipient_status_index');
            $table->dropConstrainedForeignId('recipient_user_id');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn([
                'title',
                'message',
                'action_url',
                'dedupe_key',
                'sent_email_at',
            ]);
        });
    }
};
