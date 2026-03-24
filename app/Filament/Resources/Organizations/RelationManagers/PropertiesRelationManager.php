<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Enums\PropertyType;
use App\Filament\Actions\Admin\Properties\CreatePropertyAction;
use App\Filament\Actions\Admin\Properties\DeletePropertyAction;
use App\Filament\Actions\Admin\Properties\UpdatePropertyAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Properties';
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('properties_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withWorkspaceSummary()->ordered())
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_number')
                    ->label('Unit')
                    ->sortable(),
                TextColumn::make('building.name')
                    ->label('Building')
                    ->searchable(),
                TextColumn::make('currentAssignment.tenant.name')
                    ->label('Tenant')
                    ->default('Unassigned')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create property')
                    ->authorize(function (): bool {
                        $user = Auth::guard()->user();

                        return $user instanceof User
                            && Gate::forUser($user)->allows('create', Property::class);
                    })
                    ->form([
                        Select::make('building_id')
                            ->label('Building')
                            ->options(fn (): array => Building::query()
                                ->forOrganization($this->getOwnerRecord()->getKey())
                                ->ordered()
                                ->pluck('name', 'id')
                                ->all())
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('unit_number')
                            ->label('Unit')
                            ->required()
                            ->maxLength(50),
                        Select::make('type')
                            ->label('Type')
                            ->options(PropertyType::options())
                            ->required(),
                        TextInput::make('floor_area_sqm')
                            ->label('Floor area (sqm)')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->using(fn (array $data): Property => app(CreatePropertyAction::class)->handle($this->getOwnerRecord(), $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(function (Property $record): bool {
                        $user = Auth::guard()->user();

                        return $user instanceof User
                            && Gate::forUser($user)->allows('update', $record);
                    })
                    ->form([
                        Select::make('building_id')
                            ->label('Building')
                            ->options(fn (): array => Building::query()
                                ->forOrganization($this->getOwnerRecord()->getKey())
                                ->ordered()
                                ->pluck('name', 'id')
                                ->all())
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('unit_number')
                            ->label('Unit')
                            ->required()
                            ->maxLength(50),
                        Select::make('type')
                            ->label('Type')
                            ->options(PropertyType::options())
                            ->required(),
                        TextInput::make('floor_area_sqm')
                            ->label('Floor area (sqm)')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->using(fn (Property $record, array $data): Property => app(UpdatePropertyAction::class)->handle($record, $data)),
                DeleteAction::make()
                    ->authorize(function (Property $record): bool {
                        $user = Auth::guard()->user();

                        return $user instanceof User
                            && Gate::forUser($user)->allows('delete', $record);
                    })
                    ->using(fn (Property $record) => app(DeletePropertyAction::class)->handle($record)),
            ])
            ->defaultSort('name');
    }
}
