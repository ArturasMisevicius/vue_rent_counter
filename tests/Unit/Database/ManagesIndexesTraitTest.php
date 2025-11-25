<?php

use App\Database\Concerns\ManagesIndexes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Create a test table
    Schema::create('test_indexes_table', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_indexes_table');
});

test('indexExists returns false for non-existent index', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testIndexExists(string $table, string $index): bool
        {
            return $this->indexExists($table, $index);
        }
    };
    
    expect($migration->testIndexExists('test_indexes_table', 'non_existent_index'))->toBeFalse();
});

test('indexExists returns true for existing index', function () {
    // Create an index
    Schema::table('test_indexes_table', function (Blueprint $table) {
        $table->index('name', 'test_name_index');
    });
    
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testIndexExists(string $table, string $index): bool
        {
            return $this->indexExists($table, $index);
        }
    };
    
    expect($migration->testIndexExists('test_indexes_table', 'test_name_index'))->toBeTrue();
});

test('indexExists returns false for non-existent table', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testIndexExists(string $table, string $index): bool
        {
            return $this->indexExists($table, $index);
        }
    };
    
    expect($migration->testIndexExists('non_existent_table', 'some_index'))->toBeFalse();
});

test('columnExists returns true for existing column', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testColumnExists(string $table, string $column): bool
        {
            return $this->columnExists($table, $column);
        }
    };
    
    expect($migration->testColumnExists('test_indexes_table', 'name'))->toBeTrue();
    expect($migration->testColumnExists('test_indexes_table', 'email'))->toBeTrue();
});

test('columnExists returns false for non-existent column', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testColumnExists(string $table, string $column): bool
        {
            return $this->columnExists($table, $column);
        }
    };
    
    expect($migration->testColumnExists('test_indexes_table', 'non_existent_column'))->toBeFalse();
});

test('dropIndexIfExists drops existing index', function () {
    // Create an index
    Schema::table('test_indexes_table', function (Blueprint $table) {
        $table->index('name', 'test_name_index');
    });
    
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testDropIndexIfExists(string $table, string $index): void
        {
            $this->dropIndexIfExists($table, $index);
        }
        
        public function testIndexExists(string $table, string $index): bool
        {
            return $this->indexExists($table, $index);
        }
    };
    
    expect($migration->testIndexExists('test_indexes_table', 'test_name_index'))->toBeTrue();
    
    $migration->testDropIndexIfExists('test_indexes_table', 'test_name_index');
    
    expect($migration->testIndexExists('test_indexes_table', 'test_name_index'))->toBeFalse();
});

test('dropIndexIfExists does not throw error for non-existent index', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testDropIndexIfExists(string $table, string $index): void
        {
            $this->dropIndexIfExists($table, $index);
        }
    };
    
    // Should not throw exception
    $migration->testDropIndexIfExists('test_indexes_table', 'non_existent_index');
    
    expect(true)->toBeTrue();
});

test('getTableIndexes returns array of indexes', function () {
    // Create multiple indexes
    Schema::table('test_indexes_table', function (Blueprint $table) {
        $table->index('name', 'test_name_index');
        $table->index('email', 'test_email_index');
    });
    
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testGetTableIndexes(string $table): array
        {
            return $this->getTableIndexes($table);
        }
    };
    
    $indexes = $migration->testGetTableIndexes('test_indexes_table');
    
    expect($indexes)->toBeArray()
        ->and(isset($indexes['test_name_index']))->toBeTrue()
        ->and(isset($indexes['test_email_index']))->toBeTrue();
});

test('foreignKeyExists returns false for non-existent foreign key', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testForeignKeyExists(string $table, string $foreignKey): bool
        {
            return $this->foreignKeyExists($table, $foreignKey);
        }
    };
    
    expect($migration->testForeignKeyExists('test_indexes_table', 'non_existent_fk'))->toBeFalse();
});
