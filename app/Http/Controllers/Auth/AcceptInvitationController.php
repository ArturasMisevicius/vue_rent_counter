<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AcceptOrganizationInvitationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\OrganizationInvitation;
use App\Support\Auth\LoginRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AcceptInvitationController extends Controller
{
    public function __construct(
        private readonly AcceptOrganizationInvitationAction $acceptInvitation,
        private readonly LoginRedirector $loginRedirector,
    ) {}

    public function show(string $token): View
    {
        $invitation = $this->findInvitation($token);

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'isExpired' => ! $invitation?->isPending(),
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = $this->findInvitation($token);

        if (! $invitation?->isPending()) {
            return redirect()->route('invitation.show', $token);
        }

        $user = $this->acceptInvitation->handle(
            $invitation,
            $request->validated(),
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended($this->loginRedirector->for($user));
    }

    private function findInvitation(string $token): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->with(['organization', 'inviter'])
            ->where('token', $token)
            ->first();
    }
}
