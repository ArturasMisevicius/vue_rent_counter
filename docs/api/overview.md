# API Documentation Overview

## API Architecture

CFlow provides a comprehensive REST API for external integrations and mobile applications, built with Laravel Sanctum for authentication and following RESTful conventions.

### Core Principles
- **RESTful Design** - Standard HTTP methods and status codes
- **JSON API** - Consistent JSON request/response format
- **Authentication** - Sanctum token-based authentication
- **Rate Limiting** - Protect against abuse
- **Versioning** - API versioning for backward compatibility
- **Documentation** - OpenAPI/Swagger documentation

### Base URL Structure
```
Production:  https://api.cflow.app/v1/
Staging:     https://staging-api.cflow.app/v1/
Development: http://localhost:8000/api/v1/
```

## Authentication

### Sanctum Token Authentication
```php
// Generate API token
POST /api/auth/tokens
{
    "name": "Mobile App Token",
    "abilities": ["invoices:read", "invoices:write"],
    "expires_at": "2024-12-31T23:59:59Z"
}

// Response
{
    "token": "1|abc123...",
    "expires_at": "2024-12-31T23:59:59Z",
    "abilities": ["invoices:read", "invoices:write"]
}
```

### Using API Tokens
```bash
# Include token in Authorization header
curl -H "Authorization: Bearer 1|abc123..." \
     -H "Accept: application/json" \
     https://api.cflow.app/v1/invoices
```

### Token Abilities
```php
// Available token abilities
'invoices:read'     // Read invoices
'invoices:write'    // Create/update invoices
'companies:read'    // Read companies
'companies:write'   // Create/update companies
'reports:read'      // Access reports
'admin:all'         // Full admin access
```

## API Endpoints

### Authentication Endpoints
```php
POST   /api/auth/login          // Login with credentials
POST   /api/auth/logout         // Logout current session
POST   /api/auth/tokens         // Create API token
GET    /api/auth/user           // Get current user
DELETE /api/auth/tokens/{id}    // Revoke API token
```

### Invoice Management
```php
GET    /api/invoices            // List invoices
POST   /api/invoices            // Create invoice
GET    /api/invoices/{id}       // Get invoice details
PUT    /api/invoices/{id}       // Update invoice
DELETE /api/invoices/{id}       // Delete invoice
POST   /api/invoices/{id}/send  // Send invoice
GET    /api/invoices/{id}/pdf   // Download PDF
```

### Company Management
```php
GET    /api/companies           // List companies
POST   /api/companies           // Create company
GET    /api/companies/{id}      // Get company details
PUT    /api/companies/{id}      // Update company
DELETE /api/companies/{id}      // Delete company
GET    /api/companies/{id}/invoices // Company invoices
```

### Reports and Analytics
```php
GET    /api/reports/revenue     // Revenue reports
GET    /api/reports/invoices    // Invoice analytics
GET    /api/reports/companies   // Company statistics
GET    /api/reports/taxes       // Tax reports
```

## Request/Response Format

### Standard Request Format
```json
{
    "data": {
        "name": "Acme Corp",
        "email": "contact@acme.com",
        "phone": "+1234567890"
    },
    "meta": {
        "source": "mobile_app",
        "version": "1.2.0"
    }
}
```

### Standard Response Format
```json
{
    "data": {
        "id": 123,
        "name": "Acme Corp",
        "email": "contact@acme.com",
        "created_at": "2024-12-13T10:00:00Z",
        "updated_at": "2024-12-13T10:00:00Z"
    },
    "meta": {
        "timestamp": "2024-12-13T10:00:00Z",
        "version": "v1"
    }
}
```

### Collection Response Format
```json
{
    "data": [
        {
            "id": 123,
            "name": "Invoice #001",
            "amount": "1000.00"
        }
    ],
    "links": {
        "first": "/api/invoices?page=1",
        "last": "/api/invoices?page=10",
        "prev": null,
        "next": "/api/invoices?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "per_page": 15,
        "to": 15,
        "total": 150
    }
}
```

