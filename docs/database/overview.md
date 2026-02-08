# Database Overview

## Database Architecture

CFlow uses a robust database architecture designed for multi-tenancy, performance, and data integrity.

### Database Systems
- **Development**: SQLite with WAL mode
- **Production**: PostgreSQL 15+
- **Caching**: Redis for sessions and application cache
- **Search**: Database full-text search with indexes

### Multi-Tenancy Strategy
- **Team-based tenancy** - All data scoped to teams
- **Automatic scoping** - Filament v4 handles tenant isolation
- **Foreign key constraints** - Enforce referential integrity
- **Soft deletes** - Preserve data for audit trails

## Schema Design

### Core Tables

#### Users and Teams
```sql
-- Users table
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    current_team_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (current_team_id) REFERENCES teams(id)
);

-- Teams table (tenant isolation)
CREATE TABLE teams (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    settings JSON NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

-- Team user pivot
CREATE TABLE team_user (
    id BIGINT PRIMARY KEY,
    team_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    role VARCHAR(255) NOT NULL DEFAULT 'member',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(team_id, user_id)
);
```

#### Business Entities
```sql
-- Companies (customers/suppliers)
CREATE TABLE companies (
    id BIGINT PRIMARY KEY,
    team_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(255) NULL,
    address TEXT NULL,
    tax_number VARCHAR(255) NULL,
    type ENUM('customer', 'supplier') NOT NULL DEFAULT 'customer',
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_companies_team_type (team_id, type),
    INDEX idx_companies_active (is_active)
);

-- Invoices
CREATE TABLE invoices (
    id BIGINT PRIMARY KEY,
    team_id BIGINT NOT NULL,
    company_id BIGINT NOT NULL,
    number VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE(team_id, number),
    INDEX idx_invoices_team_status (team_id, status),
    INDEX idx_invoices_due_date (due_date),
    INDEX idx_invoices_company (company_id)
);

-- Invoice items
CREATE TABLE invoice_items (
    id BIGINT PRIMARY KEY,
    invoice_id BIGINT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(8,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);
```

### Audit and Tracking
```sql
-- Activity log for audit trails
CREATE TABLE activity_log (
    id BIGINT PRIMARY KEY,
    log_name VARCHAR(255) NULL,
    description TEXT NOT NULL,
    subject_type VARCHAR(255) NULL,
    subject_id BIGINT NULL,
    causer_type VARCHAR(255) NULL,
    causer_id BIGINT NULL,
    properties JSON NULL,
    batch_uuid UUID NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_activity_subject (subject_type, subject_id),
    INDEX idx_activity_causer (causer_type, causer_id),
    INDEX idx_activity_log_name (log_name),
    INDEX idx_activity_created_at (created_at)
);

-- Failed jobs tracking
CREATE TABLE failed_jobs (
    id BIGINT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

## Migration Strategy

### Migration Best Practices
```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])
                ->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->unique(['team_id', 'number']);
            $table->index(['team_id', 'status']);
            $table->index('due_date');
            $table->index('company_id');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
```

### Data Migration Patterns
```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new column
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('number');
        });
        
        // Migrate existing data
        DB::table('invoices')->chunkById(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'reference_number' => 'REF-' . $invoice->number,
                    ]);
            }
        });
        
        // Make column non-nullable
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('reference_number')->nullable(false)->change();
        });
    }
    
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
    }
};
```

## Model Relationships

### Eloquent Relationships
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Invoice extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'team_id',
        'company_id',
        'number',
        'amount',
        'tax_amount',
        'total_amount',
        'currency',
        'issue_date',
        'due_date',
        'status',
        'notes',
    ];
    
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
        ];
    }
    
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
    
    // Scopes for common queries
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
    
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', InvoiceStatus::PAID);
    }
    
    public function scopeByStatus(Builder $query, InvoiceStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
```

### Polymorphic Relationships
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class Comment extends Model
{
    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'content',
    ];
    
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// Usage in other models
final class Invoice extends Model
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
```

## Query Optimization

### Eager Loading
```php
// ✅ GOOD: Prevent N+1 queries
$invoices = Invoice::with([
    'company:id,name,email',
    'items:id,invoice_id,description,total_price',
    'team:id,name',
])->get();

// ✅ GOOD: Conditional eager loading
$invoices = Invoice::with([
    'company',
    'items' => function ($query) {
        $query->where('total_price', '>', 0);
    },
])->get();

