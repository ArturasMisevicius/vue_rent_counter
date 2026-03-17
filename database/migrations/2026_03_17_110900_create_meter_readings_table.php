<?php

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('reading_value', 12, 3);
            $table->date('reading_date');
            $table->string('validation_status')->default(MeterReadingValidationStatus::VALID->value)->index();
            $table->string('submission_method')->default(MeterReadingSubmissionMethod::ADMIN_MANUAL->value)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['meter_id', 'reading_date']);
            $table->index(['organization_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
