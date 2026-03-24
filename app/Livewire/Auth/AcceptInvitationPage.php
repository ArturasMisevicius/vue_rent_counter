<?php

namespace App\Livewire\Auth;

use App\Filament\Actions\Auth\AcceptOrganizationInvitationAction;
use App\Filament\Actions\Preferences\ResolveGuestLocaleAction;
use App\Filament\Support\Auth\AuthenticatedSessionHistory;
use App\Filament\Support\Auth\LoginRedirector;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Models\OrganizationInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class AcceptInvitationPage extends Component
{
    use AppliesShellLocale;

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
        $invitation = $this->findInvitation($token);

        if ($invitation?->isAccepted()) {
            return redirect()
                ->route('invitation.show', $token)
                ->with('status', __('auth.invitation_used'));
        }

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
        $statusMessage = match (true) {
            $invitation === null => __('auth.invitation_expired'),
            $invitation->isAccepted() => session('status', __('auth.invitation_used')),
            $invitation->isExpired() => __('auth.invitation_expired'),
            default => null,
        };

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'statusMessage' => $statusMessage,
            'token' => $this->token,
        ])->extends('layouts.guest');
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();
    }

    #[Computed]
    public function invitation(): ?OrganizationInvitation
    {
        return $this->findInvitation($this->token);
    }

    private function findInvitation(?string $token): ?OrganizationInvitation
    {
        $normalizedToken = $this->normalizeInvitationToken((string) $token);

        if (! $this->isPlausibleInvitationToken($normalizedToken)) {
            return null;
        }

        return OrganizationInvitation::query()
            ->forAcceptancePortal()
            ->forToken($normalizedToken)
            ->first();
    }

    private function normalizeInvitationToken(string $token): string
    {
        return trim($token);
    }

    private function isPlausibleInvitationToken(string $token): bool
    {
        return strlen($token) === 64 && ctype_alnum($token);
    }
}
