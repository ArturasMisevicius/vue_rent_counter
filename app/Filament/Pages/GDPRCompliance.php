<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;

class GDPRCompliance extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    protected string $view = 'filament.pages.gdpr-compliance';

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = 92;

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationLabel(): string
    {
        return __('pages.gdpr_compliance');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.system');
    }

    public function getTitle(): string
    {
        return __('pages.gdpr_compliance');
    }
}
