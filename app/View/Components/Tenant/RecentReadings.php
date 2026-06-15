<?php

declare(strict_types=1);

namespace App\View\Components\Tenant;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class RecentReadings extends Component
{
    /**
     * @var Collection<int, array<string, mixed>>
     */
    public Collection $groups;

    /**
     * @param  iterable<int, array<string, mixed>>  $groups
     */
    public function __construct(iterable $groups = [])
    {
        $this->groups = collect($groups)->filter(fn (array $group): bool => ! empty($group['readings'] ?? []));
    }

    public function render(): View
    {
        return view('components.tenant.recent-readings');
    }
}
