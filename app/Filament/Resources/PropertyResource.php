<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Concerns\HasRoleBasedNavigation;
use App\Filament\Concerns\HasTenantScoping;
use App\Filament\Concerns\HasTranslatedValidation;
use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers;
use App\Models\Property;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament resource for managing properties.
 *
 * Provides CRUD operations for properties with:
 * - Tenant-scoped data access
 * - Role-based navigation visibility
 * - Localized validation messages
 * - Relationship management (buildings, tenants, meters)
 *
 * @see \App\Models\Property
 * @see \App\Policies\PropertyPolicy
 * @see \App\Filament\Concerns\HasTranslatedValidation
 */
class PropertyResource extends Resource
{
    use HasRoleBasedNavigation;
    use HasTenantScoping;
    use HasTranslatedValidation;

    protected static ?string $model = Property::class;

    /**
     * Translation prefix for validation messages.
     *
     * Used by HasTranslatedValidation trait to load messages from
     * lang/{locale}/properties.php under the 'validation' key.
     */
    protected static string $translationPrefix = 'properties.validation';

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-home';
    }

    public static function getNavigationLabel(): string
    {
        return __('properties.labels.properties');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.operations');
    }

    protected static ?string $recordTitleAttribute = 'address';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * Get the displayable label for the resource.
     */
    public static function getLabel(): string
    {
        return __('properties.labels.property');
    }

    /**
     * Get the displayable plural label for the resource.
     */
    public static function getPluralLabel(): string
    {
        return __('properties.labels.properties');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(__('properties.sections.property_details'))
                    ->description(__('properties.sections.property_details_description'))
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label(__('properties.labels.address'))
                            ->placeholder(__('properties.placeholders.address'))
                            ->helperText(__('properties.helper_text.address'))
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->validationMessages(self::getValidationMessages('address')),

                        Forms\Components\Select::make('type')
                            ->label(__('properties.labels.type'))
                            ->options(PropertyType::class)
                            ->required()
                            ->native(false)
                            ->helperText(__('properties.helper_text.type'))
                            ->validationMessages(self::getValidationMessages('type')),

                        Forms\Components\TextInput::make('area_sqm')
                            ->label(__('properties.labels.area'))
                            ->placeholder(__('properties.placeholders.area'))
                            ->helperText(__('properties.helper_text.area'))
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(999999.99)
                            ->suffix(__('app.units.square_meter'))
                            ->step(0.01)
                            ->validationMessages(self::getValidationMessages('area_sqm')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('properties.sections.additional_info'))
                    ->description(__('properties.sections.additional_info_description'))
                    ->schema([
                        Forms\Components\Select::make('building_id')
                            ->label(__('properties.labels.building'))
                            ->relationship(
                                name: 'building',
                                titleAttribute: 'address',
                                modifyQueryUsing: fn (Builder $query) => self::scopeToUserTenant($query)
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->validationMessages(self::getValidationMessages('building_id')),

                        Forms\Components\Select::make('tenants')
                            ->label(__('properties.labels.current_tenant'))
                            ->relationship(
                                name: 'tenants',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->tap(fn ($q) => self::scopeToUserTenant($q))
                                    ->where('role', UserRole::TENANT)
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText(__('properties.helper_text.tenant_available')),

                        Forms\Components\Select::make('tags')
                            ->label(__('properties.labels.tags'))
                            ->relationship(
                                name: 'tags',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => self::scopeToUserTenant($query)
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('tags.labels.name'))
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\ColorPicker::make('color')
                                    ->label(__('tags.labels.color'))
                                    ->nullable(),
                                Forms\Components\Textarea::make('description')
                                    ->label(__('tags.labels.description'))
                                    ->nullable()
                                    ->maxLength(500),
                            ])
                            ->helperText(__('properties.helper_text.tags')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(PropertyResource\Columns\PropertyTableColumns::get())
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('properties.filters.type'))
                    ->options(PropertyType::labels())
                    ->native(false),

                Tables\Filters\SelectFilter::make('building_id')
                    ->label(__('properties.filters.building'))
                    ->relationship('building', 'address')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Tables\Filters\Filter::make('vacant')
                    ->label(__('properties.filters.vacant'))
                    ->query(fn (Builder $query): Builder => $query->doesntHave('tenants'))
                    ->toggle(),

                Tables\Filters\Filter::make('large_properties')
                    ->label(__('properties.filters.large_properties'))
                    ->query(fn (Builder $query): Builder => $query->where('area_sqm', '>', 100))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('tags')
                    ->label(__('properties.filters.tags'))
                    ->relationship('tags', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->native(false),
            ])
            ->recordActions([
                // Table row actions removed - use page header actions instead
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading(__('properties.modals.bulk_delete.title'))
                        ->modalDescription(__('properties.modals.bulk_delete.description'))
                        ->modalSubmitActionLabel(__('properties.modals.bulk_delete.confirm')),
                ]),
            ])
            ->emptyStateHeading(__('properties.empty_state.heading'))
            ->emptyStateDescription(__('properties.empty_state.description'))
            ->emptyStateActions([
                // Empty state actions removed - use page header actions instead
            ])
            ->defaultSort('address', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MetersRelationManager::class,
            RelationManagers\ServiceConfigurationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['address', 'unit_number'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Unit Number' => $record->unit_number ?? 'N/A',
            'Type' => ucfirst($record->type?->value ?? 'Unknown'),
            'Area' => $record->area_sqm ? number_format($record->area_sqm, 2) . ' m²' : 'N/A',
        ];
    }

    public static function getGlobalSearchResultActions(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            \Filament\GlobalSearch\Actions\Action::make('edit')
                ->iconButton()
                ->icon('heroicon-m-pencil-square')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }
}
