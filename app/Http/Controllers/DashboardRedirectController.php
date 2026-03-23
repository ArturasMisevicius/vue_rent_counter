<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Support\Auth\LoginRedirector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request, LoginRedirector $loginRedirector): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return redirect()->to($loginRedirector->for($user));
    }
}
