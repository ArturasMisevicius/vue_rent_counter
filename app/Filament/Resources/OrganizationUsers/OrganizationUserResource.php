<?php

namespace App\Filament\Resources\OrganizationUsers;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\OrganizationUsers\Pages\CreateOrganizationUser;
use App\Filament\Resources\OrganizationUsers\Pages\EditOrganizationUser;
use App\Filament\Resources\OrganizationUsers\Pages\ListOrganizationUsers;
use App\Filament\Resources\OrganizationUsers\Pages\ViewOrganizationUser;
use App\Filament\Resources\OrganizationUsers\Schemas\OrganizationUserForm;
use App\Filament\Resources\OrganizationUsers\Schemas\OrganizationUserInfolist;
use App\Filament\Resources\OrganizationUsers\Tables\OrganizationUsersTable;
use App\Models\OrganizationUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrganizationUserResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static ?string $model = OrganizationUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return OrganizationUserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrganizationUserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationUsers::route('/'),
            'create' => CreateOrganizationUser::route('/create'),
            'view' => ViewOrganizationUser::route('/{record}'),
            'edit' => EditOrganizationUser::route('/{record}/edit'),
        ];
    }
}
