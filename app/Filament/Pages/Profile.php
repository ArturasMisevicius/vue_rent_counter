<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithAccountProfileForms;
use App\Filament\Pages\Concerns\RefreshesOnShellLocaleUpdate;
use Filament\Pages\Page;

class Profile extends Page
{
    use InteractsWithAccountProfileForms;
    use RefreshesOnShellLocaleUpdate;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.pages.profile';

    public function mount(): void
    {
        $this->applyShellLocale();
        $this->fillAccountProfileForms();
    }

    public function getTitle(): string
    {
        return __('shell.profile.title');
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
