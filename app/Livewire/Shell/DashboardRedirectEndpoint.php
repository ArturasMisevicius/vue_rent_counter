<?php

declare(strict_types=1);

namespace App\Livewire\Shell;

use App\Filament\Support\Auth\LoginRedirector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Livewire\Component;

final class DashboardRedirectEndpoint extends Component
{
    public function show(
        Request $request,
        LoginRedirector $loginRedirector,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return redirect()->to($loginRedirector->for($user));
    }
}
