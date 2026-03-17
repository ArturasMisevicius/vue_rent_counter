<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Auth\AuthenticatedSessionHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LogoutEndpoint extends Component
{
    public function logout(
        Request $request,
        AuthenticatedSessionHistory $authenticatedSessionHistory,
    ): RedirectResponse {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withCookie($authenticatedSessionHistory->forget());
    }
}
