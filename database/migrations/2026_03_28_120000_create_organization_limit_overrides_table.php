<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_limit_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('dimension', 32);
            $table->unsignedInteger('value');
            $table->text('reason');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'dimension'], 'org_limit_overrides_org_dimension_index');
            $table->index('expires_at', 'org_limit_overrides_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_limit_overrides');
    }
};
