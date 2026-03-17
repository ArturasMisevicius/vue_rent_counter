<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->after('email')->default(UserRole::ADMIN->value)->index();
            $table->string('status')->after('role')->default(UserStatus::ACTIVE->value)->index();
            $table->string('locale', 5)->after('status')->default('en');
            $table->foreignId('organization_id')->nullable()->after('locale')->constrained('organizations')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn([
                'role',
                'status',
                'locale',
                'last_login_at',
            ]);
        });
    }
};
