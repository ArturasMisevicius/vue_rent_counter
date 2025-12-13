# Performance Optimization Overview

## Performance Philosophy

CFlow prioritizes performance through proactive optimization, monitoring, and continuous improvement. All performance optimizations must be measured and validated.

## Performance Targets

### Response Time Goals
- **Page Load**: < 2 seconds (95th percentile)
- **API Responses**: < 500ms (95th percentile)
- **Database Queries**: < 100ms (95th percentile)
- **Background Jobs**: < 30 seconds (95th percentile)

### Throughput Goals
- **Concurrent Users**: 1000+ simultaneous users
- **API Requests**: 10,000+ requests/minute
- **Database Connections**: Efficient connection pooling
- **Memory Usage**: < 512MB per worker process

## Database Performance

### Query Optimization

#### N+1 Query Prevention
```php
// ❌ BAD: N+1 queries
$invoices = Invoice::all();
foreach ($invoices as $invoice) {
    echo $invoice->tenant->name; // Separate query for each invoice
}

// ✅ GOOD: Eager loading
$invoices = Invoice::with('tenant')->get();
foreach ($invoices as $invoice) {
    echo $invoice->tenant->name; // No additional queries
}
```

#### Repository Pattern with Eager Loading
```php
<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use Illuminate\Database\Eloquent\Collection;

final readonly class EloquentInvoiceRepository implements InvoiceRepository
{
    private const DEFAULT_RELATIONS = [
        'tenant:id,name,email',
        'items:id,invoice_id,description,amount',
        'payments:id,invoice_id,amount,paid_at',
    ];
    
    public function findWithRelations(int $id): ?Invoice
    {
        return Invoice::with(self::DEFAULT_RELATIONS)->find($id);
    }
    
    public function getAllWithRelations(): Collection
    {
        return Invoice::with(self::DEFAULT_RELATIONS)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
```

#### Query Monitoring
```php
// Monitor slow queries in development
if (app()->environment('local')) {
    DB::listen(function ($query) {
        if ($query->time > 100) { // Log queries > 100ms
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        }
    });
}
```

### Database Indexing Strategy

#### Primary Indexes
```sql
-- Foreign key indexes
CREATE INDEX idx_invoices_tenant_id ON invoices(tenant_id);
CREATE INDEX idx_invoice_items_invoice_id ON invoice_items(invoice_id);

-- Status and date indexes
CREATE INDEX idx_invoices_status_due_date ON invoices(status, due_date);
CREATE INDEX idx_invoices_created_at ON invoices(created_at);

-- Search indexes
CREATE INDEX idx_tenants_name ON tenants(name);
CREATE INDEX idx_tenants_email ON tenants(email);
```

#### Composite Indexes
```sql
-- Multi-column indexes for common queries
CREATE INDEX idx_invoices_tenant_status ON invoices(tenant_id, status);
CREATE INDEX idx_payments_invoice_date ON payments(invoice_id, paid_at);
```

### Connection Optimization
```php
// config/database.php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => [
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ],
    ],
],
```

## Caching Strategy

### Laravel Cache Configuration
```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

### Cache Patterns

#### Repository Caching
```php
<?php

declare(strict_types=1);

namespace App\Repositories\Cached;

use App\Repositories\InvoiceRepository;
use Illuminate\Support\Facades\Cache;

final readonly class CachedInvoiceRepository implements InvoiceRepository
{
    public function __construct(
        private InvoiceRepository $repository,
    ) {}
    
    public function find(int $id): ?Invoice
    {
        return Cache::remember(
            "invoice.{$id}",
            3600, // 1 hour
            fn() => $this->repository->find($id)
        );
    }
    
    public function getOverdue(): Collection
    {
        return Cache::remember(
            'invoices.overdue',
            300, // 5 minutes
            fn() => $this->repository->getOverdue()
        );
    }
}
```

#### Tagged Caching
```php
// Cache with tags for easy invalidation
Cache::tags(['invoices', 'tenant:' . $tenantId])
    ->remember("tenant.{$tenantId}.invoices", 3600, function () use ($tenantId) {
        return Invoice::where('tenant_id', $tenantId)->get();
    });

// Invalidate all invoice caches for a tenant
Cache::tags(['tenant:' . $tenantId])->flush();
```

#### Cache Invalidation
```php
<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;

final readonly class UpdateInvoiceAction
{
    public function execute(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        
        // Invalidate related caches
        Cache::forget("invoice.{$invoice->id}");
        Cache::tags(['tenant:' . $invoice->tenant_id])->flush();
        
        return $invoice->fresh();
    }
}
```

## Frontend Performance

### Asset Optimization
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['alpinejs'],
                    utils: ['lodash'],
                },
            },
        },
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
    },
});
```

### Image Optimization
```php
// Optimize images on upload
use Intervention\Image\Facades\Image;

public function optimizeImage(UploadedFile $file): string
{
    $image = Image::make($file);
    
    // Resize if too large
    if ($image->width() > 1920) {
        $image->resize(1920, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
    }
    
    // Compress
    $image->encode('jpg', 85);
    
    return $image->save();
}
```

### Lazy Loading
```php
// Livewire component with lazy loading
use Livewire\Component;

class InvoiceList extends Component
{
    public bool $loaded = false;
    
    public function loadInvoices(): void
    {
        $this->loaded = true;
    }
    
    public function render()
    {
        return view('livewire.invoice-list', [
            'invoices' => $this->loaded 
                ? Invoice::with('tenant')->paginate(25)
                : collect(),
        ]);
    }
}
```

