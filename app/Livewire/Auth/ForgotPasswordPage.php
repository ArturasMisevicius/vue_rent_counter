<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class ForgotPasswordPage extends Component
{
    public function render(): View
    {
        return view('auth.forgot-password');
    }
}
