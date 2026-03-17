<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method')->default('manual');
            $table->string('period')->default('annually');
            $table->dateTime('old_expires_at');
            $table->dateTime('new_expires_at');
            $table->unsignedInteger('duration_days');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_renewals');
    }
};
