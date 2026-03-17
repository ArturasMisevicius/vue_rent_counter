<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_ip_addresses', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->text('reason')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->foreignId('blocked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ip_addresses');
    }
};
