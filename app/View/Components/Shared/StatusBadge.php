<?php

declare(strict_types=1);

namespace App\View\Components\Shared;

use App\Filament\Support\View\BladeViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public string $resolvedClasses;

    public string $translationKey;

    public function __construct(mixed $status, ?Model $model = null)
    {
        $badge = BladeViewData::statusBadge($status, $model);
        $this->resolvedClasses = $badge['classes'];
        $this->translationKey = $badge['translation_key'];
    }

    public function render(): View
    {
        return view('components.shared.status-badge');
    }
}
