<?php

namespace App\Livewire\Auth;

use App\Models\OrganizationInvitation;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AcceptInvitationPage extends Component
{
    public string $token = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function render(): View
    {
        $invitation = $this->findInvitation($this->token);

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'isExpired' => ! $invitation?->isPending(),
        ]);
    }

    protected function findInvitation(string $token): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->forAcceptancePortal()
            ->where('token', $token)
            ->first();
    }
}
