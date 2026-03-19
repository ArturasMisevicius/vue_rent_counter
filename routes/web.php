<?php

use App\Http\Controllers\CspViolationReportController;
use App\Livewire\Preferences\UpdateGuestLocaleEndpoint;
use App\Livewire\PublicSite\HomepagePage;
use App\Livewire\PublicSite\ShowFaviconEndpoint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::livewire('/', HomepagePage::class)->name('home');
Route::get('/favicon', [ShowFaviconEndpoint::class, 'show'])->name('favicon');
Route::post('/locale', [UpdateGuestLocaleEndpoint::class, 'update'])->name('locale.update');
Route::post('/csp/report', CspViolationReportController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->middleware('throttle:security-csp-report')
    ->name('security.csp.report');

Route::middleware('guest')->group(base_path('routes/web/guest.php'));
Route::middleware(['auth', 'set.auth.locale', 'ensure.account.accessible'])
    ->group(base_path('routes/web/authenticated.php'));
Route::middleware('auth')->group(base_path('routes/web/logout.php'));
