# Authentication System Documentation

## Overview

CFlow implements a comprehensive authentication system with multi-factor authentication (MFA), role-based access control (RBAC), and team-based multi-tenancy.

## Authentication Flow

### User Registration
1. User provides email, password, and basic information
2. Email verification is sent (optional)
3. User is assigned to default team or creates new team
4. Default role is assigned based on team settings

### Login Process
1. Email/password authentication
2. MFA challenge (if enabled)
3. Team selection (if user belongs to multiple teams)
4. Session establishment with proper scoping

### Password Reset
1. User requests password reset via email
2. Secure token is generated and sent
3. User sets new password
4. All existing sessions are invalidated

## Multi-Factor Authentication (MFA)

### Setup
MFA is enabled in Filament panel configuration:

```php
// In PanelProvider
public function panel(Panel $panel): Panel
{
    return $panel
        ->mfa(
            requireMfa: config('auth.require_mfa', false),
            enforceMfa: fn () => auth()->user()->is_admin,
        );
}
```

### Supported Methods
- **TOTP (Time-based One-Time Password)** - Google Authenticator, Authy
- **SMS** - Text message codes (optional)
- **Email** - Email-based codes (fallback)

### Recovery Codes
- Generated during MFA setup
- Single-use codes for account recovery
- Stored encrypted in database

## Role-Based Access Control (RBAC)

### Filament Shield Integration
CFlow uses Filament Shield for comprehensive RBAC:

```php
// Check permissions
auth()->user()->can('view_any::Invoice');
auth()->user()->can('create::User');

// Assign roles (team-scoped)
$user->assignRole('admin', $team);
$user->assignRole('manager', $team);
```

### Permission Structure
Permissions follow the pattern: `{action}::{Resource}`

**Standard Actions**:
- `view_any` - List/index pages
- `view` - View individual records
- `create` - Create new records
- `update` - Edit existing records
- `delete` - Delete records
- `restore` - Restore soft-deleted records
- `force_delete` - Permanently delete records

**Custom Permissions**:
```php
// config/filament-shield.php
'custom_permissions' => [
    'export_reports',
    'import_data',
    'manage_integrations',
    'view_analytics',
],
```

### Default Roles

#### Super Admin
- Full system access
- Can manage all teams
- Bypasses all permission checks
- Cannot be deleted or modified

#### Team Admin
- Full access within team
- Can manage team members
- Can assign roles within team
- Can modify team settings

#### Manager
- Can view and edit most resources
- Cannot manage users or settings
- Limited to assigned areas

#### User
- Basic access to assigned features
- Cannot modify system settings
- Read-only access to most data

#### Viewer
- Read-only access
- Cannot create or modify data
- Can export reports (if permitted)

## Multi-Tenancy

### Team-Based Tenancy
CFlow uses team-based multi-tenancy where:
- Users belong to one or more teams
- Data is automatically scoped to current team
- Permissions are team-specific
- Billing is per-team

### Automatic Scoping
Filament v4 automatically scopes all queries:

```php
// Automatically scoped to current team
$invoices = Invoice::all();

// Manual scoping (when needed)
$invoices = Invoice::where('team_id', auth()->user()->current_team_id)->get();
```

### Team Switching
Users can switch between teams:

```php
// Switch team
auth()->user()->switchTeam($team);

// Get current team
$currentTeam = auth()->user()->currentTeam;

// Check team membership
$user->belongsToTeam($team);
```

## Session Management

### Session Configuration
```php
// config/session.php
'lifetime' => env('SESSION_LIFETIME', 120), // 2 hours
'expire_on_close' => true,
'encrypt' => true,
'files' => storage_path('framework/sessions'),
'connection' => env('SESSION_CONNECTION'),
'table' => 'sessions',
'store' => env('SESSION_STORE'),
'lottery' => [2, 100],
'cookie' => env('SESSION_COOKIE', 'cflow_session'),
'path' => '/',
'domain' => env('SESSION_DOMAIN'),
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'lax',
```

