<?php

declare(strict_types=1);

use App\Enums\HelpAudienceRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_contexts', function (Blueprint $table): void {
            $table->id();
            $table->string('page_key')->index();
            $table->string('article_slug')->index();
            $table->string('role')->default(HelpAudienceRole::ALL->value)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['page_key', 'article_slug', 'role']);
            $table->index(['page_key', 'role', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_contexts');
    }
};
