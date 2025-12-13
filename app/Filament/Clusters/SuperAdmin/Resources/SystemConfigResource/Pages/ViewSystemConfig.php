<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\SystemConfigResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\SystemConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Components\Grid;

final class ViewSystemConfig extends ViewRecord
{
    protected static string $resource = SystemConfigResource::class;

    public function getTitle(): string
    {
        return __('superadmin.config.pages.view.title', [
            'key' => $this->getRecord()->key,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('superadmin.config.actions.edit'))
                ->icon('heroicon-o-pencil-square'),
            Actions\DeleteAction::make()
                ->label(__('superadmin.config.actions.delete'))
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.config.modals.delete.heading'))
                ->modalDescription(__('superadmin.config.modals.delete.description'))
                ->modalSubmitActionLabel(__('superadmin.config.modals.delete.confirm'))
                ->successNotificationTitle(__('superadmin.config.notifications.deleted'))
                ->after(function () {
                    // Log the configuration deletion
                    \App\Models\SuperAdminAuditLog::create([
                        'admin_id' => auth()->id(),
                        'action' => \App\Enums\AuditAction::SYSTEM_CONFIG_DELETED,
                        'target_type' => \App\Models\SystemConfiguration::class,
                        'target_id' => $this->getRecord()->id,
                        'changes' => [
                            'key' => $this->getRecord()->key,
                            'category' => $this->getRecord()->category,
                        ],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.config.sections.basic_info'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('key')
                                ->label(__('superadmin.config.fields.key'))
                                ->copyable()
                                ->icon('heroicon-o-key'),
                            TextEntry::make('category')
                                ->label(__('superadmin.config.fields.category'))
                                ->badge()
                                ->color('info'),
                        ]),
                        TextEntry::make('description')
                            ->label(__('superadmin.config.fields.description'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('superadmin.config.sections.value'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('type')
                                ->label(__('superadmin.config.fields.type'))
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'string' => 'gray',
                                    'integer' => 'blue',
                                    'float' => 'cyan',
                                    'boolean' => 'green',
                                    'array' => 'orange',
                                    'json' => 'purple',
                                    default => 'gray',
                                }),
                            TextEntry::make('is_sensitive')
                                ->label(__('superadmin.config.fields.is_sensitive'))
                                ->badge()
                                ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                                ->formatStateUsing(fn (bool $state): string => 
                                    $state ? __('common.yes') : __('common.no')
                                ),
                        ]),
                        TextEntry::make('value')
                            ->label(__('superadmin.config.fields.value'))
                            ->columnSpanFull()
                            ->formatStateUsing(function (mixed $state, $record): string {
                                if ($record->is_sensitive) {
                                    return '••••••••';
                                }

                                if (is_bool($state)) {
                                    return $state ? __('common.yes') : __('common.no');
                                }

                                if (is_array($state)) {
                                    return json_encode($state, JSON_PRETTY_PRINT);
                                }

                                return (string) $state;
                            })
                            ->copyable(fn ($record): bool => !$record->is_sensitive),
                    ]),

                Section::make(__('superadmin.config.sections.metadata'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')
                                ->label(__('superadmin.config.fields.created_at'))
                                ->dateTime()
                                ->since(),
                            TextEntry::make('updated_at')
                                ->label(__('superadmin.config.fields.updated_at'))
                                ->dateTime()
                                ->since(),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('createdBy.name')
                                ->label(__('superadmin.config.fields.created_by'))
                                ->default(__('common.unknown')),
                            TextEntry::make('updatedBy.name')
                                ->label(__('superadmin.config.fields.updated_by'))
                                ->default(__('common.unknown')),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}