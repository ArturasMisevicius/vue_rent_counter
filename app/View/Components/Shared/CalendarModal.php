<?php

declare(strict_types=1);

namespace App\View\Components\Shared;

use App\Filament\Support\Formatting\LocalizedDateFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CalendarModal extends Component
{
    public string $locale;

    public int $weekStartsOn;

    public bool $hasTime;

    public string $selectedDisplay;

    public function __construct(
        public string $id,
        public string $label,
        public string $value = '',
        ?string $displayValue = null,
        public string $mode = 'date',
        public ?string $min = null,
        public ?string $max = null,
        public bool $includeSeconds = false,
        public int $minuteStep = 1,
    ) {
        $this->locale = app()->getLocale();
        $this->weekStartsOn = in_array($this->locale, ['lt', 'ru', 'es'], true) ? 1 : 0;
        $this->hasTime = $mode === 'datetime';
        $this->selectedDisplay = $displayValue
            ?? (filled($value)
                ? ($this->hasTime ? LocalizedDateFormatter::dateTime($value) : LocalizedDateFormatter::date($value))
                : __('calendar.no_date_selected'));
    }

    public function render(): View
    {
        return view('components.shared.calendar-modal');
    }
}
