# Security Guidelines Overview

## Security Philosophy

CFlow implements defense-in-depth security with multiple layers of protection. Security is integrated into every aspect of the application, from development to deployment.

## Security Principles

### 1. Secure by Default
- All features are secure by default
- Opt-in for less secure options
- Fail securely when errors occur
- Principle of least privilege

### 2. Defense in Depth
- Multiple security layers
- No single point of failure
- Redundant security controls
- Comprehensive monitoring

### 3. Zero Trust Architecture
- Verify every request
- Authenticate and authorize everything
- Monitor all activities
- Encrypt all communications

## Authentication Security

### Password Security
```php
// config/auth.php
'password_requirements' => [
    'min_length' => 12,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => true,
    'prevent_common' => true,
    'prevent_personal_info' => true,
],
```

### Multi-Factor Authentication
```php
// Enable MFA for all admin users
public function panel(Panel $panel): Panel
{
    return $panel
        ->mfa(
            requireMfa: true,
            enforceMfa: fn () => auth()->user()->hasRole('admin'),
        );
}
```

### Session Security
```php
// config/session.php
'lifetime' => 120, // 2 hours
'expire_on_close' => true,
'encrypt' => true,
'http_only' => true,
'same_site' => 'strict',
'secure' => true, // HTTPS only
'domain' => env('SESSION_DOMAIN'),
```

### Account Lockout
```php
<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

final readonly class LoginAttemptService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900; // 15 minutes
    
    public function recordFailedAttempt(string $email, string $ip): void
    {
        $key = $this->getThrottleKey($email, $ip);
        
        RateLimiter::hit($key, self::LOCKOUT_DURATION);
        
        if (RateLimiter::attempts($key) >= self::MAX_ATTEMPTS) {
            $this->lockAccount($email);
            $this->notifySecurityTeam($email, $ip);
        }
    }
    
    public function isLocked(string $email, string $ip): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->getThrottleKey($email, $ip),
            self::MAX_ATTEMPTS
        );
    }
    
    private function getThrottleKey(string $email, string $ip): string
    {
        return "login_attempts:{$email}:{$ip}";
    }
}
```

## Authorization Security

### Role-Based Access Control
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
        return $user->can('view_any::Invoice');
    }
    
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('view::Invoice') 
            && $this->belongsToUserTeam($user, $invoice);
    }
    
    public function create(User $user): bool
    {
        return $user->can('create::Invoice');
    }
    
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('update::Invoice')
            && $this->belongsToUserTeam($user, $invoice)
            && $invoice->status->canBeModified();
    }
    
    private function belongsToUserTeam(User $user, Invoice $invoice): bool
    {
        return $invoice->tenant->team_id === $user->current_team_id;
    }
}
```

### API Authorization
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvoiceController extends Controller
{
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        // Authorize access
        $this->authorize('view', $invoice);
        
        // Verify team ownership
        if ($invoice->tenant->team_id !== $request->user()->current_team_id) {
            abort(403, 'Access denied');
        }
        
        return response()->json(new InvoiceResource($invoice));
    }
}
```

## Input Validation and Sanitization

### Form Request Validation
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }
    
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/', // Only letters and spaces
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'unique:users,email',
                'max:255',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }
    
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strip_tags($this->name),
            'email' => strtolower(trim($this->email)),
        ]);
    }
}
```

### SQL Injection Prevention
```php
// ✅ GOOD: Use Eloquent ORM or Query Builder
$users = User::where('email', $email)->get();
$invoices = DB::table('invoices')
    ->where('tenant_id', $tenantId)
    ->where('status', $status)
    ->get();

// ✅ GOOD: Use parameter binding for raw queries
$results = DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// ❌ NEVER: Direct string concatenation
$results = DB::select("SELECT * FROM users WHERE email = '{$email}'");
```

### XSS Prevention
```php
// Blade templates automatically escape output
{{ $user->name }} // Automatically escaped

// For raw HTML (use with caution)
{!! $trustedHtml !!} // Not escaped - only for trusted content

// Manual escaping
echo e($userInput); // Escape HTML entities
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

## File Upload Security

### Secure File Handling
```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Blaspsoft\Onym\Facades\Onym;

final readonly class SecureFileUploadService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'text/plain',
    ];
    
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    
    public function upload(UploadedFile $file, string $directory): string
    {
        $this->validateFile($file);
        
        // Generate secure filename
        $filename = Onym::make(
            defaultFilename: '',
            extension: $file->getClientOriginalExtension(),
            strategy: 'uuid'
        );
        
        // Scan for malware (if available)
        $this->scanForMalware($file);
        
        // Store file securely
        $path = Storage::disk('secure')->putFileAs(
            $directory,
            $file,
            $filename
        );
        
        return $path;
    }
    
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('File too large');
        }
        
        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new InvalidArgumentException('Invalid file type');
        }
        
        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new InvalidArgumentException('Invalid file extension');
        }
        
        // Additional security checks
        $this->performSecurityChecks($file);
    }
    
    private function performSecurityChecks(UploadedFile $file): void
    {
        // Check for embedded scripts in images
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $content = file_get_contents($file->getPathname());
            if (preg_match('/<script|javascript:|vbscript:/i', $content)) {
                throw new InvalidArgumentException('Malicious content detected');
            }
        }
        
        // Check for PHP code in uploaded files
        $content = file_get_contents($file->getPathname());
        if (preg_match('/<\?php|<\?=/i', $content)) {
            throw new InvalidArgumentException('PHP code not allowed');
        }
    }
}
```

## CSRF Protection

### Middleware Configuration
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class, // CSRF protection
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### CSRF Token Usage
```html
<!-- Blade forms -->
<form method="POST" action="/invoices">
    @csrf
    <!-- form fields -->
</form>

<!-- AJAX requests -->
<script>
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = 
    document.querySelector('meta[name="csrf-token"]').getAttribute('content');
</script>
```

