<?php

namespace App\Filament\Resources\Meters;

use App\Filament\Resources\Meters\Pages\CreateMeter;
use App\Filament\Resources\Meters\Pages\EditMeter;
use App\Filament\Resources\Meters\Pages\ListMeters;
use App\Filament\Resources\Meters\Pages\ViewMeter;
use App\Filament\Resources\Meters\Schemas\MeterForm;
use App\Filament\Resources\Meters\Schemas\MeterInfolist;
use App\Filament\Resources\Meters\Tables\MetersTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Meter;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MeterResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldCheckPolicyExistence = false;

    protected static ?string $model = Meter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MeterForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MeterInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MetersTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.meters.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.meters.plural');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
    }

    /**
     * @return Builder<Meter>
     */
    public static function getEloquentQuery(): Builder
    {
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()->forOrganizationWorkspace($organizationId);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof Meter
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

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListMeters::route('/'),
            'create' => CreateMeter::route('/create'),
            'view' => ViewMeter::route('/{record}'),
            'edit' => EditMeter::route('/{record}/edit'),
        ];
    }
}
