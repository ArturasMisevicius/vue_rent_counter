<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuildingResource\RelationManagers;

use App\Enums\PropertyType;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Properties Relation Manager for Building Resource
 *
 * Manages the properties associated with a building in the Filament admin panel.
 * Integrates validation from StorePropertyRequest and UpdatePropertyRequest.
 * Enforces tenant scope isolation and automatic tenant assignment.
 *
 * @property-read string $relationship
 */
final class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    protected static ?string $recordTitleAttribute = 'address';

    protected static ?string $title = 'Properties';

    protected static ?string $icon = 'heroicon-o-home';

    /**
     * Configure the form schema for creating and editing properties.
     *
     * Integrates validation rules from StorePropertyRequest and UpdatePropertyRequest.
     * Automatically sets default area values based on property type.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Property Details')
                    ->description('Enter the basic information for this property')
                    ->icon('heroicon-o-home')
                    ->schema([
                        $this->getAddressField(),
                        $this->getTypeField(),
                        $this->getAreaField(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->description('Optional details about this property')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Placeholder::make('building_info')
                            ->label('Building')
                            ->content(fn ($livewire): string => $livewire->getOwnerRecord()->name ?? 'N/A'),

                        Forms\Components\Placeholder::make('tenant_info')
                            ->label('Current Tenant')
                            ->content(fn (?Property $record): string => $record?->tenants->first()?->name ?? 'Vacant')
                            ->visible(fn (?Property $record): bool => $record !== null),

                        Forms\Components\Placeholder::make('meters_info')
                            ->label('Installed Meters')
                            ->content(fn (?Property $record): int => $record?->meters->count() ?? 0)
                            ->visible(fn (?Property $record): bool => $record !== null),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Get the address field configuration.
     *
     * Uses validation rules from StorePropertyRequest.
     */
    protected function getAddressField(): Forms\Components\TextInput
    {
        $request = new StorePropertyRequest;
        $messages = $request->messages();

        return Forms\Components\TextInput::make('address')
            ->label('Address')
            ->placeholder('e.g., Apartment 12, Floor 3')
            ->required()
            ->maxLength(255)
            ->validationAttribute('address')
            ->validationMessages([
                'required' => $messages['address.required'],
                'max' => 'The property address may not be greater than 255 characters.',
            ])
            ->helperText('Enter the unit number, floor, or other identifying information')
            ->columnSpanFull();
    }

    /**
     * Get the property type field configuration.
     *
     * Uses validation rules from StorePropertyRequest.
     * Automatically sets default area based on selected type.
     */
    protected function getTypeField(): Forms\Components\Select
    {
        $request = new StorePropertyRequest;
        $messages = $request->messages();

        return Forms\Components\Select::make('type')
            ->label('Property Type')
            ->options(PropertyType::class)
            ->required()
            ->native(false)
            ->validationAttribute('type')
            ->rules([Rule::enum(PropertyType::class)])
            ->validationMessages([
                'required' => $messages['type.required'],
                'enum' => $messages['type.enum'],
            ])
            ->helperText('Select the type of property')
            ->live()
            ->afterStateUpdated(fn (string $state, Forms\Set $set): mixed => $this->setDefaultArea($state, $set));
    }

    /**
     * Get the area field configuration.
     *
     * Uses validation rules from StorePropertyRequest.
     */
    protected function getAreaField(): Forms\Components\TextInput
    {
        $request = new StorePropertyRequest;
        $messages = $request->messages();
        $config = config('billing.property');

        return Forms\Components\TextInput::make('area_sqm')
            ->label('Area (m²)')
            ->placeholder('0.00')
            ->required()
            ->numeric()
            ->minValue($config['min_area'])
            ->maxValue($config['max_area'])
            ->suffix('m²')
            ->step(0.01)
            ->validationAttribute('area_sqm')
            ->validationMessages([
                'required' => $messages['area_sqm.required'],
                'numeric' => $messages['area_sqm.numeric'],
                'min' => $messages['area_sqm.min'],
                'max' => $messages['area_sqm.max'],
            ])
            ->helperText('Enter the total area in square meters');
    }

    /**
     * Set default area based on property type.
     */
    protected function setDefaultArea(string $state, Forms\Set $set): void
    {
        $config = config('billing.property');

        if ($state === PropertyType::APARTMENT->value) {
            $set('area_sqm', $config['default_apartment_area']);
        } elseif ($state === PropertyType::HOUSE->value) {
            $set('area_sqm', $config['default_house_area']);
        }
    }

    /**
     * Configure the table schema for displaying properties.
     *
     * Includes filters, bulk actions, and relationship columns.
     * Eager loads relationships to prevent N+1 queries.
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['tenants', 'meters']))
            ->columns([
                Tables\Columns\TextColumn::make('address')
                    ->label('Address')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Property $record): string => $record->type->getLabel())
                    ->icon('heroicon-o-home')
                    ->copyable()
                    ->tooltip('Click to copy address'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (PropertyType $state): string => match ($state) {
                        PropertyType::APARTMENT => 'info',
                        PropertyType::HOUSE => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('area_sqm')
                    ->label('Area')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' m²')
                    ->sortable()
                    ->alignEnd()
                    ->icon('heroicon-o-squares-2x2'),

                Tables\Columns\TextColumn::make('tenants.name')
                    ->label('Current Tenant')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'warning' : 'gray')
                    ->default('Vacant')
                    ->icon(fn (?string $state): string => $state ? 'heroicon-o-user' : 'heroicon-o-home-modern')
                    ->searchable()
                    ->tooltip(fn (Property $record): string => $record->tenants->isEmpty()
                        ? 'No tenant assigned'
                        : 'Occupied by '.$record->tenants->first()?->name),

                Tables\Columns\TextColumn::make('meters_count')
                    ->label('Meters')
                    ->counts('meters')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-bolt')
                    ->tooltip('Number of installed meters')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Property Type')
                    ->options(PropertyType::class)
                    ->native(false),

                Tables\Filters\TernaryFilter::make('has_tenant')
                    ->label('Occupancy')
                    ->placeholder('All properties')
                    ->trueLabel('Occupied')
                    ->falseLabel('Vacant')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('tenants'),
                        false: fn (Builder $query) => $query->whereDoesntHave('tenants'),
                    )
                    ->native(false),

                Tables\Filters\Filter::make('large_properties')
                    ->label('Large (>100m²)')
                    ->query(fn (Builder $query) => $query->where('area_sqm', '>', 100))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(fn (array $data): array => $this->preparePropertyData($data))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Property created')
                            ->body('The property has been added to this building.')
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil')
                        ->mutateFormDataUsing(fn (array $data): array => $this->preparePropertyData($data))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Property updated')
                                ->body('The property details have been saved.')
                        ),

                    Tables\Actions\Action::make('manage_tenant')
                        ->label('Manage Tenant')
                        ->icon('heroicon-o-user-plus')
                        ->color('warning')
                        ->form(fn (Property $record): array => $this->getTenantManagementForm($record))
                        ->action(function (Property $record, array $data): void {
                            $this->handleTenantManagement($record, $data);
                        })
                        ->modalWidth('md'),

                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure? This will also remove all associated meters and readings.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Property deleted')
                                ->body('The property has been removed.')
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure? This will also remove all associated meters and readings.')
                        ->successNotification(
                            fn (int $count): Notification => Notification::make()
                                ->success()
                                ->title('Properties deleted')
                                ->body("Successfully deleted {$count} properties.")
                        ),

                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function (): void {
                            $this->handleExport();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('address', 'asc')
            ->poll('30s')
            ->emptyStateHeading('No properties yet')
            ->emptyStateDescription('Add properties to this building using the button above.')
            ->emptyStateIcon('heroicon-o-home')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add First Property')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    /**
     * Prepare property data for create/update operations.
     *
     * Automatically sets tenant_id and building_id (Requirements 3.5, 7.5).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePropertyData(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['building_id'] = $this->getOwnerRecord()->id;

        return $data;
    }

    /**
     * Get the tenant management form configuration.
     *
     * @return array<Forms\Components\Component>
     */
    protected function getTenantManagementForm(Property $record): array
    {
        $hasTenant = $record->tenants->isNotEmpty();

        return [
            Forms\Components\Select::make('tenant_id')
                ->label($hasTenant ? 'Reassign Tenant' : 'Assign Tenant')
                ->relationship(
                    'tenants',
                    'name',
                    fn (Builder $query): Builder => $query
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->whereDoesntHave('properties')
                )
                ->searchable()
                ->required(! $hasTenant)
                ->helperText($hasTenant
                    ? 'Select a new tenant or leave empty to remove current tenant'
                    : 'Only available tenants without properties are shown')
                ->nullable($hasTenant),
        ];
    }

    /**
     * Handle tenant assignment/removal for a property.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleTenantManagement(Property $record, array $data): void
    {
        if (empty($data['tenant_id'])) {
            $record->tenants()->detach();

            Notification::make()
                ->success()
                ->title('Tenant removed')
                ->body('The property is now vacant.')
                ->send();

            return;
        }

        $record->tenants()->sync([$data['tenant_id']]);

        Notification::make()
            ->success()
            ->title('Tenant assigned')
            ->body('The tenant has been assigned to this property.')
            ->send();
    }

    /**
     * Handle property export action.
     */
    protected function handleExport(): void
    {
        // Export logic - could integrate with Laravel Excel
        Notification::make()
            ->info()
            ->title('Export started')
            ->body('Your export is being processed.')
            ->send();
    }

    /**
     * Apply tenant scope to the relation query.
     *
     * Properties are already scoped through the building relationship.
     */
    protected function applyTenantScoping(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Check if user can view any properties for the given building.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', Property::class);
    }
}
