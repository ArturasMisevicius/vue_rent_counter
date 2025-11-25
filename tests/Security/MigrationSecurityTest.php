<?php

use App\Database\Concerns\ManagesIndexes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::spy();
});

test('migration validates table names against SQL injection', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testValidation(string $table, string $index) {
            return $this->indexExists($table, $index);
        }
    };
    
    // Test SQL injection attempts
    expect(fn() => $migration->testValidation('users; DROP TABLE users;--', 'test'))
        ->toThrow(InvalidArgumentException::class, 'Invalid table name');
    
    expect(fn() => $migration->testValidation('users\' OR \'1\'=\'1', 'test'))
        ->toThrow(InvalidArgumentException::class, 'Invalid table name');
    
    expect(fn() => $migration->testValidation('../../../etc/passwd', 'test'))
        ->toThrow(InvalidArgumentException::class, 'Invalid table name');
});

test('migration validates index names against SQL injection', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testValidation(string $table, string $index) {
            return $this->indexExists($table, $index);
        }
    };
    
    // Test SQL injection attempts
    expect(fn() => $migration->testValidation('users', 'idx; DROP TABLE users;--'))
        ->toThrow(InvalidArgumentException::class, 'Invalid index name');
    
    expect(fn() => $migration->testValidation('users', 'idx\' OR \'1\'=\'1'))
        ->toThrow(InvalidArgumentException::class, 'Invalid index name');
});

test('migration logs security violations', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testValidation(string $table, string $index) {
            return $this->indexExists($table, $index);
        }
    };
    
    $exceptionThrown = false;
    try {
        $migration->testValidation('users; DROP TABLE users;--', 'test');
    } catch (InvalidArgumentException $e) {
        $exceptionThrown = true;
    }
    
    expect($exceptionThrown)->toBeTrue('Expected InvalidArgumentException to be thrown');
    
    Log::shouldHaveReceived('warning')
        ->atLeast()->once()
        ->with('Invalid table name attempted', \Mockery::on(function ($context) {
            return isset($context['table']) && str_contains($context['table'], 'DROP TABLE');
        }));
});

test('migration validates table name length', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testValidation(string $table, string $index) {
            return $this->indexExists($table, $index);
        }
    };
    
    $longTableName = str_repeat('a', 65); // 65 characters
    
    expect(fn() => $migration->testValidation($longTableName, 'test'))
        ->toThrow(InvalidArgumentException::class, 'Table name too long');
});

test('migration validates index name length', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testValidation(string $table, string $index) {
            return $this->indexExists($table, $index);
        }
    };
    
    $longIndexName = str_repeat('a', 65); // 65 characters
    
    expect(fn() => $migration->testValidation('users', $longIndexName))
        ->toThrow(InvalidArgumentException::class, 'Index name too long');
});

test('migration accepts valid table and index names', function () {
    $migration = new class extends Migration {
        use ManagesIndexes;
        
        public function testValidation(string $table, string $index) {
            return $this->indexExists($table, $index);
        }
    };
    
    // These should not throw exceptions
    $result = $migration->testValidation('users', 'users_email_index');
    expect($result)->toBeBool();
    
    $result = $migration->testValidation('meter_readings', 'meter_readings_meter_id_index');
    expect($result)->toBeBool();
});
