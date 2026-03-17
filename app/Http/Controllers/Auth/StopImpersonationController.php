<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\ImpersonationManager;
use App\Support\Auth\LoginRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StopImpersonationController extends Controller
{
    public function __invoke(
        Request $request,
        ImpersonationManager $impersonationManager,
        LoginRedirector $loginRedirector,
    ): RedirectResponse {
        $impersonator = $impersonationManager->stop();

        abort_if($impersonator === null, 404);

        Auth::guard('web')->login($impersonator);
        $request->session()->regenerate();

        return redirect()->to($loginRedirector->for($impersonator));
    }
}
