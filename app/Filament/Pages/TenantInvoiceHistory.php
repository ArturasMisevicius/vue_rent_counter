<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Actions\Help\ContextualHelpAction;
use App\Filament\Support\Workspace\WorkspaceResolver;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class TenantInvoiceHistory extends TenantPortalPage
{
    protected static ?string $slug = 'tenant-invoice-history';

    protected static ?string $navigationLabel = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected string $view = 'filament.pages.tenant-invoice-history';

    public static function getNavigationLabel(): string
    {
        return __('tenant.navigation.invoices');
    }

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('tenant.invoices'),
        ];
    }

    public static function canAccess(): bool
    {
        $workspaceResolver = app(WorkspaceResolver::class);

        if (! $workspaceResolver instanceof WorkspaceResolver) {
            return false;
        }

        $workspace = $workspaceResolver->current();

        return $workspace?->isTenant() ?? false;
    }
}
