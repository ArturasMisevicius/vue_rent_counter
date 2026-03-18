<?php

namespace App\Filament\Resources\MeterReadings;

use App\Filament\Resources\MeterReadings\Pages\CreateMeterReading;
use App\Filament\Resources\MeterReadings\Pages\EditMeterReading;
use App\Filament\Resources\MeterReadings\Pages\ListMeterReadings;
use App\Filament\Resources\MeterReadings\Pages\ViewMeterReading;
use App\Filament\Resources\MeterReadings\Schemas\MeterReadingForm;
use App\Filament\Resources\MeterReadings\Schemas\MeterReadingInfolist;
use App\Filament\Resources\MeterReadings\Tables\MeterReadingsTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\MeterReading;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MeterReadingResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldCheckPolicyExistence = false;

    protected static ?string $model = MeterReading::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'reading_value';

    public static function form(Schema $schema): Schema
    {
        return MeterReadingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MeterReadingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeterReadingsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.meter_readings.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.meter_readings.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('shell.navigation.groups.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.meter_readings.navigation');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isSuperadmin() || $user?->isAdmin() || $user?->isManager();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isSuperadmin() || $user?->isAdmin() || $user?->isManager();
    }

    /**
     * @return Builder<MeterReading>
     */
    public static function getEloquentQuery(): Builder
    {
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()->forAdminWorkspace($organizationId);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof MeterReading
            && $record->organization_id === app(OrganizationContext::class)->currentOrganizationId()
            && ($user?->isSuperadmin() || $user?->isAdmin() || $user?->isManager());
    }

    public static function canEdit(Model $record): bool
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
            'index' => ListMeterReadings::route('/'),
            'create' => CreateMeterReading::route('/create'),
            'view' => ViewMeterReading::route('/{record}'),
            'edit' => EditMeterReading::route('/{record}/edit'),
        ];
    }
}
