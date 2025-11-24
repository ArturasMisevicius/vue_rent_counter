<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use UnitEnum;
use App\Enums\MeterType;
use App\Filament\Concerns\HasTranslatedValidation;
use App\Filament\Resources\MeterResource\Pages;
use App\Models\Meter;
use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeterResource extends Resource
{
    use HasTranslatedValidation;

    protected static ?string $model = Meter::class;

    protected static string $translationPrefix = 'meters.validation';

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-cpu-chip';
    }

    public static function getNavigationLabel(): string
    {
        return __('app.nav.meters');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.operations');
    }

    // Integrate MeterPolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Meter::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', Meter::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    // Visible to all authenticated users (Requirements 9.1, 9.2, 9.3)
    // Tenants can view meters for their properties
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('property_id')
                    ->label(__('meters.labels.property'))
                    ->relationship('property', 'address', function (Builder $query) {
                        // Filter properties by authenticated user's tenant_id (Requirement 9.1, 12.4)
                        $user = auth()->user();
                        if ($user && $user->tenant_id) {
                            $query->where('tenant_id', $user->tenant_id);

                            // For tenant users, filter by property_id as well
                            if ($user->role === \App\Enums\UserRole::TENANT && $user->property_id) {
                                $query->where('id', $user->property_id);
                            }
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->validationMessages(self::getValidationMessages('property_id')),

                Forms\Components\Select::make('type')
                    ->label(__('meters.labels.type'))
                    ->options(MeterType::labels())
                    ->required()
                    ->native(false)
                    ->validationMessages(self::getValidationMessages('type')),

                Forms\Components\TextInput::make('serial_number')
                    ->label(__('meters.labels.serial_number'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(self::getValidationMessages('serial_number')),

                Forms\Components\DatePicker::make('installation_date')
                    ->label(__('meters.labels.installation_date'))
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->validationMessages(self::getValidationMessages('installation_date')),

                Forms\Components\Toggle::make('supports_zones')
                    ->label(__('meters.labels.supports_time_of_use'))
                    ->helperText(__('meters.helper_text.supports_time_of_use'))
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('property.address')
                    ->label(__('meters.labels.property'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('meters.labels.type'))
                    ->badge()
                    ->color(fn (MeterType $state): string => match ($state) {
                        MeterType::ELECTRICITY => 'warning',
                        MeterType::WATER_COLD => 'info',
                        MeterType::WATER_HOT => 'danger',
                        MeterType::HEATING => 'success',
                    })
                    ->formatStateUsing(fn (?MeterType $state): ?string => $state?->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('serial_number')
                    ->label(__('meters.labels.serial_number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('installation_date')
                    ->label(__('meters.labels.installation_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('supports_zones')
                    ->label(__('meters.labels.zones'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('meters.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('meters.labels.type'))
                    ->options(MeterType::labels())
                    ->native(false),

                Tables\Filters\TernaryFilter::make('supports_zones')
                    ->label(__('meters.filters.supports_zones'))
                    ->placeholder(__('meters.filters.all_meters'))
                    ->trueLabel(__('meters.filters.with_zones'))
                    ->falseLabel(__('meters.filters.without_zones'))
                    ->native(false),
            ])
            ->actions([
                // Table row actions removed - use page header actions instead
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('property.address', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeters::route('/'),
            'create' => Pages\CreateMeter::route('/create'),
            'edit' => Pages\EditMeter::route('/{record}/edit'),
        ];
    }
}
