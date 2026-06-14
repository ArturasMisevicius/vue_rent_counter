<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('normalized_phone')->nullable();
            $table->string('normalized_email')->nullable();
            $table->string('preferred_channel')->nullable();
            $table->boolean('do_not_contact')->default(false);
            $table->text('do_not_contact_reason')->nullable();
            $table->timestamp('do_not_contact_at')->nullable();
            $table->foreignId('marked_do_not_contact_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'normalized_phone']);
            $table->index(['organization_id', 'normalized_email']);
            $table->index(['organization_id', 'do_not_contact']);
            $table->index(['marked_do_not_contact_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_contacts');
    }
};
