<?php

use App\Enums\SystemSettingCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('category')->default(SystemSettingCategory::GENERAL->value)->index();
            $table->string('key')->unique();
            $table->string('label');
            $table->json('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
