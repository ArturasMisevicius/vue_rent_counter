<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingGenerationLogs\Tables;

use App\Models\Organization;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BillingGenerationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('billingPeriod.name')
                    ->label(__('admin.billing_periods.singular'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source')
                    ->label(__('admin.billing_generation.fields.source'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.billing_generation.fields.status'))
                    ->badge()
                    ->sortable(),
                IconColumn::make('dry_run')
                    ->label(__('admin.billing_generation.fields.dry_run'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_count')
                    ->label(__('admin.billing_generation.fields.created_count'))
                    ->sortable()
                    ->badge(),
                TextColumn::make('skipped_count')
                    ->label(__('admin.billing_generation.fields.skipped_count'))
                    ->sortable()
                    ->badge(),
                TextColumn::make('warning_count')
                    ->label(__('admin.billing_generation.fields.warning_count'))
                    ->sortable()
                    ->badge(),
                TextColumn::make('error_count')
                    ->label(__('admin.billing_generation.fields.error_count'))
                    ->sortable()
                    ->badge(),
                TextColumn::make('notified_tenants_count')
                    ->label(__('admin.billing_generation.fields.notified_tenants_count'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
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
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
