<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ServiceType;
use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\RelationManagers;
use App\Models\Provider;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Filament resource for managing utility providers.
 *
 * Provides CRUD operations for providers with:
 * - Tenant-scoped data access
 * - Role-based navigation visibility (admin only)
 * - Service type categorization
 * - Contact information management
 * - Relationship management (tariffs)
 *
 * @see \App\Models\Provider
 * @see \App\Policies\ProviderPolicy
 */
class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-building-library';
    }

    public static function getNavigationLabel(): string
    {
        return __('app.nav.providers');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.configuration');
    }

    // Integrate ProviderPolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Provider::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', Provider::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    /**
     * Hide from non-admin users (Requirements 9.1, 9.2, 9.3).
     * Providers are system configuration resources accessible only to admins and superadmins.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof \App\Models\User && in_array($user->role, [
            \App\Enums\UserRole::SUPERADMIN,
            \App\Enums\UserRole::ADMIN,
        ], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(__('providers.sections.provider_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('providers.labels.name'))
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => __('providers.validation.name.required'),
                            ]),
                        
                        Forms\Components\Select::make('service_type')
                            ->label(__('providers.labels.service_type'))
                            ->options(ServiceType::class)
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('providers.validation.service_type.required'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('providers.sections.contact_information'))
                    ->schema([
                        Forms\Components\KeyValue::make('contact_info')
                            ->label(__('providers.labels.contact_info'))
                            ->keyLabel(__('providers.forms.contact.field'))
                            ->valueLabel(__('providers.forms.contact.value'))
                            ->addActionLabel(__('providers.forms.contact.add'))
                            ->reorderable(false)
                            ->helperText(__('providers.forms.contact.helper')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('providers.labels.name'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('service_type')
                    ->label(__('providers.labels.service_type'))
                    ->badge()
                    ->color(fn (ServiceType $state): string => match ($state) {
                        ServiceType::ELECTRICITY => 'warning',
                        ServiceType::WATER => 'info',
                        ServiceType::HEATING => 'danger',
                    })
                    ->formatStateUsing(fn (?ServiceType $state): ?string => $state?->label())
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('contact_info')
                    ->label(__('providers.labels.contact_info'))
                    ->formatStateUsing(function (array|string|null $state): string {
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            $state = is_array($decoded) ? $decoded : ['contact' => $state];
                        }

                        if (empty($state)) {
                            return __('providers.labels.no_contact_info');
                        }

                        $items = [];
                        foreach ($state as $key => $value) {
                            $items[] = ucfirst((string) $key) . ': ' . $value;
                        }

                        return implode(' | ', array_slice($items, 0, 2)) . (count($items) > 2 ? '...' : '');
                    })
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('tariffs_count')
                    ->label(__('providers.tables.tariff_count'))
                    ->counts('tariffs')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('providers.tables.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TariffsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
