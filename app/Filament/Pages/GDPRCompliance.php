<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;

class GDPRCompliance extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'GDPR Compliance';

    protected static ?string $title = 'GDPR Compliance';

    protected string $view = 'filament.pages.gdpr-compliance';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 92;

    protected static bool $shouldRegisterNavigation = true;
}
