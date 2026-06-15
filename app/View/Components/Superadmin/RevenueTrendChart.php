<?php

declare(strict_types=1);

namespace App\View\Components\Superadmin;

use App\Filament\Support\View\BladeViewData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RevenueTrendChart extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $chartData;

    /**
     * @param  array<string, mixed>  $chart
     */
    public function __construct(array $chart)
    {
        $this->chartData = BladeViewData::revenueTrendChart($chart);
    }

    public function render(): View
    {
        return view('components.superadmin.revenue-trend-chart');
    }
}
