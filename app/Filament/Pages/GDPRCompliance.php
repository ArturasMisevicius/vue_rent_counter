<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class GDPRCompliance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static string $view = 'filament.pages.gdpr-compliance';

    protected static ?string $navigationLabel = 'GDPR Compliance';

    protected static ?string $title = 'GDPR Compliance';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 92;

    protected static bool $shouldRegisterNavigation = true;
}
