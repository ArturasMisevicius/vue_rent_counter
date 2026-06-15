<?php

declare(strict_types=1);

namespace App\View\Components\Shared;

use App\Filament\Support\Preferences\SupportedLocaleOptions;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LanguageSwitcher extends Component
{
    /**
     * @var array<string, string>
     */
    public array $supportedLocales;

    public string $currentLocale;

    public bool $isLight;

    public function __construct(public string $variant = 'dark')
    {
        $this->supportedLocales = app(SupportedLocaleOptions::class)->labels();
        $this->currentLocale = app()->getLocale();
        $this->isLight = $variant === 'light';
    }

    public function render(): View
    {
        return view('components.shared.language-switcher');
    }
}
