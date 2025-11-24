<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;

class TermsOfService extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Terms of Service';

    protected static ?string $title = 'Terms of Service';

    protected string $view = 'filament.pages.terms-of-service';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 91;

    protected static bool $shouldRegisterNavigation = true;
}
