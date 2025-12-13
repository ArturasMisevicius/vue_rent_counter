<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources;

use App\Enums\AuditAction;
use App\Filament\Clusters\SuperAdmin;
use App\Filament\Clusters\SuperAdmin\Resources\AuditLogResource\Pages;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\KeyValue;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class AuditLogResource extends Resource
{
    protected static ?string $model = SuperAdminAuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = SuperAdmin::class;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('superadmin.navigation.audit_logs');
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.audit.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.audit.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.audit.sections.basic_info'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('created_at')
                                    ->label(__('superadmin.audit.fields.timestamp'))
                                    ->disabled()
                                    ->native(false),

                                Select::make('action')
                                    ->label(__('superadmin.audit.fields.action'))
                                    ->options(AuditAction::class)
                                    ->disabled(),

                                Select::make('admin_id')
                                    ->label(__('superadmin.audit.fields.admin'))
                                    ->relationship('admin', 'name')
                                    ->disabled(),

                                TextInput::make('ip_address')
                                    ->label(__('superadmin.audit.fields.ip_address'))
                                    ->disabled(),
                            ]),
                    ]),

                Section::make(__('superadmin.audit.sections.target_info'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('target_type')
                                    ->label(__('superadmin.audit.fields.target_type'))
                                    ->disabled(),

                                TextInput::make('target_id')
                                    ->label(__('superadmin.audit.fields.target_id'))
                                    ->disabled(),

                                TextInput::make('tenant_id')
                                    ->label(__('superadmin.audit.fields.tenant_id'))
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make(__('superadmin.audit.sections.details'))
                    ->schema([
                        KeyValue::make('changes')
                            ->label(__('superadmin.audit.fields.changes'))
                            ->disabled(),

                        Textarea::make('user_agent')
                            ->label(__('superadmin.audit.fields.user_agent'))
                            ->rows(3)
                            ->disabled(),

                        TextInput::make('impersonation_session_id')
                            ->label(__('superadmin.audit.fields.impersonation_session'))
                            ->disabled(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('superadmin.audit.fields.timestamp'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M j, Y H:i:s')),

                Tables\Columns\TextColumn::make('action')
                    ->label(__('superadmin.audit.fields.action'))
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label(__('superadmin.audit.fields.admin'))
                    ->description(fn ($record) => $record->admin?->email)
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->placeholder(__('superadmin.audit.values.system')),

                Tables\Columns\TextColumn::make('target_info')
                    ->label(__('superadmin.audit.fields.target'))
                    ->formatStateUsing(function ($record) {
                        if (!$record->target_type) {
                            return '—';
                        }
                        
                        $type = class_basename($record->target_type);
                        $id = $record->target_id;
                        
                        return "{$type} #{$id}";
                    })
                    ->description(function ($record) {
                        if ($record->target_type === User::class && $record->target_id) {
                            $user = User::find($record->target_id);
                            return $user ? $user->email : null;
                        }
                        return null;
                    })
                    ->searchable(['target_type', 'target_id']),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('superadmin.audit.fields.tenant'))
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('superadmin.audit.fields.ip_address'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('impersonation_session_id')
                    ->label(__('superadmin.audit.fields.impersonation'))
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->impersonation_session_id !== null)
                    ->trueIcon('heroicon-o-user-circle')
                    ->falseIcon('')
                    ->trueColor('warning')
                    ->tooltip(__('superadmin.audit.tooltips.impersonated'))
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label(__('superadmin.audit.filters.action'))
                    ->options(AuditAction::class)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('admin_id')
                    ->label(__('superadmin.audit.filters.admin'))
                    ->relationship('admin', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label(__('superadmin.audit.filters.tenant'))
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('target_type')
                    ->label(__('superadmin.audit.filters.target_type'))
                    ->form([
                        Select::make('target_type')
                            ->label(__('superadmin.audit.fields.target_type'))
                            ->options([
                                User::class => __('superadmin.audit.target_types.user'),
                                \App\Models\Organization::class => __('superadmin.audit.target_types.organization'),
                                \App\Models\SystemConfiguration::class => __('superadmin.audit.target_types.system_config'),
                            ])
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['target_type'],
                            fn (Builder $query, $types): Builder => $query->whereIn('target_type', $types),
                        );
                    }),

                Tables\Filters\Filter::make('date_range')
                    ->label(__('superadmin.audit.filters.date_range'))
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('from')
                                    ->label(__('superadmin.audit.fields.from_date')),
                                DatePicker::make('to')
                                    ->label(__('superadmin.audit.fields.to_date')),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('impersonation')
                    ->label(__('superadmin.audit.filters.impersonation'))
                    ->nullable()
                    ->trueLabel(__('superadmin.audit.values.impersonated_only'))
                    ->falseLabel(__('superadmin.audit.values.direct_only'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('impersonation_session_id'),
                        false: fn (Builder $query) => $query->whereNull('impersonation_session_id'),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\Filter::make('has_changes')
                    ->label(__('superadmin.audit.filters.has_changes'))
                    ->query(fn (Builder $query) => $query->whereNotNull('changes'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(__('superadmin.audit.modals.details.heading'))
                    ->modalContent(fn ($record) => view('filament.superadmin.modals.audit-log-details', [
                        'auditLog' => $record,
                    ]))
                    ->modalWidth('4xl'),

                Tables\Actions\Action::make('view_target')
                    ->label(__('superadmin.audit.actions.view_target'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->visible(fn ($record) => $record->target_type && $record->target_id)
                    ->url(function ($record) {
                        if ($record->target_type === User::class) {
                            return SystemUserResource::getUrl('view', ['record' => $record->target_id]);
                        }
                        if ($record->target_type === \App\Models\Organization::class) {
                            return TenantResource::getUrl('view', ['record' => $record->target_id]);
                        }
                        return null;
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label(__('superadmin.audit.bulk_actions.export'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // TODO: Implement export functionality
                            \Filament\Notifications\Notification::make()
                                ->title(__('superadmin.audit.notifications.export_started'))
                                ->body(__('superadmin.audit.notifications.export_started_body', [
                                    'count' => $records->count(),
                                ]))
                                ->info()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->searchable()
            ->persistSearchInSession()
            ->persistFiltersInSession();
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
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Audit logs are created automatically
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Audit logs should not be editable
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Audit logs should not be deletable
    }

    public static function canDeleteAny(): bool
    {
        return false; // Audit logs should not be deletable
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['admin:id,name,email', 'tenant:id,name']);
    }
}