<?php

namespace App\View\Components\Shell;

use App\Models\User;
use App\Support\Shell\DashboardUrlResolver;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppFrame extends Component
{
    public function __construct(
        public ?string $title = null,
        public bool $showTenantNavigation = false,
        public array $breadcrumbs = [],
    ) {}

    public function render(): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        return view('components.shell.app-frame', [
            'dashboardUrl' => app(DashboardUrlResolver::class)->for($user),
            'breadcrumbs' => $this->breadcrumbs,
            'user' => $user,
        ]);
    }
}
