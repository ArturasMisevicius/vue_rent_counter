<?php

namespace App\Filament\Resources\Organizations;

use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Resources\Organizations\Schemas\OrganizationInfolist;
use App\Filament\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Organizations';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Organization';

    protected static ?string $pluralModelLabel = 'Organizations';

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrganizationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.organizations.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.organizations.plural');
    }

    /**
     * @return Builder<Organization>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select(['id', 'name', 'slug', 'status', 'owner_user_id', 'created_at', 'updated_at'])
            ->with(['owner:id,name,email'])
            ->withCount(['users', 'properties', 'subscriptions']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'view' => ViewOrganization::route('/{record}'),
        ];
    }
}
