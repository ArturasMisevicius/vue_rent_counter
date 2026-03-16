<?php

declare(strict_types=1);

use App\Filament\Auth\Pages\RoleAwareLogin;
use App\Services\RoleDashboardResolver;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Middleware\SetUpPanel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/', function (RoleDashboardResolver $dashboardResolver) {
        $user = auth()->user();

        if ($user !== null) {
            return $dashboardResolver->redirectToDashboard($user);
        }

        return redirect()->route('login');
    })->name('home');

    Route::get('/login', RoleAwareLogin::class)
        ->middleware([
            SetUpPanel::class.':admin',
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ])
        ->name('login');

    Route::get('/register', function () {
        return redirect()->route('login');
    })->name('register');

    Route::post('/logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function (): void {
        Route::get('/dashboard', function (RoleDashboardResolver $dashboardResolver) {
            return $dashboardResolver->redirectToDashboard(auth()->user());
        })->name('dashboard');
    });
});
