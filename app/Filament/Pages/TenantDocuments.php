<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Actions\Help\ContextualHelpAction;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class TenantDocuments extends TenantPortalPage
{
    protected static ?string $slug = 'tenant-documents';

    protected static ?string $navigationLabel = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected string $view = 'filament.pages.tenant-documents';

    public static function getNavigationLabel(): string
    {
        return __('tenant.navigation.documents');
    }

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('tenant.documents'),
        ];
    }
}
