<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\On;

trait ListensForDashboardRefreshes
{
    use AppliesShellLocale;
    use SupportsEchoListeners;

    /**
     * @return array<string, string>
     */
    protected function dashboardRefreshListeners(): array
    {
        $listeners = [];

        $organizationId = property_exists($this, 'organizationId')
            ? (int) ($this->organizationId ?? 0)
            : 0;

        if ($this->shouldUseEchoListeners() && $organizationId > 0) {
            $listeners['echo-private:org.'.$organizationId.',.invoice.finalized'] = 'refreshDashboardData';
            $listeners['echo-private:org.'.$organizationId.',.reading.submitted'] = 'refreshDashboardData';
        }

        return $listeners;
    }

    #[On('invoice.finalized')]
    #[On('reading.submitted')]
    #[On('shell-locale-updated')]
    public function refreshDashboardData(): void
    {
        $this->applyShellLocale();

        if (property_exists($this, 'dashboardData')) {
            $this->dashboardData = [];
        }

        if (property_exists($this, 'summaryData')) {
            $this->summaryData = [];
        }

        if (method_exists($this, 'dashboard')) {
            unset($this->dashboard);
        }

        if (method_exists($this, 'summary')) {
            unset($this->summary);
        }
    }
}
