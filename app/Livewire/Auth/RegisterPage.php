<?php

namespace App\Livewire\Auth;

use App\Filament\Actions\Auth\RegisterAdminAction;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RegisterPage extends Component
{
    public function store(
        RegisterRequest $request,
        RegisterAdminAction $registerAdminAction,
    ): RedirectResponse {
        $user = $registerAdminAction->handle($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('welcome.show');
    }

    public function render(): View
    {
        return view('auth.register')
            ->extends('layouts.guest');
    }
}
