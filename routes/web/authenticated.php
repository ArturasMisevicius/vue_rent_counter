<?php

use App\Livewire\Onboarding\WelcomePage;
use App\Livewire\Profile\EditProfilePage;
use App\Livewire\Shell\StopImpersonationEndpoint;
use Illuminate\Support\Facades\Route;

Route::livewire('/welcome', WelcomePage::class)->name('welcome.show');
Route::post('/welcome', [WelcomePage::class, 'store'])->name('welcome.store');
Route::post('/impersonation/stop', [StopImpersonationEndpoint::class, 'stop'])->name('impersonation.stop');
Route::livewire('/profile', EditProfilePage::class)->name('profile.edit');

Route::prefix('tenant')
    ->name('tenant.')
    ->middleware('tenant.only')
    ->group(base_path('routes/web/tenant.php'));
