<?php

namespace App\Livewire\Auth;

use App\Support\Auth\LoginDemoAccountPresenter;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LoginPage extends Component
{
    public function render(LoginDemoAccountPresenter $loginDemoAccountPresenter): View
    {
        return view('auth.login', [
            'demoAccounts' => $loginDemoAccountPresenter->accounts() ?: config('tenanto.demo_accounts', []),
        ]);
    }
}
