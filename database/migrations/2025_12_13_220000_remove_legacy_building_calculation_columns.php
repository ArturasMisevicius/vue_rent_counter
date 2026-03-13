<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('buildings')) {
            $this->dropIndexIfExists('buildings', 'buildings_gyvatukas_index');
            $this->dropIndexIfExists('buildings', 'buildings_gyvatukas_calculated_idx');
            $this->dropIndexIfExists('buildings', 'buildings_gyvatukas_valid_idx');

            $columnsToDrop = [];
            if (Schema::hasColumn('buildings', 'gyvatukas_summer_average')) {
                $columnsToDrop[] = 'gyvatukas_summer_average';
            }
            if (Schema::hasColumn('buildings', 'gyvatukas_last_calculated')) {
                $columnsToDrop[] = 'gyvatukas_last_calculated';
            }

            if ($columnsToDrop !== []) {
                Schema::table('buildings', function (Blueprint $table) use ($columnsToDrop): void {
                    $table->dropColumn($columnsToDrop);
                });
            }
        }

        if (Schema::hasTable('gyvatukas_calculation_audits')) {
            Schema::drop('gyvatukas_calculation_audits');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('buildings')) {
            return;
        }

        Schema::table('buildings', function (Blueprint $table): void {
            if (! Schema::hasColumn('buildings', 'gyvatukas_summer_average')) {
                $table->decimal('gyvatukas_summer_average', 10, 2)->nullable();
            }

            if (! Schema::hasColumn('buildings', 'gyvatukas_last_calculated')) {
                $table->date('gyvatukas_last_calculated')->nullable();
            }
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = Schema::getConnection()->select("PRAGMA index_list({$table})");
            return collect($indexes)->pluck('name')->contains($index);
        }

        if ($driver === 'mysql') {
            $indexes = Schema::getConnection()->select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            return ! empty($indexes);
        }

        if ($driver === 'pgsql') {
            $indexes = Schema::getConnection()->select(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index],
            );

            return ! empty($indexes);
        }

        return false;
    }
};

