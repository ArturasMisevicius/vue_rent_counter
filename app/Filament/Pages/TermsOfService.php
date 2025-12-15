<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class TermsOfService extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    protected string $view = 'filament.pages.terms-of-service';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 91;

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationLabel(): string
    {
        return __('pages.terms_of_service');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system');
    }

    public function getTitle(): string
    {
        return __('pages.terms_of_service');
    }
}
