<?php

declare(strict_types=1);

namespace App\View\Components\Tenant;

use App\Filament\Support\View\BladeViewData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Action extends Component
{
    /**
     * @var array<int|string, string|bool>
     */
    public array $classes;

    public function __construct(
        public ?string $href = null,
        public ?string $icon = null,
        public string $type = 'button',
        public string $variant = 'secondary',
        public bool $wireNavigate = false,
    ) {
        $this->classes = BladeViewData::tenantActionClasses($variant);
    }

    public function render(): View
    {
        return view('components.tenant.action');
    }
}
