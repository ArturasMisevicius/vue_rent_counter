<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('users.sections.user_details'))
                    ->schema([
                        Components\TextEntry::make('name')
                            ->label(__('users.labels.name'))
                            ->icon('heroicon-o-user')
                            ->copyable(),
                        
                        Components\TextEntry::make('email')
                            ->label(__('users.labels.email'))
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->copyMessage(__('users.tooltips.copy_email')),
                    ])
                    ->columns(2),

                Section::make(__('users.sections.role_and_access'))
                    ->schema([
                        Components\TextEntry::make('role')
                            ->label(__('users.labels.role'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                \App\Enums\UserRole::SUPERADMIN => 'danger',
                                \App\Enums\UserRole::ADMIN => 'warning',
                                \App\Enums\UserRole::MANAGER => 'info',
                                \App\Enums\UserRole::TENANT => 'success',
                            })
                            ->formatStateUsing(fn ($state) => $state->label()),
                        
                        Components\TextEntry::make('parentUser.name')
                            ->label(__('users.labels.tenant'))
                            ->placeholder(__('app.common.dash'))
                            ->visible(fn ($record) => $record->tenant_id !== null),
                        
                        Components\IconEntry::make('is_active')
                            ->label(__('users.labels.is_active'))
                            ->boolean(),
                    ])
                    ->columns(3),

                Section::make(__('app.common.metadata'))
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label(__('app.common.created_at'))
                            ->dateTime()
                            ->icon('heroicon-o-calendar'),
                        
                        Components\TextEntry::make('updated_at')
                            ->label(__('app.common.updated_at'))
                            ->dateTime()
                            ->icon('heroicon-o-clock')
                            ->since(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
