<?php

use App\Enums\LeadOutreachChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_outreach_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('channel')->default(LeadOutreachChannel::EMAIL->value)->index();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('locale', 10)->default('en')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'channel', 'is_active']);
            $table->index(['organization_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_outreach_templates');
    }
};
