<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('plan_name_snapshot')->nullable()->after('plan');
            $table->json('limits_snapshot')->nullable()->after('plan_name_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn([
                'plan_name_snapshot',
                'limits_snapshot',
            ]);
        });
    }
};
