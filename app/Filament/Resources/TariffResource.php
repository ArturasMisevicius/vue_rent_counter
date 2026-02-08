<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\Concerns\CachesAuthUser;
use App\Filament\Resources\TariffResource\Concerns\BuildsTariffFormFields;
use App\Filament\Resources\TariffResource\Pages;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use App\Models\Tariff;


class TariffResource extends Resource
{
    use BuildsTariffFormFields;

    use CachesAuthUser {
        clearCachedUser as protected clearAuthUserCache;
    }

    protected static ?string $model = Tariff::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-euro';
    
    protected static string|UnitEnum|null $navigationGroup = 'Configuration';
    
    protected static ?int $navigationSort = 1;

    protected static ?bool $navigationVisible = null;

    public static function clearCachedUser(): void
    {
        static::clearAuthUserCache();
        static::$navigationVisible = null;
    }

    public static function canViewAny(): bool
    {
        $user = static::getAuthenticatedUser();

        return $user?->can('viewAny', Tariff::class) ?? false;
    }

    public static function canCreate(): bool
    {
        $user = static::getAuthenticatedUser();

        return $user?->can('create', Tariff::class) ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = static::getAuthenticatedUser();

        return $user?->can('update', $record) ?? false;
    }

    public static function canDelete($record): bool
    {
        $user = static::getAuthenticatedUser();

        return $user?->can('delete', $record) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Avoid leaking static cache between tests / property test repetitions.
        if (app()->environment('testing')) {
            $user = static::getAuthenticatedUser();

            return $user !== null && in_array($user->role, [UserRole::SUPERADMIN, UserRole::ADMIN], true);
        }

        if (static::$navigationVisible !== null) {
            return static::$navigationVisible;
        }

        $user = static::getAuthenticatedUser();

        static::$navigationVisible = $user !== null && in_array($user->role, [UserRole::SUPERADMIN, UserRole::ADMIN], true);

        return static::$navigationVisible;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['provider:id,name,service_type']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Tariff')
                ->schema([
                    ...static::buildBasicInformationFields(),
                    ...static::buildEffectivePeriodFields(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Configuration')
                ->schema(static::buildConfigurationFields())
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('provider.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('remote_id')
                    ->label('Remote ID')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('configuration.type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('active_from')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('active_until')
                    ->date()
                    ->sortable()
                    ->placeholder('Ongoing'),

                Tables\Columns\IconColumn::make('is_currently_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('manual')
                    ->label('Manual')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('provider_id'),
                        false: fn (Builder $query) => $query->whereNotNull('provider_id'),
                    ),

                Tables\Filters\SelectFilter::make('provider_id')
                    ->label('Provider')
                    ->relationship('provider', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListTariffs::route('/'),
            'create' => Pages\CreateTariff::route('/create'),
            // Avoid collision with legacy/custom admin routes at `/admin/tariffs/{tariff}/edit`.
            'edit' => Pages\EditTariff::route('/{record}/edit-filament'),
        ];
    }
}
