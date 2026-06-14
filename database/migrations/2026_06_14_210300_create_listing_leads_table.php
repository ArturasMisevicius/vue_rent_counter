<?php

use App\Enums\ListingLeadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('lead_import_batches')->nullOnDelete();
            $table->foreignId('lead_contact_id')->nullable()->constrained('lead_contacts')->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('source_url')->nullable();
            $table->string('listing_title')->nullable();
            $table->string('property_address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('property_type')->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->unsignedSmallInteger('rooms')->nullable();
            $table->string('floor')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->string('owner_name')->nullable();
            $table->string('owner_phone')->nullable();
            $table->string('owner_email')->nullable();
            $table->text('contact_raw')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default(ListingLeadStatus::NEW->value)->index();
            $table->json('duplicate_reasons')->nullable();
            $table->json('raw_payload')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->foreignId('converted_property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'source_url']);
            $table->index(['organization_id', 'external_id']);
            $table->index(['organization_id', 'assigned_to_user_id', 'status'], 'listing_leads_org_assignee_status_index');
            $table->index(['lead_contact_id', 'status']);
            $table->index(['next_follow_up_at']);
            $table->index(['converted_property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_leads');
    }
};
