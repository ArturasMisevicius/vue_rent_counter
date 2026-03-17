<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Livewire\Component;

class ResetPasswordPage extends Component
{
    public string $token = '';

    public string $email = '';

    public function mount(Request $request, string $token): void
    {
        $this->token = $token;
        $this->email = (string) $request->string('email');
    }

    public function render(): View
    {
        return view('auth.reset-password', [
            'token' => $this->token,
            'email' => $this->email,
        ]);
    }
}
