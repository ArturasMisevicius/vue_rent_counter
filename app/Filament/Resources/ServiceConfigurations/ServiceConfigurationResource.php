<?php

namespace App\Filament\Resources\ServiceConfigurations;

use App\Filament\Resources\ServiceConfigurations\Pages\CreateServiceConfiguration;
use App\Filament\Resources\ServiceConfigurations\Pages\EditServiceConfiguration;
use App\Filament\Resources\ServiceConfigurations\Pages\ListServiceConfigurations;
use App\Filament\Resources\ServiceConfigurations\Pages\ViewServiceConfiguration;
use App\Filament\Resources\ServiceConfigurations\Schemas\ServiceConfigurationForm;
use App\Filament\Resources\ServiceConfigurations\Schemas\ServiceConfigurationInfolist;
use App\Filament\Resources\ServiceConfigurations\Tables\ServiceConfigurationsTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\ServiceConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceConfigurationResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldCheckPolicyExistence = false;

    protected static ?string $model = ServiceConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function form(Schema $schema): Schema
    {
        return ServiceConfigurationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServiceConfigurationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceConfigurationsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.service_configurations.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.service_configurations.plural');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    /**
     * @return Builder<ServiceConfiguration>
     */
    public static function getEloquentQuery(): Builder
    {
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'utility_service_id',
                'pricing_model',
                'rate_schedule',
                'distribution_method',
                'is_shared_service',
                'effective_from',
                'effective_until',
                'tariff_id',
                'provider_id',
                'area_type',
                'custom_formula',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId)
            ->with([
                'property:id,organization_id,building_id,name,unit_number',
                'utilityService:id,organization_id,name,unit_of_measurement',
                'provider:id,organization_id,name',
                'tariff:id,provider_id,name',
            ]);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof ServiceConfiguration
            && $record->organization_id === app(OrganizationContext::class)->currentOrganizationId()
            && ($user?->isAdmin() || $user?->isManager());
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canView($record);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceConfigurations::route('/'),
            'create' => CreateServiceConfiguration::route('/create'),
            'view' => ViewServiceConfiguration::route('/{record}'),
            'edit' => EditServiceConfiguration::route('/{record}/edit'),
        ];
    }
}
