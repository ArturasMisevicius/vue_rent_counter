<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PrivacyPolicy extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.privacy-policy';

    protected static ?string $navigationLabel = 'Privacy Policy';

    protected static ?string $title = 'Privacy Policy';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 90;

    protected static bool $shouldRegisterNavigation = true;
}
