<?php

namespace App\Filament\Widgets\Admin;

use App\Support\Admin\Dashboard\UpcomingReadingDeadlineData;
use Filament\Widgets\Widget;

class UpcomingReadingDeadlinesWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.admin.upcoming-reading-deadlines-widget';

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'deadlines' => app(UpcomingReadingDeadlineData::class)->for(auth()->user()),
        ];
    }
}
