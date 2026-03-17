<?php

use App\Enums\SubscriptionDuration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('duration')->default(SubscriptionDuration::MONTHLY->value);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EUR');
            $table->timestamp('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'paid_at']);
            $table->index(['organization_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
