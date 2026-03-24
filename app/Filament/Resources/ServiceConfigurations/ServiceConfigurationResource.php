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
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ServiceConfigurationResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

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
        return static::allows('viewAny', ServiceConfiguration::class);
    }

    public static function canCreate(): bool
    {
        return static::allows('create', ServiceConfiguration::class);
    }

    /**
     * @return Builder<ServiceConfiguration>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::currentUser();
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        return parent::getEloquentQuery()
            ->forWorkspaceIndex($user?->isSuperadmin() ?? false, $organizationId);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof ServiceConfiguration
            && static::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof ServiceConfiguration
            && static::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof ServiceConfiguration
            && static::allows('delete', $record);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::guard()->user();

        return $user instanceof User ? $user : null;
    }

    private static function allows(string $ability, ServiceConfiguration|string $subject): bool
    {
        $user = static::currentUser();

        return $user instanceof User
            && Gate::forUser($user)->allows($ability, $subject);
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
