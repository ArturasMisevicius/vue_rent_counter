<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Enums\OrganizationStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('superadmin.organizations.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('superadmin.organizations.columns.slug'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('superadmin.organizations.columns.status'))
                    ->badge()
                    ->color(fn (OrganizationStatus $state): string => $state->badgeColor())
                    ->formatStateUsing(fn (OrganizationStatus $state): string => $state->label()),
                TextColumn::make('owner.name')
                    ->label(__('superadmin.organizations.columns.owner'))
                    ->placeholder(__('superadmin.organizations.empty.owner'))
                    ->toggleable(),
                TextColumn::make('users_count')
                    ->label(__('superadmin.organizations.columns.users_count'))
                    ->sortable(),
                TextColumn::make('properties_count')
                    ->label(__('superadmin.organizations.columns.properties_count'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('subscriptions_count')
                    ->label(__('superadmin.organizations.columns.subscriptions_count'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.organizations.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('superadmin.organizations.columns.status'))
                    ->options(OrganizationStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
