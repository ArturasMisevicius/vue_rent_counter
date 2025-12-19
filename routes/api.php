<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MeterApiController;
use App\Http\Controllers\Api\MeterReadingApiController;
use App\Http\Controllers\Api\ProviderApiController;
use App\Http\Controllers\Api\ServiceValidationController;
use App\Http\Controllers\Enhanced\InvoiceController as EnhancedInvoiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    // Meter API endpoints
    Route::get('/meters/{meter}/last-reading', [MeterApiController::class, 'lastReading']);
    
    // Meter Reading API endpoints
    // Requirements: 1.1, 1.2, 1.3, 1.4, 1.5
    Route::post('/meter-readings', [MeterReadingApiController::class, 'store']);
    Route::get('/meter-readings/{meterReading}', [MeterReadingApiController::class, 'show']);
    Route::put('/meter-readings/{meterReading}', [MeterReadingApiController::class, 'update']);
    Route::patch('/meter-readings/{meterReading}', [MeterReadingApiController::class, 'update']);
    
    // Property API endpoints
    Route::get('/properties', [ProviderApiController::class, 'properties']);
    Route::get('/properties/{property}', [ProviderApiController::class, 'propertyDetails']);
    
    // Provider API endpoints
    Route::get('/providers/{provider}/tariffs', [ProviderApiController::class, 'tariffs']);
    
    // Service Validation API endpoints
    Route::prefix('validation')->group(function () {
        Route::post('/readings/{reading}/validate', [ServiceValidationController::class, 'validateReading']);
        Route::post('/readings/batch-validate', [ServiceValidationController::class, 'batchValidateReadings']);
        Route::post('/service-configurations/{serviceConfiguration}/validate-rate-change', [ServiceValidationController::class, 'validateRateChange']);
        Route::post('/service-configurations/{serviceConfiguration}/rate-changes', [ServiceValidationController::class, 'validateRateChange']);
        Route::get('/readings', [ServiceValidationController::class, 'getReadingsByStatus']);
        Route::get('/readings/by-status', [ServiceValidationController::class, 'getReadingsByStatus']);
        Route::patch('/readings/bulk-update-status', [ServiceValidationController::class, 'bulkUpdateValidationStatus']);
        Route::post('/readings/{reading}/validate-estimated', [ServiceValidationController::class, 'validateEstimatedReading']);
        Route::get('/health', [ServiceValidationController::class, 'healthCheck']);
    });

    // Invoice API endpoints
    Route::prefix('invoices')->name('api.invoices.')->group(function () {
        Route::get('/{tenant}/billing-history', [EnhancedInvoiceController::class, 'billingHistory'])
            ->name('billing-history');
        Route::get('/{invoice}/consumption-data', [EnhancedInvoiceController::class, 'consumptionData'])
            ->name('consumption-data');
    });
});

// API Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me']);
        Route::post('/refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh']);
    });
});

// API v1 (Sanctum) routes
Route::prefix('v1/validation')->group(function () {
    require __DIR__ . '/api_v1_validation.php';
});
