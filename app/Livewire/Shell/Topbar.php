<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\DashboardUrlResolver;
use App\Filament\Support\Shell\Navigation\NavigationBuilder;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class Topbar extends Component
{
    use AppliesShellLocale;

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
    #[On('profile-avatar-updated')]
    public function refresh(): void
    {
        $this->applyShellLocale();
    }

    public function render(
        DashboardUrlResolver $dashboardUrlResolver,
        NavigationBuilder $navigationBuilder,
    ): View {
        $user = auth()->user();

        return view('livewire.shell.topbar', [
            'dashboardUrl' => $dashboardUrlResolver->for($user),
            'navigationGroups' => $this->navigationGroupsFor($user, $navigationBuilder),
            'profileUrl' => $this->resolveProfileUrl($user),
            'roleLabel' => $user?->role?->label(),
            'showLanguageSwitcher' => ! $user?->isTenant(),
            'user' => $user,
        ]);
    }

    protected function navigationGroupsFor(?User $user, NavigationBuilder $navigationBuilder): array
    {
        if ($user === null || ! $user->isTenant()) {
            return [];
        }

        return $navigationBuilder->forUser($user, request());
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
