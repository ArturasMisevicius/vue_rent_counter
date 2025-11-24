<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;

class PrivacyPolicy extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Privacy Policy';

    protected static ?string $title = 'Privacy Policy';

    protected string $view = 'filament.pages.privacy-policy';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 90;

    protected static bool $shouldRegisterNavigation = true;
}
