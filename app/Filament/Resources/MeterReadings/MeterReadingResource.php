<?php

namespace App\Filament\Resources\MeterReadings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Resources\MeterReadings\Pages\CreateMeterReading;
use App\Filament\Resources\MeterReadings\Pages\EditMeterReading;
use App\Filament\Resources\MeterReadings\Pages\ListMeterReadings;
use App\Filament\Resources\MeterReadings\Pages\ViewMeterReading;
use App\Models\MeterReading;
use App\Support\Admin\OrganizationContext;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
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
        return $schema->components([
            Section::make('Reading Details')
                ->schema([
                    Select::make('meter_id')
                        ->label('Meter')
                        ->relationship(
                            name: 'meter',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->select(['id', 'organization_id', 'property_id', 'name'])
                                ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId()),
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('reading_value')
                        ->label('Reading Value')
                        ->numeric()
                        ->required(),
                    DatePicker::make('reading_date')
                        ->label('Reading Date')
                        ->required(),
                    Select::make('submission_method')
                        ->label('Submission Method')
                        ->options(
                            collect(MeterReadingSubmissionMethod::cases())
                                ->mapWithKeys(fn (MeterReadingSubmissionMethod $method): array => [
                                    $method->value => ucfirst(str_replace('_', ' ', $method->value)),
                                ])
                                ->all(),
                        )
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reading Details')
                ->schema([
                    TextEntry::make('meter.name')
                        ->label('Meter'),
                    TextEntry::make('meter.property.name')
                        ->label('Property'),
                    TextEntry::make('reading_value')
                        ->label('Reading Value'),
                    TextEntry::make('reading_date')
                        ->label('Reading Date')
                        ->date(),
                    TextEntry::make('submission_method')
                        ->label('Submission Method')
                        ->formatStateUsing(fn ($state): string => ucfirst(str_replace('_', ' ', (string) ($state->value ?? $state)))),
                    TextEntry::make('validation_status')
                        ->label('Validation Status')
                        ->badge()
                        ->formatStateUsing(fn ($state): string => ucfirst((string) ($state->value ?? $state))),
                    TextEntry::make('notes')
                        ->label('Notes')
                        ->placeholder('No notes'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meter.name')
                    ->label('Meter')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('meter.property.name')
                    ->label('Property')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('reading_value')
                    ->label('Reading Value')
                    ->sortable(),
                TextColumn::make('reading_date')
                    ->label('Reading Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('validation_status')
                    ->label('Validation Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ucfirst((string) ($state->value ?? $state))),
            ])
            ->defaultSort('reading_date', 'desc');
    }

    public static function getModelLabel(): string
    {
        return 'Meter Reading';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Meter Readings';
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
                'meter:id,organization_id,property_id,name',
                'meter.property:id,organization_id,building_id,name',
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
