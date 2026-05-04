<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_disk')->nullable()->after('phone');
            $table->string('avatar_path')->nullable()->after('avatar_disk');
            $table->string('avatar_mime_type', 80)->nullable()->after('avatar_path');
            $table->timestamp('avatar_updated_at')->nullable()->after('avatar_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'avatar_disk',
                'avatar_path',
                'avatar_mime_type',
                'avatar_updated_at',
            ]);
        });
    }
};
