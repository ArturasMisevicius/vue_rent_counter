<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Livewire\Pages\Dashboard\TenantDashboard as TenantDashboardPage;
use Livewire\Attributes\On;

class TenantDashboard extends TenantDashboardPage
{
    #[On('reading.submitted')]
    public function refreshReadingsAndConsumption(): void
    {
        $this->refreshDashboardData();
    }
}
