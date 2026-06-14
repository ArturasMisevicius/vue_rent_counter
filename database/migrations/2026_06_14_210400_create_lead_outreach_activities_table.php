<?php

use App\Enums\LeadOutreachChannel;
use App\Enums\LeadOutreachDirection;
use App\Enums\LeadOutreachStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_outreach_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_lead_id')->constrained('listing_leads')->cascadeOnDelete();
            $table->foreignId('lead_contact_id')->nullable()->constrained('lead_contacts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel')->default(LeadOutreachChannel::MANUAL->value)->index();
            $table->string('direction')->default(LeadOutreachDirection::INTERNAL_NOTE->value)->index();
            $table->string('subject')->nullable();
            $table->text('message_summary');
            $table->string('status')->default(LeadOutreachStatus::COMPLETED->value)->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('internal_correction_reason')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['listing_lead_id', 'status']);
            $table->index(['lead_contact_id']);
            $table->index(['user_id']);
            $table->index(['next_follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_outreach_activities');
    }
};
