<?php

namespace App\Livewire\Shell;

use App\Models\User;
use App\Support\Auth\ImpersonationManager;
use App\Support\Shell\DashboardUrlResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\On;
use Livewire\Component;

class Topbar extends Component
{
    public string $context = 'panel';

    public ?string $eyebrow = null;

    public ?string $heading = null;

    public function mount(string $context = 'panel', ?string $eyebrow = null, ?string $heading = null): void
    {
        $this->context = $context;
        $this->eyebrow = $eyebrow;
        $this->heading = $heading;
    }

    #[On('shell-locale-updated')]
    public function refresh(): void {}

    public function render(
        DashboardUrlResolver $dashboardUrlResolver,
        ImpersonationManager $impersonationManager,
    ): View {
        $user = auth()->user();

        return view('livewire.shell.topbar', [
            'dashboardUrl' => $dashboardUrlResolver->for($user),
            'impersonation' => $impersonationManager->current(request()),
            'profileUrl' => $this->resolveProfileUrl($user),
            'roleLabel' => $user?->role?->label(),
            'user' => $user,
        ]);
    }

    protected function resolveProfileUrl(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if ($user->isTenant() && Route::has('tenant.profile.edit')) {
            return route('tenant.profile.edit');
        }

        if (Route::has('profile.edit')) {
            return route('profile.edit');
        }

        return null;
    }
}
