<?php

namespace App\Http\Controllers\Onboarding;

use App\Actions\Auth\CompleteOnboardingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteOnboardingRequest;
use App\Support\Auth\LoginRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function __construct(
        private readonly CompleteOnboardingAction $completeOnboarding,
        private readonly LoginRedirector $loginRedirector,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfUnavailable($request)) {
            return $redirect;
        }

        return view('onboarding.welcome');
    }

    public function store(CompleteOnboardingRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectIfUnavailable($request)) {
            return $redirect;
        }

        $this->completeOnboarding->handle(
            $request->user(),
            $request->validated(),
        );

        return redirect()->route('filament.admin.pages.organization-dashboard');
    }

    private function redirectIfUnavailable(Request $request): ?RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || filled($user->organization_id)) {
            return redirect()->to($this->loginRedirector->for($user));
        }

        return null;
    }
}
