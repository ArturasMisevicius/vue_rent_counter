<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraChargeTypes\Tables;

use App\Filament\Resources\ExtraChargeTypes\ExtraChargeTypeResource;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\ExtraChargeType;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExtraChargeTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.extra_charge_types.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.extra_charge_types.fields.type'))
                    ->state(fn (ExtraChargeType $record): string => $record->typeLabel())
                    ->badge()
                    ->sortable(),
                TextColumn::make('default_amount')
                    ->label(__('admin.extra_charge_types.fields.default_amount'))
                    ->state(fn (ExtraChargeType $record): string => EuMoneyFormatter::format($record->default_amount, $record->currency))
                    ->sortable(),
                IconColumn::make('is_recurring')
                    ->label(__('admin.extra_charge_types.fields.is_recurring'))
                    ->boolean(),
                IconColumn::make('tenant_visible_by_default')
                    ->label(__('admin.extra_charge_types.fields.tenant_visible_by_default'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('requires_comment')
                    ->label(__('admin.extra_charge_types.fields.requires_comment'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('requires_attachment')
                    ->label(__('admin.extra_charge_types.fields.requires_attachment'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('admin.extra_charge_types.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->forOrganization((int) $data['value'])
                        : $query),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->authorize(fn (ExtraChargeType $record): bool => ExtraChargeTypeResource::canEdit($record)),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->authorize(fn (ExtraChargeType $record): bool => ExtraChargeTypeResource::canDelete($record)),
            ])
            ->defaultSort('name');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
