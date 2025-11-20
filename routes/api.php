<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MeterApiController;
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
    Route::post('/meter-readings', [MeterApiController::class, 'store']);
    
    // Property API endpoints
    Route::get('/properties', [ProviderApiController::class, 'properties']);
    Route::get('/properties/{property}', [ProviderApiController::class, 'propertyDetails']);
    
    // Provider API endpoints
    Route::get('/providers/{provider}/tariffs', [ProviderApiController::class, 'tariffs']);
});
