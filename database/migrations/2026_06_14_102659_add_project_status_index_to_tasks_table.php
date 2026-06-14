<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'tasks_project_id_status_index';

    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(['project_id', 'status'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(self::INDEX_NAME);
        });
    }
};
