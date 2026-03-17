<?php

namespace App\Filament\Resources\MeterReadings;

use App\Filament\Resources\MeterReadings\Pages\CreateMeterReading;
use App\Filament\Resources\MeterReadings\Pages\EditMeterReading;
use App\Filament\Resources\MeterReadings\Pages\ListMeterReadings;
use App\Filament\Resources\MeterReadings\Pages\ViewMeterReading;
use App\Filament\Resources\MeterReadings\Schemas\MeterReadingForm;
use App\Filament\Resources\MeterReadings\Schemas\MeterReadingInfolist;
use App\Filament\Resources\MeterReadings\Tables\MeterReadingsTable;
use App\Models\MeterReading;
use App\Support\Admin\OrganizationContext;
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

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
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

        return parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'meter_id',
                'submitted_by_user_id',
                'reading_value',
                'reading_date',
                'validation_status',
                'submission_method',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId)
            ->with([
                'meter:id,organization_id,property_id,name,identifier,type,unit',
                'property:id,organization_id,building_id,name,unit_number',
                'property.building:id,organization_id,name',
            ]);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof MeterReading
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
            'index' => ListMeterReadings::route('/'),
            'create' => CreateMeterReading::route('/create'),
            'view' => ViewMeterReading::route('/{record}'),
            'edit' => EditMeterReading::route('/{record}/edit'),
        ];
    }
}
