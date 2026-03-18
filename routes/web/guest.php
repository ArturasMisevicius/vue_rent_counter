<?php

use App\Livewire\Auth\AcceptInvitationPage;
use App\Livewire\Auth\ForgotPasswordPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use Illuminate\Support\Facades\Route;

Route::livewire('/login', LoginPage::class)->name('login');
Route::post('/login', [LoginPage::class, 'store'])
    ->middleware('throttle:auth-login')
    ->name('login.store');

Route::livewire('/register', RegisterPage::class)->name('register');
Route::post('/register', [RegisterPage::class, 'store'])->name('register.store');

Route::livewire('/forgot-password', ForgotPasswordPage::class)->name('password.request');
Route::post('/forgot-password', [ForgotPasswordPage::class, 'sendResetLink'])
    ->middleware('throttle:password-reset-link')
    ->name('password.email');

Route::livewire('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');
Route::post('/reset-password', [ResetPasswordPage::class, 'resetPassword'])
    ->middleware('throttle:password-reset')
    ->name('password.update');

Route::livewire('/invite/{token}', AcceptInvitationPage::class)->name('invitation.show');
Route::post('/invite/{token}', [AcceptInvitationPage::class, 'store'])->name('invitation.store');