## Memory Management

### Memory Efficient Processing
```php
// Process large datasets efficiently
public function processLargeDataset(): void
{
    // Use chunking to avoid memory issues
    Invoice::chunk(100, function ($invoices) {
        foreach ($invoices as $invoice) {
            $this->processInvoice($invoice);
        }
    });
    
    // Or use lazy collections
    Invoice::lazy()->each(function ($invoice) {
        $this->processInvoice($invoice);
    });
}
```

### Memory Monitoring
```php
// Monitor memory usage
public function monitorMemory(): void
{
    $startMemory = memory_get_usage(true);
    
    // Perform operations
    $this->performOperations();
    
    $endMemory = memory_get_usage(true);
    $memoryUsed = $endMemory - $startMemory;
    
    if ($memoryUsed > 50 * 1024 * 1024) { // 50MB
        Log::warning('High memory usage detected', [
            'memory_used' => $memoryUsed,
            'operation' => 'performOperations',
        ]);
    }
}
```

## Background Job Optimization

### Queue Configuration
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

### Job Optimization
```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessInvoiceBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $timeout = 300; // 5 minutes
    public int $tries = 3;
    public int $backoff = 60; // 1 minute
    
    public function __construct(
        private readonly array $invoiceIds,
    ) {}
    
    public function handle(): void
    {
        // Process in smaller batches to avoid memory issues
        collect($this->invoiceIds)
            ->chunk(50)
            ->each(function ($chunk) {
                Invoice::whereIn('id', $chunk)
                    ->with('tenant')
                    ->each(function ($invoice) {
                        $this->processInvoice($invoice);
                    });
            });
    }
}
```

## Monitoring and Profiling

### Performance Monitoring
```php
// Monitor application performance
use Illuminate\Support\Facades\Log;

class PerformanceMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000; // Convert to ms
        $memoryUsed = memory_get_usage(true) - $startMemory;
        
        if ($duration > 1000) { // Log slow requests
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'memory_used' => $memoryUsed,
                'user_id' => auth()->id(),
            ]);
        }
        
        return $response;
    }
}
```

### Database Query Monitoring
```php
// Monitor database performance
use Illuminate\Database\Events\QueryExecuted;

Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
    if ($query->time > 100) { // Log queries > 100ms
        Log::warning('Slow database query', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'connection' => $query->connectionName,
        ]);
    }
});
```

## Performance Testing

### Load Testing
```php
// Pest performance test
it('handles concurrent user creation', function () {
    $startTime = microtime(true);
    
    // Simulate concurrent requests
    $promises = [];
    for ($i = 0; $i < 100; $i++) {
        $promises[] = Http::async()->post('/api/users', [
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'password' => 'password123',
        ]);
    }
    
    $responses = Http::pool($promises);
    
    $duration = microtime(true) - $startTime;
    
    expect($duration)->toBeLessThan(5.0); // Should complete in < 5 seconds
    expect(collect($responses)->every(fn($r) => $r->successful()))->toBeTrue();
});
```

### Memory Testing
```php
it('processes large datasets without memory issues', function () {
    $startMemory = memory_get_usage(true);
    
    // Create large dataset
    Invoice::factory()->count(1000)->create();
    
    // Process dataset
    app(ProcessInvoicesAction::class)->execute();
    
    $endMemory = memory_get_usage(true);
    $memoryUsed = $endMemory - $startMemory;
    
    expect($memoryUsed)->toBeLessThan(100 * 1024 * 1024); // < 100MB
});
```

## Configuration Optimization

### PHP Configuration
```ini
; php.ini optimizations
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M

; OPcache settings
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.save_comments = 0
```

### Laravel Optimizations
```bash
# Production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Clear optimizations (development)
php artisan optimize:clear
```

## CDN and Static Assets

### Asset Delivery
```php
// config/filesystems.php
'disks' => [
    'cdn' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ],
],
```

### Image Optimization Service
```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class ImageOptimizationService
{
    public function optimizeAndStore(UploadedFile $file, string $path): string
    {
        // Optimize image
        $optimizedImage = $this->optimize($file);
        
        // Store on CDN
        $filename = $this->generateFilename($file);
        Storage::disk('cdn')->put($path . '/' . $filename, $optimizedImage);
        
        return Storage::disk('cdn')->url($path . '/' . $filename);
    }
    
    private function optimize(UploadedFile $file): string
    {
        // Image optimization logic
        return $file->getContent();
    }
}
```

## Performance Checklist

### Development
- [ ] Use eager loading to prevent N+1 queries
- [ ] Add database indexes for frequently queried columns
- [ ] Implement caching for expensive operations
- [ ] Optimize images and assets
- [ ] Use background jobs for heavy processing

### Testing
- [ ] Write performance tests for critical paths
- [ ] Monitor memory usage in tests
- [ ] Test with realistic data volumes
- [ ] Validate response times under load

### Production
- [ ] Enable OPcache and other PHP optimizations
- [ ] Configure Redis for caching and sessions
- [ ] Set up CDN for static assets
- [ ] Monitor application performance
- [ ] Implement alerting for performance issues

## Related Documentation

- [Database Design](../database/optimization.md)
- [Caching Strategy](./caching.md)
- [Monitoring Setup](../monitoring/overview.md)
- [Testing Performance](../testing/performance.md)