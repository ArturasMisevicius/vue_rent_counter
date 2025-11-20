<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meter_reading_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_reading_id')->constrained('meter_readings')->onDelete('cascade');
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('old_value', 10, 2);
            $table->decimal('new_value', 10, 2);
            $table->text('change_reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_reading_audits');
    }
};
