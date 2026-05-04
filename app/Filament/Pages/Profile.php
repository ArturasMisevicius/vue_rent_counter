<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithAccountProfileForms;
use App\Filament\Pages\Concerns\InteractsWithKycProfileForms;
use App\Filament\Pages\Concerns\InteractsWithProfileAvatarForms;
use App\Filament\Pages\Concerns\RefreshesOnShellLocaleUpdate;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class Profile extends Page
{
    use InteractsWithAccountProfileForms;
    use InteractsWithKycProfileForms;
    use InteractsWithProfileAvatarForms;
    use RefreshesOnShellLocaleUpdate;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.pages.profile';

    public function mount(): void
    {
        $this->applyShellLocale();
        $this->fillAccountProfileForms();
        $this->fillProfileAvatarForm();
        $this->fillKycProfileForm();
    }

    public function getTitle(): string
    {
        if (Auth::user()?->isTenant()) {
            return '';
        }

        return __('shell.profile.title');
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        return Auth::user() instanceof User;
    }
}
