<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TermsOfService extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.terms-of-service';

    protected static ?string $navigationLabel = 'Terms of Service';

    protected static ?string $title = 'Terms of Service';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 91;

    protected static bool $shouldRegisterNavigation = true;
}
