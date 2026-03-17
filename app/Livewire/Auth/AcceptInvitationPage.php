<?php

namespace App\Livewire\Auth;

use App\Filament\Actions\Auth\AcceptOrganizationInvitationAction;
use App\Filament\Actions\Preferences\ResolveGuestLocaleAction;
use App\Filament\Support\Auth\AuthenticatedSessionHistory;
use App\Filament\Support\Auth\LoginRedirector;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\OrganizationInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AcceptInvitationPage extends Component
{
    #[Locked]
    public string $token = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function store(
        AcceptInvitationRequest $request,
        string $token,
        AcceptOrganizationInvitationAction $acceptInvitation,
        ResolveGuestLocaleAction $resolveGuestLocaleAction,
        LoginRedirector $loginRedirector,
        AuthenticatedSessionHistory $authenticatedSessionHistory,
    ): RedirectResponse {
        $invitation = OrganizationInvitation::query()
            ->with(['organization', 'inviter'])
            ->forToken($token)
            ->first();

        if (! $invitation?->isPending()) {
            return redirect()->route('invitation.show', $token);
        }

        $locale = $resolveGuestLocaleAction->handle($request);

        $user = $acceptInvitation->handle(
            $invitation,
            $request->validated(),
            $locale,
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->intended($loginRedirector->for($user))
            ->withCookie($authenticatedSessionHistory->remember());
    }

    public function render(): View
    {
        $invitation = $this->invitation;

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'isExpired' => ! $invitation?->isPending(),
        ]);
    }

    #[Computed]
    public function invitation(): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->forAcceptancePortal()
            ->forToken($this->token)
            ->first();
    }
}