## API Resources

### Invoice Resource
```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

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
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'issue_date' => $this->issue_date->toDateString(),
            'due_date' => $this->due_date->toDateString(),
            'status' => $this->status->value,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'items' => InvoiceItemResource::collection(
                $this->whenLoaded('items')
            ),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

### Company Resource
```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'tax_number' => $this->tax_number,
            'type' => $this->type->value,
            'is_active' => $this->is_active,
            'invoices_count' => $this->whenCounted('invoices'),
            'total_revenue' => $this->when(
                $request->user()->can('view-financial-data'),
                $this->total_revenue
            ),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

## API Controllers

### Base API Controller
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => 'v1',
            ],
        ], $status);
    }
    
    protected function errorResponse(
        string $message = 'Error',
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => 'v1',
            ],
        ], $status);
    }
}
```

### Invoice API Controller
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Invoice\CreateInvoiceAction;
use App\Actions\Invoice\UpdateInvoiceAction;
use App\Data\Invoice\CreateInvoiceData;
use App\Data\Invoice\UpdateInvoiceData;
use App\Http\Requests\Api\V1\CreateInvoiceRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceRequest;
use App\Http\Resources\V1\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvoiceController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);
        
        $invoices = Invoice::query()
            ->with(['company', 'items'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->company_id, fn($q) => $q->where('company_id', $request->company_id))
            ->latest()
            ->paginate($request->per_page ?? 15);
        
        return $this->successResponse(
            InvoiceResource::collection($invoices)
        );
    }
    
    public function store(
        CreateInvoiceRequest $request,
        CreateInvoiceAction $action
    ): JsonResponse {
        $data = CreateInvoiceData::fromRequest($request);
        $invoice = $action->execute($data);
        
        return $this->successResponse(
            new InvoiceResource($invoice->load(['company', 'items'])),
            'Invoice created successfully',
            201
        );
    }
    
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);
        
        return $this->successResponse(
            new InvoiceResource($invoice->load(['company', 'items']))
        );
    }
    
    public function update(
        UpdateInvoiceRequest $request,
        Invoice $invoice,
        UpdateInvoiceAction $action
    ): JsonResponse {
        $this->authorize('update', $invoice);
        
        $data = UpdateInvoiceData::fromRequest($request);
        $invoice = $action->execute($invoice, $data);
        
        return $this->successResponse(
            new InvoiceResource($invoice->load(['company', 'items'])),
            'Invoice updated successfully'
        );
    }
    
    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);
        
        $invoice->delete();
        
        return $this->successResponse(
            null,
            'Invoice deleted successfully'
        );
    }
}
```

## Rate Limiting

### API Rate Limits
```php
// config/cache.php
'limiter' => [
    'api' => [
        'driver' => 'redis',
        'connection' => 'default',
    ],
],

// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
});

// Custom rate limits
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('reports/generate', [ReportController::class, 'generate']);
});
```

### Rate Limit Headers
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
Retry-After: 60
```

## Error Handling

### Standard Error Responses
```json
// Validation Error (422)
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "amount": ["The amount must be greater than 0."]
    },
    "meta": {
        "timestamp": "2024-12-13T10:00:00Z",
        "version": "v1"
    }
}

// Authentication Error (401)
{
    "success": false,
    "message": "Unauthenticated.",
    "meta": {
        "timestamp": "2024-12-13T10:00:00Z",
        "version": "v1"
    }
}

// Authorization Error (403)
{
    "success": false,
    "message": "This action is unauthorized.",
    "meta": {
        "timestamp": "2024-12-13T10:00:00Z",
        "version": "v1"
    }
}

// Not Found Error (404)
{
    "success": false,
    "message": "Resource not found.",
    "meta": {
        "timestamp": "2024-12-13T10:00:00Z",
        "version": "v1"
    }
}

