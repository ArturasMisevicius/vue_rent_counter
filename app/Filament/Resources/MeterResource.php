<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\MeterType;
use App\Enums\UserRole;
use App\Filament\Concerns\HasTranslatedValidation;
use App\Filament\Resources\MeterResource\Pages;
use App\Filament\Resources\MeterResource\RelationManagers;
use App\Models\Meter;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament resource for managing meters.
 *
 * Provides CRUD operations for meters with:
 * - Tenant-scoped data access
 * - Role-based navigation visibility
 * - Localized validation messages
 * - Integration with StoreMeterRequest and UpdateMeterRequest validation
 *
 * @see \App\Models\Meter
 * @see \App\Policies\MeterPolicy
 * @see \App\Http\Requests\StoreMeterRequest
 * @see \App\Http\Requests\UpdateMeterRequest
 */
class MeterResource extends Resource
{
    use HasTranslatedValidation;

    protected static ?string $model = Meter::class;

    /**
     * Translation prefix for validation messages.
     *
     * Used by HasTranslatedValidation trait to load messages from
     * lang/{locale}/meters.php under the 'validation' key.
     */
    protected static string $translationPrefix = 'meters.validation';

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 4;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-bolt';
    }

    public static function getNavigationLabel(): string
    {
        return __('meters.labels.meters');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.operations');
    }

    protected static ?string $recordTitleAttribute = 'serial_number';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * Hide from tenant users.
     * Policies handle granular authorization.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role !== UserRole::TENANT;
    }

    /**
     * Get the displayable label for the resource.
     */
    public static function getLabel(): string
    {
        return __('meters.labels.meter');
    }

    /**
     * Get the displayable plural label for the resource.
     */
    public static function getPluralLabel(): string
    {
        return __('meters.labels.meters');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(__('meters.sections.meter_details'))
                    ->description(__('meters.sections.meter_details_description'))
                    ->schema([
                        Forms\Components\Select::make('property_id')
                            ->label(__('meters.labels.property'))
                            ->relationship(
                                name: 'property',
                                titleAttribute: 'address',
                                modifyQueryUsing: fn (Builder $query) => self::scopeToUserTenant($query)
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('meters.helper_text.property'))
                            ->validationMessages(self::getValidationMessages('property_id')),

                        Forms\Components\Select::make('service_configuration_id')
                            ->label('Service Configuration')
                            ->options(function (Get $get): array {
                                $propertyId = $get('property_id');

                                if (!$propertyId) {
                                    return [];
                                }

                                return ServiceConfiguration::query()
                                    ->where('property_id', $propertyId)
                                    ->where('is_active', true)
                                    ->with('utilityService:id,name,unit_of_measurement')
                                    ->orderBy('effective_from', 'desc')
                                    ->get()
                                    ->mapWithKeys(function (ServiceConfiguration $config): array {
                                        $service = $config->utilityService;
                                        $label = $service
                                            ? "{$service->name} ({$service->unit_of_measurement})"
                                            : "Service #{$config->id}";

                                        return [$config->id => $label];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->native(false)
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if (!$state) {
                                    return;
                                }

                                $set('type', MeterType::CUSTOM->value);

                                $config = ServiceConfiguration::with('utilityService')->find($state);
                                if ($config?->pricing_model === \App\Enums\PricingModel::TIME_OF_USE) {
                                    $set('supports_zones', true);
                                }
                            })
                            ->helperText('Link this meter to a property service so it is billed correctly.'),

                        Forms\Components\Select::make('type')
                            ->label(__('meters.labels.type'))
                            ->options(MeterType::class)
                            ->required()
                            ->native(false)
                            ->default(MeterType::CUSTOM->value)
                            ->disabled(fn (Get $get): bool => filled($get('service_configuration_id')))
                            ->dehydrated(fn (?string $state): bool => true)
                            ->helperText(__('meters.helper_text.type'))
                            ->validationMessages(self::getValidationMessages('type')),

                        Forms\Components\TextInput::make('serial_number')
                            ->label(__('meters.labels.serial_number'))
                            ->placeholder(__('meters.placeholders.serial_number'))
                            ->helperText(__('meters.helper_text.serial_number'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->validationMessages(self::getValidationMessages('serial_number')),

                        Forms\Components\DatePicker::make('installation_date')
                            ->label(__('meters.labels.installation_date'))
                            ->helperText(__('meters.helper_text.installation_date'))
                            ->required()
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->validationMessages(self::getValidationMessages('installation_date')),

                        Forms\Components\Toggle::make('supports_zones')
                            ->label(__('meters.labels.supports_zones'))
                            ->helperText(__('meters.helper_text.supports_zones'))
                            ->default(false)
                            ->inline(false)
                            ->validationMessages(self::getValidationMessages('supports_zones')),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Scope query to authenticated user's tenant.
     */
    protected static function scopeToUserTenant(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user instanceof User && $user->tenant_id) {
            $table = $query->getModel()->getTable();
            $query->where("{$table}.tenant_id", $user->tenant_id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('property.address')
                    ->label(__('meters.labels.property'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->tooltip(fn ($record): string => __('meters.tooltips.property_address', [
                        'address' => $record->property->address,
                    ])),

                Tables\Columns\TextColumn::make('serviceConfiguration.utilityService.name')
                    ->label('Service')
                    ->badge()
                    ->formatStateUsing(fn ($state, Meter $record): string => $record->getServiceDisplayName())
                    ->color(fn (Meter $record): string => match ($record->type) {
                        MeterType::ELECTRICITY => 'warning',
                        MeterType::WATER_COLD => 'info',
                        MeterType::WATER_HOT => 'danger',
                        MeterType::HEATING => 'success',
                        MeterType::CUSTOM => 'gray',
                    }),

                Tables\Columns\TextColumn::make('serviceConfiguration.utilityService.unit_of_measurement')
                    ->label('Unit')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state, Meter $record): string => $record->getUnitOfMeasurement()),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('meters.labels.type'))
                    ->badge()
                    ->color(fn (MeterType $state): string => match ($state) {
                        MeterType::ELECTRICITY => 'warning',
                        MeterType::WATER_COLD => 'info',
                        MeterType::WATER_HOT => 'danger',
                        MeterType::HEATING => 'success',
                        MeterType::CUSTOM => 'gray',
                    })
                    ->formatStateUsing(fn (?MeterType $state): ?string => $state?->label())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('serial_number')
                    ->label(__('meters.labels.serial_number'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('meters.tooltips.copy_serial'))
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('installation_date')
                    ->label(__('meters.labels.installation_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('supports_zones')
                    ->label(__('meters.labels.supports_zones'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record): string => $record->supports_zones
                        ? __('meters.tooltips.supports_zones_yes')
                        : __('meters.tooltips.supports_zones_no')
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('readings_count')
                    ->label(__('meters.labels.readings_count'))
                    ->counts('readings')
                    ->badge()
                    ->color('gray')
                    ->tooltip(__('meters.tooltips.readings_count'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('meters.labels.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('meters.filters.type'))
                    ->options(MeterType::labels())
                    ->native(false),

                Tables\Filters\SelectFilter::make('property_id')
                    ->label(__('meters.filters.property'))
                    ->relationship('property', 'address')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Tables\Filters\Filter::make('supports_zones')
                    ->label(__('meters.filters.supports_zones'))
                    ->query(fn (Builder $query): Builder => $query->where('supports_zones', true))
                    ->toggle(),

                Tables\Filters\Filter::make('no_readings')
                    ->label(__('meters.filters.no_readings'))
                    ->query(fn (Builder $query): Builder => $query->doesntHave('readings'))
                    ->toggle(),
            ])
            ->recordActions([
                // Table row actions removed - use page header actions instead
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading(__('meters.modals.bulk_delete.title'))
                        ->modalDescription(__('meters.modals.bulk_delete.description'))
                        ->modalSubmitActionLabel(__('meters.modals.bulk_delete.confirm')),
                ]),
            ])
            ->emptyStateHeading(__('meters.empty_state.heading'))
            ->emptyStateDescription(__('meters.empty_state.description'))
            ->emptyStateActions([
                // Empty state actions removed - use page header actions instead
            ])
            ->defaultSort('serial_number', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReadingsRelationManager::class,
        ];
    }

    /**
     * Get the navigation badge for the resource.
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $query = static::getModel()::query();

        // Apply tenant scope for non-superadmin users
        if ($user->role !== UserRole::SUPERADMIN && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color for the resource.
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeters::route('/'),
            'create' => Pages\CreateMeter::route('/create'),
            'view' => Pages\ViewMeter::route('/{record}'),
            'edit' => Pages\EditMeter::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['serial_number'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Type' => ucfirst($record->type?->value ?? 'Unknown'),
            'Installation Date' => $record->installation_date?->format('Y-m-d') ?? 'N/A',
        ];
    }

    public static function getGlobalSearchResultActions(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            \Filament\GlobalSearch\Actions\Action::make('view')
                ->iconButton()
                ->icon('heroicon-m-eye')
                ->url(static::getUrl('view', ['record' => $record])),
        ];
    }
}
