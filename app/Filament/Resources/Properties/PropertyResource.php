<?php

namespace App\Filament\Resources\Properties;

use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Filament\Resources\Properties\Schemas\PropertyForm;
use App\Filament\Resources\Properties\Schemas\PropertyInfolist;
use App\Filament\Resources\Properties\Tables\PropertiesTable;
use App\Models\Property;
use App\Support\Admin\SubscriptionLimitGuard;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PropertyResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PropertyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PropertyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertiesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.properties.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.properties.plural');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (! static::canAccess()) {
            return false;
        }

        if ($user === null) {
            return false;
        }

        if ($user->organization_id === null) {
            return false;
        }

        return app(SubscriptionLimitGuard::class)->canCreateProperty($user->organization_id);
    }

    /**
     * @return Builder<Property>
     */
    public static function getEloquentQuery(): Builder
    {
        $organizationId = auth()->user()?->organization_id;

        return parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'building_id',
                'name',
                'unit_number',
                'type',
                'floor_area_sqm',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId)
            ->with([
                'building:id,organization_id,name,address_line_1,city',
                'currentAssignment:id,organization_id,property_id,tenant_user_id,unit_area_sqm,assigned_at,unassigned_at',
                'currentAssignment.tenant:id,name',
                'assignments' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'tenant_user_id',
                        'unit_area_sqm',
                        'assigned_at',
                        'unassigned_at',
                    ])
                    ->with([
                        'tenant:id,name',
                    ])
                    ->latest('assigned_at'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListProperties::route('/'),
            'create' => CreateProperty::route('/create'),
            'view' => ViewProperty::route('/{record}'),
            'edit' => EditProperty::route('/{record}/edit'),
        ];
    }
}
