<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Enums\UserRole;
use App\Filament\Support\Admin\Dashboard\AdminDashboardStats;
use App\Filament\Support\Superadmin\Dashboard\PlatformDashboardData;
use App\Filament\Support\Tenant\Portal\TenantHomePresenter;
use App\Livewire\Pages\Dashboard\AdminDashboard;
use App\Livewire\Pages\Dashboard\SuperadminDashboard;
use App\Livewire\Pages\Dashboard\TenantDashboard;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class DashboardPage extends Component
{
    public function mount(): void
    {
        $this->user();
    }

    public function render(): View
    {
        return view('livewire.pages.dashboard-page');
    }

    #[Computed]
    public function dashboardData(): array
    {
        return match ($this->user()->role) {
            UserRole::SUPERADMIN => $this->buildSuperadminData(),
            UserRole::ADMIN, UserRole::MANAGER => $this->buildAdminData(),
            UserRole::TENANT => $this->buildTenantData(),
            default => ['role' => 'default'],
        };
    }

    #[Computed]
    public function buildSuperadminData(): array
    {
        return [
            'role' => UserRole::SUPERADMIN->value,
            'data' => app(PlatformDashboardData::class)->for($this->user()),
        ];
    }

    #[Computed]
    public function buildAdminData(): array
    {
        return [
            'role' => $this->user()->role->value,
            'data' => app(AdminDashboardStats::class)->dashboardFor($this->user(), 10, 10),
        ];
    }

    #[Computed]
    public function buildTenantData(): array
    {
        return [
            'role' => UserRole::TENANT->value,
            'data' => app(TenantHomePresenter::class)->for($this->user()),
        ];
    }

    public function getRoleDashboardComponent(): string
    {
        return match ($this->user()->role) {
            UserRole::SUPERADMIN => SuperadminDashboard::class,
            UserRole::ADMIN, UserRole::MANAGER => AdminDashboard::class,
            UserRole::TENANT => TenantDashboard::class,
            default => '',
        };
    }

    public function getRoleDashboardData(): array
    {
        return match ($this->user()->role) {
            UserRole::SUPERADMIN => $this->dashboardData()['data'],
            UserRole::ADMIN, UserRole::MANAGER => $this->dashboardData()['data'],
            UserRole::TENANT => $this->dashboardData()['data'],
            default => [],
        };
    }

    public function shouldRenderDashboard(): bool
    {
        return in_array($this->user()->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ], true);
    }

    protected function user(): User
    {
        $user = auth()->user();

        if ($user instanceof User) {
            return $user;
        }

        abort(403);
    }
}

