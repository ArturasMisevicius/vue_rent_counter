<?php

declare(strict_types=1);

namespace App\View\Components\Tenant;

use App\Filament\Support\View\BladeViewData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Card extends Component
{
    /**
     * @var array<int|string, string|bool>
     */
    public array $classes;

    public function __construct(
        public ?string $href = null,
        public string $tone = 'muted',
        public bool $wireNavigate = false,
    ) {
        $this->classes = BladeViewData::tenantCardClasses($tone, filled($href));
    }

    public function render(): View
    {
        return view('components.tenant.card');
    }
}
