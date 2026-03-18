<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class TenantInvoiceHistory extends TenantPortalPage
{
    protected static ?string $slug = 'tenant-invoice-history';

    protected static ?string $navigationLabel = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected string $view = 'filament.pages.tenant-invoice-history';

    public function getTitle(): string
    {
        return __('tenant.navigation.invoices');
    }

    public static function getNavigationLabel(): string
    {
        return __('tenant.navigation.invoices');
    }
}
