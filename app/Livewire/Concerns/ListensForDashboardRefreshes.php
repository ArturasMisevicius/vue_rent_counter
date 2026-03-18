<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\On;

trait ListensForDashboardRefreshes
{
    #[On('invoice.finalized')]
    #[On('reading.submitted')]
    #[On('echo-private:org.{organizationId},.invoice.finalized')]
    #[On('echo-private:org.{organizationId},.reading.submitted')]
    public function refreshDashboardData(): void
    {
        if (method_exists($this, 'dashboard')) {
            unset($this->dashboard);
        }

        if (method_exists($this, 'summary')) {
            unset($this->summary);
        }
    }
}
