<?php

declare(strict_types=1);

use App\Services\RoleDashboardResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/', function (RoleDashboardResolver $dashboardResolver) {
        $user = auth()->user();

        if ($user !== null) {
            return $dashboardResolver->redirectToDashboard($user);
        }

        return redirect()->route('filament.admin.auth.login');
    })->name('home');

    Route::get('/login', function () {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('filament.admin.auth.login');
    })->name('login');

    Route::get('/register', function () {
        return redirect()->route('filament.admin.auth.login');
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

        Route::prefix('admin')
            ->middleware('role:admin')
            ->name('admin.')
            ->group(function (): void {
                Route::get('/dashboard', fn () => redirect()->route('filament.admin.pages.dashboard'))
                    ->name('dashboard');
            });

        Route::prefix('manager')
            ->middleware('role:manager')
            ->name('manager.')
            ->group(function (): void {
                Route::get('/dashboard', fn () => redirect()->route('filament.admin.pages.dashboard'))
                    ->name('dashboard');
            });

        Route::prefix('tenant')
            ->middleware('role:tenant')
            ->name('tenant.')
            ->group(function (): void {
                Route::get('/dashboard', fn () => redirect()->route('filament.tenant.pages.dashboard'))
                    ->name('dashboard');
            });

        Route::prefix('superadmin')
            ->middleware('superadmin')
            ->name('superadmin.')
            ->group(function (): void {
                Route::get('/dashboard', fn () => redirect()->route('filament.superadmin.pages.dashboard'))
                    ->name('dashboard');
            });
    });
});
