<?php

namespace App\Filament\Resources\Tariffs;

use App\Filament\Resources\Tariffs\Pages\CreateTariff;
use App\Filament\Resources\Tariffs\Pages\EditTariff;
use App\Filament\Resources\Tariffs\Pages\ListTariffs;
use App\Filament\Resources\Tariffs\Pages\ViewTariff;
use App\Filament\Resources\Tariffs\Schemas\TariffForm;
use App\Filament\Resources\Tariffs\Schemas\TariffInfolist;
use App\Filament\Resources\Tariffs\Tables\TariffsTable;
use App\Models\Tariff;
use App\Support\Admin\OrganizationContext;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TariffResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldCheckPolicyExistence = false;

    protected static ?string $model = Tariff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TariffForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TariffInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TariffsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.tariffs.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.tariffs.plural');
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
     * @return Builder<Tariff>
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
                'provider_id',
                'remote_id',
                'name',
                'configuration',
                'active_from',
                'active_until',
                'created_at',
                'updated_at',
            ])
            ->whereHas('provider', fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->with([
                'provider:id,organization_id,name,service_type',
            ])
            ->withCount('serviceConfigurations');
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        $providerOrganizationId = $record instanceof Tariff
            ? ($record->relationLoaded('provider')
                ? $record->provider?->organization_id
                : $record->provider()->value('organization_id'))
            : null;

        return $record instanceof Tariff
            && $providerOrganizationId === app(OrganizationContext::class)->currentOrganizationId()
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

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTariffs::route('/'),
            'create' => CreateTariff::route('/create'),
            'view' => ViewTariff::route('/{record}'),
            'edit' => EditTariff::route('/{record}/edit'),
        ];
    }
}
