# Laravel Framework Documentation

## Laravel 12 Standards

CFlow uses Laravel 12 with strict adherence to modern PHP and Laravel best practices.

## Core Principles

### PHP 8.4+ Features
- Strict typing: `declare(strict_types=1);`
- Readonly properties and final classes by default
- Property promotion in constructors
- Match expressions over switch statements
- Named arguments for clarity

### Laravel 12 Patterns
- Action-based architecture for business logic
- Repository pattern for data access
- Service layer for complex operations
- Event-driven architecture for decoupling

## Architecture Patterns

### Action Classes
Every business operation is encapsulated in a single Action class:

```php
<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Data\User\CreateUserData;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

final readonly class CreateUserAction
{
    public function __construct(
        private UserRepository $repository,
    ) {}
    
    public function execute(CreateUserData $data): User
    {
        return $this->repository->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);
    }
}
```

### Repository Pattern
Abstract data access behind interfaces:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepository
{
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
    public function getActive(): Collection;
}
```

### Data Transfer Objects
Use typed DTOs instead of arrays:

```php
<?php

declare(strict_types=1);

namespace App\Data\User;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
    
    public static function fromRequest(CreateUserRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
```

## Controller Standards

### Thin Controllers
Controllers should be under 50 lines and delegate to Action classes:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\User\CreateUserAction;
use App\Data\User\CreateUserData;
use App\Http\Requests\CreateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

final class UserController extends Controller
{
    public function store(
        CreateUserRequest $request,
        CreateUserAction $action
    ): JsonResponse {
        $data = CreateUserData::fromRequest($request);
        $user = $action->execute($data);
        
        return response()->json(
            new UserResource($user),
            201
        );
    }
}
```

### Required Triad
Every controller action must use:
1. **FormRequest** - Input validation
2. **Policy** - Authorization
3. **Action** - Business logic

```php
public function store(CreateUserRequest $request): JsonResponse
{
    $this->authorize('create', User::class); // Policy
    
    $data = CreateUserData::fromRequest($request); // FormRequest
    $user = app(CreateUserAction::class)->execute($data); // Action
    
    return response()->json(new UserResource($user), 201);
}
```

## Model Standards

### No Direct Eloquent Usage
Never use Eloquent directly in controllers or Livewire components:

```php
// ❌ WRONG
public function index()
{
    return User::where('active', true)->get();
}

// ✅ CORRECT
public function index(GetActiveUsersAction $action)
{
    return $action->execute();
}
```

### Model Design
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Invoice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'number',
        'amount',
        'due_date',
        'tenant_id',
    ];
    
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

## Query Optimization

### Repository Queries
All queries go through dedicated Query classes or repositories:

```php
<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class InvoiceQuery
{
    public function getOverdue(): Collection
    {
        return Invoice::query()
            ->with(['tenant', 'items'])
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->get();
    }
    
    public function getByTenant(int $tenantId): Builder
    {
        return Invoice::query()
            ->with(['items'])
            ->where('tenant_id', $tenantId);
    }
}
```

### Preventing N+1 Queries
Always use eager loading:

```php
// ✅ CORRECT
$invoices = Invoice::with(['tenant', 'items'])->get();

// ❌ WRONG
$invoices = Invoice::all();
foreach ($invoices as $invoice) {
    echo $invoice->tenant->name; // N+1 query
}
```

## Caching Patterns

### Laravel 12 Cache Methods
Use new `once()` and `remember()` patterns:

```php
use Illuminate\Support\Facades\Cache;

// Cache expensive operations
public function getExpensiveData(): array
{
    return Cache::remember(
        'expensive-data',
        now()->addHour(),
        fn() => $this->performExpensiveOperation()
    );
}

// One-time cache (per request)
public function getRequestData(): array
{
    return once(fn() => $this->loadData());
}
```

## Background Processing

### Queue Jobs
```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Invoice\SendInvoiceAction;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        private readonly Invoice $invoice,
    ) {}
    
    public function handle(SendInvoiceAction $action): void
    {
        $action->execute($this->invoice);
    }
}
```

### Event Listeners
```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Jobs\SendInvoiceJob;

final readonly class SendInvoiceNotification
{
    public function handle(InvoiceCreated $event): void
    {
        SendInvoiceJob::dispatch($event->invoice);
    }
}
```

## Validation

### Form Requests
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Invoice::class);
    }
    
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date', 'after:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
```

## Security

### Authorization with Policies
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

final class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_invoices');
    }
    
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('view_invoices') 
            && $invoice->tenant->team_id === $user->current_team_id;
    }
    
    public function create(User $user): bool
    {
        return $user->hasPermission('create_invoices');
    }
    
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('update_invoices')
            && $invoice->tenant->team_id === $user->current_team_id
            && $invoice->status === InvoiceStatus::DRAFT;
    }
}
```

## API Design

### API Resources
```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'amount' => $this->amount,
            'due_date' => $this->due_date->toDateString(),
            'status' => $this->status->value,
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'items' => InvoiceItemResource::collection(
                $this->whenLoaded('items')
            ),
        ];
    }
}
```

### API Versioning
```php
// routes/api/v1.php
Route::prefix('v1')->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
});

// routes/api/v2.php
Route::prefix('v2')->group(function () {
    Route::apiResource('invoices', V2\InvoiceController::class);
});
```

## Testing Integration

### Feature Tests
```php
it('creates invoice through API', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['team_id' => $user->current_team_id]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/invoices', [
            'tenant_id' => $tenant->id,
            'amount' => 100.00,
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'description' => 'Service fee',
                    'amount' => 100.00,
                ],
            ],
        ]);
    
    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'number',
                'amount',
                'due_date',
                'status',
            ],
        ]);
});
```

## Performance Optimization

### Database Optimization
```php
// Use indexes for frequently queried columns
Schema::table('invoices', function (Blueprint $table) {
    $table->index(['tenant_id', 'status']);
    $table->index(['due_date', 'status']);
});

// Use database transactions for consistency
DB::transaction(function () {
    $invoice = Invoice::create($data);
    $invoice->items()->createMany($items);
    $invoice->generateNumber();
});
```

### Memory Management
```php
// Process large datasets in chunks
Invoice::chunk(100, function ($invoices) {
    foreach ($invoices as $invoice) {
        $this->processInvoice($invoice);
    }
});

// Use lazy collections for memory efficiency
Invoice::lazy()->each(function ($invoice) {
    $this->processInvoice($invoice);
});
```

## Configuration Management

### Environment Configuration
```php
// config/invoice.php
return [
    'default_due_days' => env('INVOICE_DEFAULT_DUE_DAYS', 30),
    'number_prefix' => env('INVOICE_NUMBER_PREFIX', 'INV'),
    'auto_send' => env('INVOICE_AUTO_SEND', false),
];
```

### Service Providers
```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\InvoiceRepository;
use App\Repositories\Eloquent\EloquentInvoiceRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InvoiceRepository::class,
            EloquentInvoiceRepository::class
        );
    }
}
```

## Error Handling

### Custom Exceptions
```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class InvoiceAlreadyPaidException extends Exception
{
    public static function forInvoice(string $invoiceNumber): self
    {
        return new self("Invoice {$invoiceNumber} is already paid");
    }
}
```

### Global Exception Handler
```php
public function register(): void
{
    $this->renderable(function (InvoiceAlreadyPaidException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
        
        return back()->withErrors(['invoice' => $e->getMessage()]);
    });
}
```

## Related Documentation

- [Architecture Overview](../architecture/overview.md)
- [Development Standards](../development/standards.md)
- [Testing Guidelines](../testing/overview.md)
- [Performance Optimization](../performance/overview.md)