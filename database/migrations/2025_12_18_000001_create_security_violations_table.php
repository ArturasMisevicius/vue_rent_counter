<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('violation_type', 50)->index();
            $table->string('policy_directive', 100);
            $table->text('blocked_uri')->nullable();
            $table->text('document_uri');
            $table->text('referrer')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('source_file')->nullable();
            $table->integer('line_number')->nullable();
            $table->integer('column_number')->nullable();
            $table->enum('severity_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('threat_classification', ['false_positive', 'suspicious', 'malicious', 'unknown'])->default('unknown');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['tenant_id', 'violation_type', 'created_at'], 'violations_tenant_type_date');
            $table->index(['severity_level', 'created_at'], 'violations_severity_date');
            $table->index(['threat_classification', 'resolved_at'], 'violations_threat_resolved');
            $table->index(['violation_type', 'severity_level'], 'violations_type_severity');
            $table->index(['created_at', 'resolved_at'], 'violations_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_violations');
    }
};