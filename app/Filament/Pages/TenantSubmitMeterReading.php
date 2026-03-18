<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class TenantSubmitMeterReading extends TenantPortalPage
{
    protected static ?string $slug = 'tenant-submit-meter-reading';

    protected static ?string $navigationLabel = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected string $view = 'filament.pages.tenant-submit-meter-reading';

    public function getTitle(): string
    {
        return __('tenant.navigation.readings');
    }

    public static function getNavigationLabel(): string
    {
        return __('tenant.navigation.readings');
    }
}
