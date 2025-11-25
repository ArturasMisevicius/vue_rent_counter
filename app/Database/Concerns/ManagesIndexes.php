<?php

declare(strict_types=1);

namespace App\Database\Concerns;

use Illuminate\Support\Facades\Schema;

/**
 * ManagesIndexes Trait
 * 
 * Provides reusable index management methods for migrations.
 * Compatible with Laravel 12 (no Doctrine DBAL dependency).
 * 
 * Usage in migrations:
 * ```php
 * use App\Database\Concerns\ManagesIndexes;
 * 
 * return new class extends Migration {
 *     use ManagesIndexes;
 *     
 *     public function up(): void {
 *         if (!$this->indexExists('table_name', 'index_name')) {
 *             Schema::table('table_name', function (Blueprint $table) {
 *                 $table->index(['column'], 'index_name');
 *             });
 *         }
 *     }
 * };
 * ```
 */
trait ManagesIndexes
{
    /**
     * Validate table name to prevent SQL injection.
     * 
     * @param string $table The table name to validate
     * @throws \InvalidArgumentException If table name is invalid
     */
    private function validateTableName(string $table): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            \Illuminate\Support\Facades\Log::warning('Invalid table name attempted', [
                'table' => $table,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
            ]);
            throw new \InvalidArgumentException("Invalid table name: {$table}");
        }
        
        // Additional length check
        if (strlen($table) > 64) {
            throw new \InvalidArgumentException("Table name too long: {$table}");
        }
    }

    /**
     * Validate index name to prevent SQL injection.
     * 
     * @param string $index The index name to validate
     * @throws \InvalidArgumentException If index name is invalid
     */
    private function validateIndexName(string $index): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $index)) {
            \Illuminate\Support\Facades\Log::warning('Invalid index name attempted', [
                'index' => $index,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
            ]);
            throw new \InvalidArgumentException("Invalid index name: {$index}");
        }
        
        // Additional length check
        if (strlen($index) > 64) {
            throw new \InvalidArgumentException("Index name too long: {$index}");
        }
    }

    /**
     * Check if an index exists on a table (SECURED).
     * 
     * @param string $table The table name
     * @param string $index The index name
     * @return bool True if index exists, false otherwise
     * @throws \InvalidArgumentException If table or index name is invalid
     */
    protected function indexExists(string $table, string $index): bool
    {
        $this->validateTableName($table);
        $this->validateIndexName($index);
        
        try {
            $connection = Schema::getConnection();
            $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes($table);
            
            $exists = isset($indexes[$index]);
            
            \Illuminate\Support\Facades\Log::debug('Index existence check', [
                'table' => $table,
                'index' => $index,
                'exists' => $exists,
            ]);
            
            return $exists;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Index check failed', [
                'table' => $table,
                'index' => $index,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if a foreign key exists on a table (SECURED).
     * 
     * @param string $table The table name
     * @param string $foreignKey The foreign key name
     * @return bool True if foreign key exists, false otherwise
     * @throws \InvalidArgumentException If table or foreign key name is invalid
     */
    protected function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $this->validateTableName($table);
        $this->validateIndexName($foreignKey); // Same validation rules
        
        try {
            $connection = Schema::getConnection();
            $foreignKeys = $connection->getDoctrineSchemaManager()->listTableForeignKeys($table);
            
            foreach ($foreignKeys as $fk) {
                if ($fk->getName() === $foreignKey) {
                    \Illuminate\Support\Facades\Log::debug('Foreign key found', [
                        'table' => $table,
                        'foreign_key' => $foreignKey,
                    ]);
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Foreign key check failed', [
                'table' => $table,
                'foreign_key' => $foreignKey,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if a column exists on a table.
     * 
     * @param string $table The table name
     * @param string $column The column name
     * @return bool True if column exists, false otherwise
     */
    protected function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    /**
     * Get all indexes for a table.
     * 
     * @param string $table The table name
     * @return array<string, \Doctrine\DBAL\Schema\Index> Array of index objects keyed by name
     */
    protected function getTableIndexes(string $table): array
    {
        try {
            $connection = Schema::getConnection();
            return $connection->getDoctrineSchemaManager()->listTableIndexes($table);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Drop index if it exists (SECURED).
     * 
     * @param string $table The table name
     * @param string $index The index name
     * @return void
     * @throws \InvalidArgumentException If table or index name is invalid
     */
    protected function dropIndexIfExists(string $table, string $index): void
    {
        $this->validateTableName($table);
        $this->validateIndexName($index);
        
        if ($this->indexExists($table, $index)) {
            try {
                Schema::table($table, function ($table) use ($index) {
                    $table->dropIndex($index);
                });
                
                \Illuminate\Support\Facades\Log::info('Index dropped', [
                    'table' => $table,
                    'index' => $index,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to drop index', [
                    'table' => $table,
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Drop foreign key if it exists (SECURED).
     * 
     * @param string $table The table name
     * @param string $foreignKey The foreign key name
     * @return void
     * @throws \InvalidArgumentException If table or foreign key name is invalid
     */
    protected function dropForeignKeyIfExists(string $table, string $foreignKey): void
    {
        $this->validateTableName($table);
        $this->validateIndexName($foreignKey); // Same validation rules
        
        if ($this->foreignKeyExists($table, $foreignKey)) {
            try {
                Schema::table($table, function ($table) use ($foreignKey) {
                    $table->dropForeign($foreignKey);
                });
                
                \Illuminate\Support\Facades\Log::info('Foreign key dropped', [
                    'table' => $table,
                    'foreign_key' => $foreignKey,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to drop foreign key', [
                    'table' => $table,
                    'foreign_key' => $foreignKey,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }
}