## Data Encryption

### Database Encryption
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

final class SensitiveData extends Model
{
    protected $fillable = [
        'encrypted_field',
        'personal_data',
    ];
    
    protected function casts(): array
    {
        return [
            'encrypted_field' => 'encrypted',
            'personal_data' => 'encrypted:array',
        ];
    }
    
    // Custom encryption for sensitive fields
    protected function personalData(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => decrypt($value),
            set: fn ($value) => encrypt($value),
        );
    }
}
```

### File Encryption
```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

final readonly class EncryptedFileService
{
    public function store(string $content, string $path): void
    {
        $encrypted = Crypt::encrypt($content);
        Storage::disk('secure')->put($path, $encrypted);
    }
    
    public function retrieve(string $path): string
    {
        $encrypted = Storage::disk('secure')->get($path);
        return Crypt::decrypt($encrypted);
    }
}
```

## Security Headers

### Security Middleware
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // HTTPS enforcement
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );
        
        // Content Security Policy
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
        );
        
        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        return $response;
    }
}
```

## API Security

### Rate Limiting
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
});

// config/cache.php - Rate limiting configuration
'limiter' => [
    'api' => [
        'driver' => 'redis',
        'connection' => 'default',
    ],
],
```

### API Token Security
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Laravel\Sanctum\PersonalAccessToken;

final class TokenController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'array',
            'expires_at' => 'nullable|date|after:now',
        ]);
        
        $token = $request->user()->createToken(
            $request->name,
            $request->abilities ?? ['*'],
            $request->expires_at ? Carbon::parse($request->expires_at) : null
        );
        
        // Log token creation
        Log::info('API token created', [
            'user_id' => $request->user()->id,
            'token_name' => $request->name,
            'abilities' => $request->abilities,
            'ip' => $request->ip(),
        ]);
        
        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }
}
```

## Security Monitoring

### Security Event Logging
```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

final readonly class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        Log::warning('Failed login attempt', [
            'email' => $event->credentials['email'] ?? 'unknown',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
        
        // Check for suspicious activity
        $this->checkForSuspiciousActivity($event);
    }
    
    private function checkForSuspiciousActivity(Failed $event): void
    {
        $ip = request()->ip();
        $recentAttempts = Cache::get("failed_attempts:{$ip}", 0);
        
        if ($recentAttempts > 10) {
            // Alert security team
            Log::alert('Potential brute force attack', [
                'ip' => $ip,
                'attempts' => $recentAttempts,
                'email' => $event->credentials['email'] ?? 'unknown',
            ]);
        }
        
        Cache::put("failed_attempts:{$ip}", $recentAttempts + 1, 3600);
    }
}
```

### Intrusion Detection
```php
<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;

final readonly class IntrusionDetectionService
{
    private const SUSPICIOUS_PATTERNS = [
        '/\b(union|select|insert|update|delete|drop|create|alter)\b/i',
        '/<script[^>]*>.*?<\/script>/i',
        '/javascript:/i',
        '/vbscript:/i',
        '/onload|onerror|onclick/i',
    ];
    
    public function scanRequest(Request $request): void
    {
        $input = json_encode($request->all());
        
        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSuspiciousActivity($request, $pattern);
                
                // Block request if high risk
                if ($this->isHighRisk($pattern)) {
                    abort(403, 'Suspicious activity detected');
                }
            }
        }
    }
    
    private function logSuspiciousActivity(Request $request, string $pattern): void
    {
        Log::warning('Suspicious request detected', [
            'pattern' => $pattern,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'input' => $request->all(),
        ]);
    }
}
```

## Security Testing

### Security Test Examples
```php
// Test authentication
it('requires authentication for protected routes', function () {
    $response = $this->getJson('/api/invoices');
    $response->assertUnauthorized();
});

// Test authorization
it('prevents access to other team data', function () {
    $user = User::factory()->create();
    $otherTeamInvoice = Invoice::factory()->create();
    
    $response = $this->actingAs($user)
        ->getJson("/api/invoices/{$otherTeamInvoice->id}");
    
    $response->assertForbidden();
});

// Test input validation
it('validates malicious input', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/invoices', [
            'description' => '<script>alert("xss")</script>',
        ]);
    
    $response->assertUnprocessable();
});

// Test CSRF protection
it('requires CSRF token for web forms', function () {
    $response = $this->post('/invoices', [
        'description' => 'Test invoice',
    ]);
    
    $response->assertStatus(419); // CSRF token mismatch
});
```

## Security Checklist

### Development
- [ ] All inputs validated and sanitized
- [ ] SQL injection prevention (use ORM/Query Builder)
- [ ] XSS prevention (escape output)
- [ ] CSRF protection enabled
- [ ] Authentication required for protected routes
- [ ] Authorization checks implemented
- [ ] Secure file upload handling
- [ ] Password requirements enforced

### Configuration
- [ ] HTTPS enforced in production
- [ ] Security headers configured
- [ ] Session security settings
- [ ] Database encryption enabled
- [ ] API rate limiting configured
- [ ] MFA enabled for admin users
- [ ] Strong encryption keys
- [ ] Secure cookie settings

### Monitoring
- [ ] Security event logging
- [ ] Failed login monitoring
- [ ] Intrusion detection
- [ ] Regular security audits
- [ ] Vulnerability scanning
- [ ] Penetration testing
- [ ] Security incident response plan

## Related Documentation

- [Authentication System](../features/authentication/overview.md)
- [API Security](../api/security.md)
- [Deployment Security](../deployment/security.md)
- [Monitoring Setup](../monitoring/security.md)