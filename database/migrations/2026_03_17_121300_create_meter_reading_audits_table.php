<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_reading_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_reading_id')->constrained('meter_readings')->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('old_value', 12, 3);
            $table->decimal('new_value', 12, 3);
            $table->text('change_reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_reading_audits');
    }
};
