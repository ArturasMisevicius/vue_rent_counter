<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationInvitationResource\Pages;
use App\Models\OrganizationInvitation;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;

class OrganizationInvitationResource extends Resource
{
    protected static ?string $model = OrganizationInvitation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Organization Invitations';

    protected static string|UnitEnum|null $navigationGroup = 'System Management';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationInvitations::route('/'),
            'view' => Pages\ViewOrganizationInvitation::route('/{record}'),
        ];
    }
}

