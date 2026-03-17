<?php

namespace App\Livewire\Auth;

use App\Filament\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        return back()
            ->withInput($request->safe()->except([
                'password',
                'password_confirmation',
            ]))
            ->withErrors([
                'email' => __($status),
            ]);
    }

    public function render(): View
    {
        return view('auth.reset-password', [
            'token' => $this->token,
            'email' => $this->email,
        ]);
    }
}
