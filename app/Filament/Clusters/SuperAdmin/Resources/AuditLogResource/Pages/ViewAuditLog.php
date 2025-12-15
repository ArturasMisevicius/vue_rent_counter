<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\AuditLogResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\AuditLogResource;
use App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource;
use App\Filament\Clusters\SuperAdmin\Resources\TenantResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('superadmin.audit.sections.basic_info'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')
                                ->label(__('superadmin.audit.fields.timestamp'))
                                ->dateTime()
                                ->since()
                                ->icon('heroicon-o-clock'),

                            TextEntry::make('action')
                                ->label(__('superadmin.audit.fields.action'))
                                ->badge()
                                ->color(fn ($state) => $state->getColor())
                                ->formatStateUsing(fn ($state) => $state->getLabel()),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('admin.name')
                                ->label(__('superadmin.audit.fields.admin'))
                                ->description(fn ($record) => $record->admin?->email)
                                ->icon('heroicon-o-user')
                                ->placeholder(__('superadmin.audit.values.system')),

                            TextEntry::make('ip_address')
                                ->label(__('superadmin.audit.fields.ip_address'))
                                ->icon('heroicon-o-globe-alt')
                                ->fontFamily('mono')
                                ->copyable(),
                        ]),
                    ])
                    ->columns(1),

                Section::make(__('superadmin.audit.sections.target_info'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('target_type')
                                ->label(__('superadmin.audit.fields.target_type'))
                                ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '—')
                                ->icon('heroicon-o-cube'),

                            TextEntry::make('target_id')
                                ->label(__('superadmin.audit.fields.target_id'))
                                ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '—')
                                ->icon('heroicon-o-hashtag'),

                            TextEntry::make('tenant.name')
                                ->label(__('superadmin.audit.fields.tenant'))
                                ->badge()
                                ->color('info')
                                ->icon('heroicon-o-building-office')
                                ->url(fn ($record) => $record->tenant ? 
                                    TenantResource::getUrl('view', ['record' => $record->tenant]) : null),
                        ]),
                    ])
                    ->columns(1)
                    ->visible(fn ($record) => $record->target_type || $record->tenant_id),

                Section::make(__('superadmin.audit.sections.impersonation_info'))
                    ->schema([
                        Grid::make(2)->schema([
                            IconEntry::make('impersonation_session_id')
                                ->label(__('superadmin.audit.fields.impersonation_status'))
                                ->boolean()
                                ->getStateUsing(fn ($record) => $record->impersonation_session_id !== null)
                                ->trueIcon('heroicon-o-user-circle')
                                ->falseIcon('heroicon-o-user')
                                ->trueColor('warning')
                                ->falseColor('gray'),

                            TextEntry::make('impersonation_session_id')
                                ->label(__('superadmin.audit.fields.session_id'))
                                ->fontFamily('mono')
                                ->copyable()
                                ->placeholder('—'),
                        ]),
                    ])
                    ->columns(1)
                    ->visible(fn ($record) => $record->impersonation_session_id !== null),

                Section::make(__('superadmin.audit.fields.changes'))
                    ->schema([
                        ViewEntry::make('changes')
                            ->label('')
                            ->view('filament.superadmin.components.audit-changes')
                            ->viewData(fn ($record) => [
                                'changes' => $record->changes,
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record->changes || count($record->changes) === 0)
                    ->visible(fn ($record) => $record->changes && count($record->changes) > 0),

                Section::make(__('superadmin.audit.sections.technical_details'))
                    ->schema([
                        TextEntry::make('user_agent')
                            ->label(__('superadmin.audit.fields.user_agent'))
                            ->fontFamily('mono')
                            ->wrap()
                            ->placeholder('—'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record->user_agent),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_target')
                ->label(__('superadmin.audit.actions.view_target'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->visible(fn () => $this->getRecord()->target_type && $this->getRecord()->target_id)
                ->url(function () {
                    $record = $this->getRecord();
                    
                    if ($record->target_type === User::class) {
                        return SystemUserResource::getUrl('view', ['record' => $record->target_id]);
                    }
                    if ($record->target_type === \App\Models\Organization::class) {
                        return TenantResource::getUrl('view', ['record' => $record->target_id]);
                    }
                    return null;
                })
                ->openUrlInNewTab(),

            Action::make('view_admin')
                ->label(__('superadmin.audit.actions.view_admin'))
                ->icon('heroicon-o-user')
                ->color('gray')
                ->visible(fn () => $this->getRecord()->admin_id)
                ->url(fn () => SystemUserResource::getUrl('view', ['record' => $this->getRecord()->admin_id]))
                ->openUrlInNewTab(),

            Action::make('view_tenant')
                ->label(__('superadmin.audit.actions.view_tenant'))
                ->icon('heroicon-o-building-office')
                ->color('primary')
                ->visible(fn () => $this->getRecord()->tenant_id)
                ->url(fn () => TenantResource::getUrl('view', ['record' => $this->getRecord()->tenant_id]))
                ->openUrlInNewTab(),

            Action::make('back_to_list')
                ->label(__('superadmin.audit.actions.back_to_list'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::$resource::getUrl('index')),
        ];
    }
}
