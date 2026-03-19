<?php

use App\Filament\Actions\Preferences\ResolveGuestLocaleRedirectAction;
use App\Filament\Actions\Preferences\StoreGuestLocaleAction;
use App\Filament\Support\Auth\LoginRedirector;
use App\Filament\Support\Preferences\SupportedLocaleOptions;
use App\Http\Controllers\NotificationTrackingController;
use App\Livewire\Auth\AcceptInvitationPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Preferences\UpdateGuestLocaleEndpoint;
use App\Livewire\Shell\LogoutEndpoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): RedirectResponse => redirect()->route('dashboard'))
    ->name('home');

Route::get('/dashboard', function (LoginRedirector $loginRedirector): RedirectResponse {
    return redirect()->to($loginRedirector->for(Auth::user()));
})->middleware('auth')->name('dashboard');

Route::get('/login', LoginPage::class)->middleware('guest')->name('login');
Route::post('/login', [LoginPage::class, 'store'])
    ->middleware(['guest', 'throttle:auth-login'])
    ->name('login.store');

Route::post('/logout', [LogoutEndpoint::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/register', RegisterPage::class)->middleware('guest')->name('register');
Route::post('/register', [RegisterPage::class, 'store'])
    ->middleware('guest')
    ->name('register.store');

Route::get('/language/{locale}', function (
    string $locale,
    Request $request,
    StoreGuestLocaleAction $storeGuestLocaleAction,
    ResolveGuestLocaleRedirectAction $resolveGuestLocaleRedirectAction,
    SupportedLocaleOptions $supportedLocaleOptions,
): RedirectResponse {
    if (! in_array($locale, $supportedLocaleOptions->codes(), true)) {
        abort(404);
    }

    $storeGuestLocaleAction->handle($request, $locale);

    return redirect()->to($resolveGuestLocaleRedirectAction->handle($request));
})->name('language.switch');

Route::post('/locale', [UpdateGuestLocaleEndpoint::class, 'update'])->name('locale.update');

Route::post('/notification-track/{platformNotificationRecipient}', NotificationTrackingController::class)
    ->name('notifications.track');

Route::get('/invitations/{token}/accept', AcceptInvitationPage::class)->name('invitation.show');
Route::post('/invitations/{token}/accept', [AcceptInvitationPage::class, 'store'])->name('invitation.store');
