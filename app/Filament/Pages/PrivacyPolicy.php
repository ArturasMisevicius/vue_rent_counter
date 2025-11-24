<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;

class PrivacyPolicy extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    protected string $view = 'filament.pages.privacy-policy';

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = 90;

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationLabel(): string
    {
        return __('pages.privacy_policy');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.system');
    }

    public function getTitle(): string
    {
        return __('pages.privacy_policy');
    }
}
