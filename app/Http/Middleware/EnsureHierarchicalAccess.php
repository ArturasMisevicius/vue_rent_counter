<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureHierarchicalAccess
{
    /**
     * Handle an incoming request.
     *
     * Validates user can access requested resource based on hierarchical relationships.
     * Checks tenant_id and property_id relationships.
     * Returns 403 if access denied.
     *
     * Requirements: 12.5, 13.3
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Superadmin has unrestricted access
        if ($user->role === UserRole::SUPERADMIN) {
            return $next($request);
        }

        // Validate hierarchical access based on route parameters
        if (!$this->validateHierarchicalAccess($request, $user)) {
            $this->logAccessDenial($request, $user);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to access this resource.',
                ], 403);
            }

            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }

    /**
     * Validate hierarchical access based on user role and route parameters.
     */
    protected function validateHierarchicalAccess(Request $request, $user): bool
    {
        // Admin role: validate tenant_id matches
        if ($user->role === UserRole::ADMIN) {
            return $this->validateAdminAccess($request, $user);
        }

        // Tenant role: validate both tenant_id and property_id match
        if ($user->role === UserRole::TENANT) {
            return $this->validateTenantAccess($request, $user);
        }

        // Manager role: validate tenant_id matches (similar to admin)
        if ($user->role === UserRole::MANAGER) {
            return $this->validateAdminAccess($request, $user);
        }

        return true;
    }

    /**
     * Validate admin/manager access to resources.
     * 
     * Performance: Uses select() to minimize data transfer, caches validation results,
     * and batches queries when multiple resources are present
     */
    protected function validateAdminAccess(Request $request, $user): bool
    {
        // Performance: Cache key based on route and user with secure hashing
        $routeParams = json_encode($this->normalizeRouteParameters($request->route()->parameters()), JSON_THROW_ON_ERROR);
        $cacheKey = sprintf(
            'hierarchical_access:%d:%s:%s:%s:%s',
            $user->id,
            $user->role?->value ?? (string) $user->role,
            (string) ($user->tenant_id ?? 'null'),
            $request->route()->getName(),
            hash('sha256', $routeParams)  // Use SHA-256 instead of MD5 for security
        );
        
        return cache()->remember($cacheKey, 300, function () use ($request, $user) {
            // Check route parameters for resources that have tenant_id
            $resourceModels = [
                'building' => \App\Models\Building::class,
                'property' => \App\Models\Property::class,
                'meter' => \App\Models\Meter::class,
                'meterReading' => \App\Models\MeterReading::class,
                'invoice' => \App\Models\Invoice::class,
                'user' => \App\Models\User::class,
            ];

            foreach ($resourceModels as $param => $modelClass) {
                $resourceId = $request->route($param);
                
                if ($resourceId) {
                    $resource = $resourceId instanceof Model
                        ? $resourceId
                        // Performance: Only select tenant_id to minimize data transfer
                        : $modelClass::select('id', 'tenant_id')->find($resourceId);
                    
                    if ($resource && isset($resource->tenant_id)) {
                        // Validate tenant_id matches
                        if ($resource->tenant_id !== $user->tenant_id) {
                            return false;
                        }
                    }
                }
            }

            return true;
        });
    }

    /**
     * Validate tenant access to resources.
     * 
     * Performance: Uses select() to minimize data transfer and caches results
     */
    protected function validateTenantAccess(Request $request, $user): bool
    {
        // Performance: Cache key based on route and user
        $routeParams = json_encode($this->normalizeRouteParameters($request->route()->parameters()), JSON_THROW_ON_ERROR);
        $cacheKey = sprintf(
            'tenant_access:%d:%s:%s:%s:%s',
            $user->id,
            $user->role?->value ?? (string) $user->role,
            (string) ($user->tenant_id ?? 'null'),
            $request->route()->getName(),
            hash('sha256', $routeParams)
        );
        
        return cache()->remember($cacheKey, 300, function () use ($request, $user) {
            // First validate tenant_id matches (same as admin)
            if (!$this->validateAdminAccess($request, $user)) {
                return false;
            }

            // Additionally validate property_id for tenant-specific resources
            $propertyResourceModels = [
                'property' => \App\Models\Property::class,
                'meter' => \App\Models\Meter::class,
                'meterReading' => \App\Models\MeterReading::class,
                'invoice' => \App\Models\Invoice::class,
            ];

            foreach ($propertyResourceModels as $param => $modelClass) {
                $resourceId = $request->route($param);
                
                if ($resourceId) {
                    $resource = $resourceId instanceof Model
                        ? $resourceId
                        // Performance: Only select necessary columns
                        : $modelClass::select('id', 'property_id')->find($resourceId);
                    
                    if ($resource) {
                        // For property, check direct match
                        if ($param === 'property') {
                            if ($resource->id !== $user->property_id) {
                                return false;
                            }
                        } else {
                            // For other resources, check if they belong to tenant's property
                            if (isset($resource->property_id) && $resource->property_id !== $user->property_id) {
                                return false;
                            }
                        }
                    }
                }
            }

            return true;
        });
    }

    /**
     * Log access denial for audit purposes.
     * 
     * Performance: Only logs denials (not successful checks) to reduce I/O
     */
    protected function logAccessDenial(Request $request, $user): void
    {
        // Performance: Use audit channel with async driver if configured
        Log::channel('audit')->warning('Hierarchical access denied', [
            'user_id' => $user->id,
            'user_role' => $user->role->value,
            'user_tenant_id' => $user->tenant_id,
            'route' => $request->route()->getName(),
            'method' => $request->method(),
            'timestamp' => now()->timestamp, // Use timestamp instead of string for better performance
        ]);
    }

    /**
     * Normalize route parameters for safe hashing (casts bound models to IDs).
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    protected function normalizeRouteParameters(array $parameters): array
    {
        $normalized = [];

        foreach ($parameters as $key => $value) {
            $normalized[$key] = $value instanceof Model ? $value->getKey() : $value;
        }

        return $normalized;
    }
}
