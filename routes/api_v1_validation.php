<?php

use App\Http\Controllers\Api\V1\ServiceValidationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Service Validation API Routes
|--------------------------------------------------------------------------
|
| API routes for the Universal Utility Management System validation engine.
| All routes are prefixed with /api/v1/validation and require authentication.
|
*/

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    
    // Health and monitoring endpoints
    Route::get('/health', [ServiceValidationController::class, 'healthCheck'])
        ->name('validation.health');
    
    Route::get('/metrics', [ServiceValidationController::class, 'getMetrics'])
        ->name('validation.metrics');
    
    // Single reading validation
    Route::post('/meter-reading/{reading}', [ServiceValidationController::class, 'validateMeterReading'])
        ->name('validation.meter-reading');
    
    // Batch reading validation
    Route::post('/batch/meter-readings', [ServiceValidationController::class, 'batchValidateReadings'])
        ->name('validation.batch.meter-readings');
    
    // Rate change validation
    Route::post('/rate-change/{serviceConfiguration}', [ServiceValidationController::class, 'validateRateChange'])
        ->name('validation.rate-change');
    
    // Validation rules retrieval
    Route::get('/rules/{serviceConfiguration}', [ServiceValidationController::class, 'getValidationRules'])
        ->name('validation.rules');
    
    // Estimated reading validation
    Route::post('/estimated-reading/{estimatedReading}', [ServiceValidationController::class, 'validateEstimatedReading'])
        ->name('validation.estimated-reading');
});