<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Actions\Help\ContextualHelpAction;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class TenantVerification extends TenantPortalPage
{
    protected static ?string $slug = 'tenant-verification';

    protected static ?string $navigationLabel = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected string $view = 'filament.pages.tenant-verification';

    public static function getNavigationLabel(): string
    {
        return __('tenant.navigation.verification');
    }

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('tenant.verification'),
        ];
    }
}
