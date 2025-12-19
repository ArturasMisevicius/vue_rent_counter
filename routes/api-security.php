<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SecurityAnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Security Analytics API Routes
|--------------------------------------------------------------------------
|
| These routes provide API endpoints for security analytics, violation
| tracking, and real-time monitoring. All routes require authentication
| and appropriate permissions.
|
*/

// Authenticated Security Analytics Routes
Route::middleware([
    'auth:sanctum', 
    'throttle:api', 
    'verified',
    \App\Http\Middleware\EnsureTenantContext::class
])->group(function () {
    
    // Security Analytics Endpoints
    Route::prefix('security')->name('security.')->group(function () {
        
        // Violations Management
        Route::get('/violations', [SecurityAnalyticsController::class, 'violations'])
            ->name('violations.index')
            ->middleware(['can:viewAny,App\Models\SecurityViolation', 'throttle:security-read,60,1']);
            
        Route::get('/violations/{violation}', [SecurityAnalyticsController::class, 'show'])
            ->name('violations.show')
            ->middleware(['can:view,violation', 'throttle:security-read,60,1']);
            
        Route::patch('/violations/{violation}/resolve', [SecurityAnalyticsController::class, 'resolve'])
            ->name('violations.resolve')
            ->middleware(['can:resolve,violation', 'throttle:security-write,30,1']);
            
        // Metrics and Analytics (Enhanced Security)
        Route::get('/metrics', [SecurityAnalyticsController::class, 'metrics'])
            ->name('metrics')
            ->middleware(['can:viewAny,App\Models\SecurityViolation', 'throttle:security-analytics,30,1']);
            
        Route::get('/dashboard', [SecurityAnalyticsController::class, 'dashboard'])
            ->name('dashboard')
            ->middleware(['can:viewAny,App\Models\SecurityViolation', 'throttle:security-analytics,30,1']);
            
        // Anomaly Detection (Restricted Access)
        Route::get('/anomalies', [SecurityAnalyticsController::class, 'anomalies'])
            ->name('anomalies')
            ->middleware(['can:viewAny,App\Models\SecurityViolation', 'throttle:security-analytics,10,1']);
            
        // Reports Generation (Admin Only)
        Route::post('/reports', [SecurityAnalyticsController::class, 'report'])
            ->name('reports.generate')
            ->middleware(['can:generateReports,App\Models\SecurityViolation', 'throttle:security-reports,5,1']);
            
        // Export Functionality (Admin Only)
        Route::post('/export', [SecurityAnalyticsController::class, 'export'])
            ->name('export')
            ->middleware(['can:export,App\Models\SecurityViolation', 'throttle:security-exports,2,1']);
    });
});

// CSP Violation Reporting (Public but Heavily Rate Limited)
Route::post('/csp-report', [SecurityAnalyticsController::class, 'reportViolation'])
    ->name('csp.report')
    ->middleware([
        'throttle:csp-reports,50,1', // Reduced from 1000 to 50 per minute
        \App\Http\Middleware\SecurityHeaders::class,
        'signed' // Require signed URLs for additional security
    ]);

// Internal CSP Violation Reporting (For authenticated users)
Route::post('/security/violations/report', [SecurityAnalyticsController::class, 'reportViolation'])
    ->name('security.violations.report')
    ->middleware([
        'auth:sanctum',
        'throttle:csp-reports-auth,200,1', // Higher limit for authenticated users
        \App\Http\Middleware\EnsureTenantContext::class
    ]);

// Real-time WebSocket routes (if using Laravel WebSockets)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/security/stream', function () {
        return response()->json([
            'websocket_url' => config('broadcasting.connections.pusher.options.host'),
            'channel' => 'security-analytics.' . tenant()?->id,
            'events' => [
                'violation-detected',
                'metrics-updated',
                'anomaly-detected',
            ],
        ]);
    })->name('security.stream.config');
});