<?php

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_violations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default(SecurityViolationType::AUTHENTICATION->value)->index();
            $table->string('severity')->default(SecurityViolationSeverity::LOW->value)->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('summary');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['occurred_at', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_violations');
    }
};