// ✅ GOOD: Load counts efficiently
$invoices = Invoice::withCount([
    'items',
    'comments',
])->get();
```

### Database Indexes
```php
// Migration for performance indexes
Schema::table('invoices', function (Blueprint $table) {
    // Composite indexes for common queries
    $table->index(['team_id', 'status', 'due_date']);
    $table->index(['company_id', 'created_at']);
    
    // Full-text search index
    $table->fullText(['number', 'notes']);
    
    // Partial indexes (PostgreSQL)
    $table->index('due_date')->where('status', '!=', 'paid');
});
```

### Query Scopes
```php
<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TeamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check() && auth()->user()->current_team_id) {
            $builder->where('team_id', auth()->user()->current_team_id);
        }
    }
}

// Apply to models
final class Invoice extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TeamScope);
    }
}
```

## Database Seeding

### Seeder Organization
```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Core data
            TeamSeeder::class,
            UserSeeder::class,
            
            // Business data
            CompanySeeder::class,
            InvoiceSeeder::class,
            
            // Test data (only in non-production)
            ...$this->getTestSeeders(),
        ]);
    }
    
    private function getTestSeeders(): array
    {
        if (app()->environment('production')) {
            return [];
        }
        
        return [
            TestDataSeeder::class,
            DemoInvoiceSeeder::class,
        ];
    }
}
```

### Factory Patterns
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Team;
use App\Models\Company;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

final class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'company_id' => Company::factory(),
            'number' => $this->faker->unique()->numerify('INV-####'),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'tax_amount' => fn (array $attributes) => $attributes['amount'] * 0.21,
            'total_amount' => fn (array $attributes) => 
                $attributes['amount'] + $attributes['tax_amount'],
            'currency' => 'EUR',
            'issue_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'due_date' => fn (array $attributes) => 
                $attributes['issue_date']->modify('+30 days'),
            'status' => $this->faker->randomElement(InvoiceStatus::cases()),
        ];
    }
    
    public function draft(): static
    {
        return $this->state(['status' => InvoiceStatus::DRAFT]);
    }
    
    public function paid(): static
    {
        return $this->state(['status' => InvoiceStatus::PAID]);
    }
    
    public function overdue(): static
    {
        return $this->state([
            'status' => InvoiceStatus::SENT,
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
```

## Performance Monitoring

### Query Monitoring
```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

final class DatabaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (app()->environment('local')) {
            DB::listen(function (QueryExecuted $query) {
                if ($query->time > 100) { // Log slow queries
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ]);
                }
            });
        }
    }
}
```

### Database Health Checks
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class DatabaseHealthCheck extends Command
{
    protected $signature = 'db:health';
    protected $description = 'Check database health and performance';
    
    public function handle(): int
    {
        $this->info('Checking database health...');
        
        // Check connection
        try {
            DB::connection()->getPdo();
            $this->info('✓ Database connection: OK');
        } catch (\Exception $e) {
            $this->error('✗ Database connection: FAILED');
            return 1;
        }
        
        // Check query performance
        $start = microtime(true);
        DB::table('invoices')->count();
        $time = (microtime(true) - $start) * 1000;
        
        if ($time < 100) {
            $this->info("✓ Query performance: {$time}ms");
        } else {
            $this->warn("⚠ Query performance: {$time}ms (slow)");
        }
        
        // Check disk space (SQLite)
        if (config('database.default') === 'sqlite') {
            $dbPath = database_path('database.sqlite');
            $size = filesize($dbPath) / 1024 / 1024; // MB
            $this->info("Database size: {$size}MB");
        }
        
        return 0;
    }
}
```

## Backup and Recovery

### Backup Strategy
```php
// config/backup.php
return [
    'backup' => [
        'name' => env('APP_NAME', 'laravel-backup'),
        'source' => [
            'files' => [
                'include' => [
                    base_path(),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                ],
            ],
            'databases' => [
                'mysql',
                'sqlite',
            ],
        ],
        'destination' => [
            'filename_prefix' => '',
            'disks' => [
                'backup',
            ],
        ],
    ],
    
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailed::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFound::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailed::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessful::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFound::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessful::class => ['mail'],
        ],
    ],
];
```

### Recovery Procedures
```bash
# Restore from backup
php artisan backup:restore --disk=backup --filename=backup.zip

# Database-specific restore
# PostgreSQL
pg_restore -d database_name backup_file.sql

# MySQL
mysql database_name < backup_file.sql

# SQLite
cp backup_database.sqlite database/database.sqlite
```

## Related Documentation

- [Migration Guide](./migrations.md)
- [Query Optimization](./optimization.md)
- [Backup Procedures](./backup.md)
- [Performance Monitoring](../performance/database.md)