// Server Error (500)
{
    "success": false,
    "message": "Internal server error.",
    "meta": {
        "timestamp": "2024-12-13T10:00:00Z",
        "version": "v1"
    }
}
```

### Custom Exception Handler
```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e): JsonResponse
    {
        if ($request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }
        
        return parent::render($request, $e);
    }
    
    private function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        $status = 500;
        $message = 'Internal server error';
        $errors = [];
        
        if ($e instanceof ValidationException) {
            $status = 422;
            $message = 'The given data was invalid.';
            $errors = $e->errors();
        } elseif ($e instanceof NotFoundHttpException) {
            $status = 404;
            $message = 'Resource not found.';
        } elseif ($e instanceof AuthenticationException) {
            $status = 401;
            $message = 'Unauthenticated.';
        } elseif ($e instanceof AuthorizationException) {
            $status = 403;
            $message = 'This action is unauthorized.';
        }
        
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => 'v1',
            ],
        ], $status);
    }
}
```

## API Versioning

### Version Strategy
```php
// routes/api/v1.php
Route::prefix('v1')->group(function () {
    Route::apiResource('invoices', V1\InvoiceController::class);
    Route::apiResource('companies', V1\CompanyController::class);
});

// routes/api/v2.php
Route::prefix('v2')->group(function () {
    Route::apiResource('invoices', V2\InvoiceController::class);
    Route::apiResource('companies', V2\CompanyController::class);
});
```

### Version Headers
```http
Accept: application/vnd.cflow.v1+json
Content-Type: application/vnd.cflow.v1+json
```

## Testing API Endpoints

### Feature Tests
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Invoice;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class InvoiceApiTest extends TestCase
{
    public function test_can_list_invoices(): void
    {
        $user = User::factory()->create();
        $invoices = Invoice::factory()->count(3)->create([
            'team_id' => $user->current_team_id,
        ]);
        
        Sanctum::actingAs($user, ['invoices:read']);
        
        $response = $this->getJson('/api/v1/invoices');
        
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'number',
                        'amount',
                        'status',
                    ],
                ],
                'meta',
            ]);
    }
    
    public function test_can_create_invoice(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'team_id' => $user->current_team_id,
        ]);
        
        Sanctum::actingAs($user, ['invoices:write']);
        
        $response = $this->postJson('/api/v1/invoices', [
            'company_id' => $company->id,
            'amount' => 1000.00,
            'issue_date' => '2024-12-13',
            'due_date' => '2025-01-13',
            'items' => [
                [
                    'description' => 'Service fee',
                    'quantity' => 1,
                    'unit_price' => 1000.00,
                ],
            ],
        ]);
        
        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'number',
                    'amount',
                ],
                'meta',
            ]);
    }
    
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/invoices');
        
        $response->assertUnauthorized();
    }
    
    public function test_requires_proper_abilities(): void
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($user, ['companies:read']); // Wrong ability
        
        $response = $this->getJson('/api/v1/invoices');
        
        $response->assertForbidden();
    }
}
```

## API Documentation

### OpenAPI/Swagger
```yaml
# api-docs.yaml
openapi: 3.0.0
info:
  title: CFlow API
  description: CFlow accounting and invoicing API
  version: 1.0.0
  contact:
    name: CFlow Support
    email: support@cflow.app

servers:
  - url: https://api.cflow.app/v1
    description: Production server
  - url: https://staging-api.cflow.app/v1
    description: Staging server

security:
  - bearerAuth: []

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    Invoice:
      type: object
      properties:
        id:
          type: integer
          example: 123
        number:
          type: string
          example: "INV-001"
        amount:
          type: string
          example: "1000.00"
        status:
          type: string
          enum: [draft, sent, paid, overdue, cancelled]
          example: "draft"

paths:
  /invoices:
    get:
      summary: List invoices
      parameters:
        - name: page
          in: query
          schema:
            type: integer
        - name: per_page
          in: query
          schema:
            type: integer
            maximum: 100
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Invoice'
```

## Related Documentation

- [Authentication Setup](authentication.md)
- [Rate Limiting Configuration](./rate-limiting.md)
- [API Testing](./testing.md)
- [Security Guidelines](../security/api.md)