### Session Security
- Sessions are encrypted
- CSRF protection on all forms
- Session regeneration on login
- Automatic logout on inactivity

## API Authentication

### Sanctum Integration
API authentication uses Laravel Sanctum:

```php
// Generate API token
$token = $user->createToken('api-token')->plainTextToken;

// Authenticate API requests
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
});
```

### Token Scopes
```php
// Create token with specific abilities
$token = $user->createToken('invoice-token', ['invoice:read', 'invoice:write']);

// Check token abilities
if ($user->tokenCan('invoice:write')) {
    // Allow write operations
}
```

## Security Features

### Password Requirements
```php
// config/auth.php
'password_requirements' => [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => false,
    'prevent_common' => true,
],
```

### Account Lockout
```php
// config/auth.php
'lockout' => [
    'max_attempts' => 5,
    'decay_minutes' => 15,
    'lockout_duration' => 30, // minutes
],
```

### Security Headers
```php
// Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    
    return $response;
}
```

## User Management

### User Model
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'current_team_id',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasRole('super_admin'),
            'user' => true,
            default => false,
        };
    }
    
    // Team relationships
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }
    
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }
}
```

### User Registration Action
```php
<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\RegisterUserData;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

final readonly class RegisterUserAction
{
    public function execute(RegisterUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create user
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);
            
            // Create or assign team
            if ($data->teamId) {
                $team = Team::findOrFail($data->teamId);
                $user->teams()->attach($team);
            } else {
                $team = Team::create([
                    'name' => $data->teamName ?? "{$data->name}'s Team",
                    'owner_id' => $user->id,
                ]);
                $user->teams()->attach($team, ['role' => 'admin']);
            }
            
            // Set current team
            $user->update(['current_team_id' => $team->id]);
            
            // Assign default role
            $user->assignRole('user', $team);
            
            return $user;
        });
    }
}
```

## Testing Authentication

### Feature Tests
```php
it('allows user to register', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    
    $response->assertCreated();
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

it('requires MFA for admin users', function () {
    $admin = User::factory()->admin()->create();
    
    $response = $this->actingAs($admin)
        ->get('/admin');
    
    $response->assertRedirect('/admin/mfa/challenge');
});
```

### Unit Tests
```php
it('hashes password on creation', function () {
    $user = User::factory()->create(['password' => 'password123']);
    
    expect($user->password)->not->toBe('password123');
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

it('can check team membership', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    
    $user->teams()->attach($team);
    
    expect($user->belongsToTeam($team))->toBeTrue();
});
```

## Configuration

### Authentication Guards
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

### Password Brokers
```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
    ],
],
```

## Monitoring and Logging

### Authentication Events
```php
// Log authentication events
Event::listen([
    'Illuminate\Auth\Events\Login',
    'Illuminate\Auth\Events\Logout',
    'Illuminate\Auth\Events\Failed',
], function ($event) {
    Log::info('Authentication event', [
        'event' => class_basename($event),
        'user_id' => $event->user->id ?? null,
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ]);
});
```

### Failed Login Attempts
```php
// Monitor failed login attempts
Event::listen('Illuminate\Auth\Events\Failed', function ($event) {
    if ($this->hasTooManyFailedAttempts($event->credentials['email'])) {
        // Send security alert
        // Lock account temporarily
        // Log security incident
    }
});
```

## Troubleshooting

### Common Issues

**MFA not working**
- Check time synchronization
- Verify secret key generation
- Test with multiple authenticator apps

**Permission denied errors**
- Clear permission cache: `php artisan permission:cache-reset`
- Verify role assignments
- Check team scoping

**Session issues**
- Clear session storage
- Check session configuration
- Verify CSRF tokens

## Related Documentation

- [Filament Shield](../../filament/shield.md)
- [Multi-Tenancy Setup](../tenancy/overview.md)
- [Security Guidelines](../../security/overview.md)
- [API Authentication](../../api/authentication.md)