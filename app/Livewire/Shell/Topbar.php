<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\DashboardUrlResolver;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class Topbar extends Component
{
    #[Locked]
    public string $context = 'panel';

    #[Locked]
    public ?string $eyebrow = null;

    #[Locked]
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
    ): View {
        $user = auth()->user();

        return view('livewire.shell.topbar', [
            'dashboardUrl' => $dashboardUrlResolver->for($user),
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

        if (Route::has('filament.admin.pages.profile') && $user->canAccessPanel(filament()->getCurrentOrDefaultPanel())) {
            return route('filament.admin.pages.profile');
        }

        if (Route::has('profile.edit')) {
            return route('profile.edit');
        }

        return null;
    }
}
