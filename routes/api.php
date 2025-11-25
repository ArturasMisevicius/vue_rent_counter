<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MeterApiController;
use App\Http\Controllers\Api\MeterReadingApiController;
use App\Http\Controllers\Api\ProviderApiController;

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
});
