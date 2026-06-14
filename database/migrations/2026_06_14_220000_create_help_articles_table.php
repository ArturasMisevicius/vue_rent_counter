<?php

declare(strict_types=1);

use App\Enums\HelpArticleCategory;
use App\Enums\HelpAudienceRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->string('category')->default(HelpArticleCategory::GETTING_STARTED->value)->index();
            $table->string('title');
            $table->longText('body');
            $table->string('locale', 5)->default('en')->index();
            $table->string('role')->default(HelpAudienceRole::ALL->value)->index();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['slug', 'locale', 'role']);
            $table->index(['category', 'locale', 'role', 'is_active', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_articles');
    }
};
