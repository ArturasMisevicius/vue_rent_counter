<?php

namespace App\Livewire\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ForgotPasswordPage extends Component
{
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->validated());

        return back()->with('status', __('auth.reset_link_generic'));
    }

    public function render(): View
    {
        return view('auth.forgot-password')
            ->extends('layouts.guest');
    }
}
