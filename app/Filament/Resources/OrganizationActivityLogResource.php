<?php

namespace App\Filament\Resources;


use App\Filament\Resources\OrganizationActivityLogResource\Pages;
use App\Models\OrganizationActivityLog;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationActivityLogResource extends Resource
{
    protected static ?string $model = OrganizationActivityLog::class;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('audit.navigation');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('action')
                    ->disabled(),
                Forms\Components\TextInput::make('resource_type')
                    ->disabled(),
                Forms\Components\TextInput::make('resource_id')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('audit.labels.timestamp'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('organization.name')
                    ->label(__('audit.labels.organization'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->organization_id 
                        ? route('filament.admin.resources.organizations.view', ['record' => $record->organization_id])
                        : null)
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('audit.labels.user'))
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->default(__('app.common.na')),
                
                Tables\Columns\TextColumn::make('action')
                    ->label(__('audit.labels.action'))
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'create') => 'success',
                        str_contains(strtolower($state), 'update') || str_contains(strtolower($state), 'edit') => 'info',
                        str_contains(strtolower($state), 'delete') || str_contains(strtolower($state), 'remove') => 'danger',
                        str_contains(strtolower($state), 'suspend') => 'warning',
                        str_contains(strtolower($state), 'reactivate') || str_contains(strtolower($state), 'activate') => 'success',
                        str_contains(strtolower($state), 'impersonate') => 'warning',
                        str_contains(strtolower($state), 'view') || str_contains(strtolower($state), 'access') => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('resource_type')
                    ->label(__('audit.labels.resource_type'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : __('app.common.na')),
                
                Tables\Columns\TextColumn::make('resource_id')
                    ->label(__('audit.labels.resource_id'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('audit.labels.ip_address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('user_agent')
                    ->label(__('audit.labels.user_agent'))
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('organization_id')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('audit.labels.organization'))
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('audit.labels.user'))
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'create' => __('audit.filters.create'),
                        'update' => __('audit.filters.update'),
                        'delete' => __('audit.filters.delete'),
                        'view' => __('audit.filters.view'),
                        'suspend' => 'Suspend',
                        'reactivate' => 'Reactivate',
                        'impersonate' => 'Impersonate',
                    ])
                    ->label(__('audit.labels.action_type'))
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }
                        
                        return $query->where(function (Builder $query) use ($data) {
                            foreach ($data['values'] as $action) {
                                $query->orWhere('action', 'like', "%{$action}%");
                            }
                        });
                    }),
                
                Tables\Filters\SelectFilter::make('resource_type')
                    ->options(function () {
                        return OrganizationActivityLog::query()
                            ->distinct()
                            ->pluck('resource_type', 'resource_type')
                            ->map(fn ($type) => class_basename($type))
                            ->toArray();
                    })
                    ->label(__('audit.labels.resource_type'))
                    ->multiple(),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('audit.filters.from'))
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('audit.filters.until'))
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From: ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until: ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_selected_csv')
                        ->label('Export Selected (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($records) {
                            return static::exportRecordsToCsv($records);
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('export_selected_json')
                        ->label('Export Selected (JSON)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            return static::exportRecordsToJson($records);
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Activity Logs')
                        ->modalDescription('Are you sure you want to delete these activity logs? This action cannot be undone.')
                        ->modalSubmitActionLabel('Delete'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
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
            'index' => Pages\ListOrganizationActivityLogs::route('/'),
            'view' => Pages\ViewOrganizationActivityLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
    
    public static function exportRecordsToCsv($records)
    {
        $filename = 'activity-logs-selected-' . now()->format('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Timestamp',
                'Organization',
                'User',
                'Action',
                'Resource Type',
                'Resource ID',
                'IP Address',
                'User Agent',
                'Metadata',
            ]);
            
            // Add data rows
            foreach ($records as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->organization?->name ?? 'N/A',
                    $log->user?->name ?? 'N/A',
                    $log->action,
                    $log->resource_type ? class_basename($log->resource_type) : 'N/A',
                    $log->resource_id ?? 'N/A',
                    $log->ip_address ?? 'N/A',
                    $log->user_agent ?? 'N/A',
                    $log->metadata ? json_encode($log->metadata) : 'N/A',
                ]);
            }
            
            fclose($file);
        };
        
        return \Illuminate\Support\Facades\Response::stream($callback, 200, $headers);
    }
    
    public static function exportRecordsToJson($records)
    {
        $filename = 'activity-logs-selected-' . now()->format('Y-m-d-His') . '.json';
        
        $data = $records->map(function ($log) {
            return [
                'id' => $log->id,
                'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
                'organization' => [
                    'id' => $log->organization_id,
                    'name' => $log->organization?->name,
                ],
                'user' => [
                    'id' => $log->user_id,
                    'name' => $log->user?->name,
                ],
                'action' => $log->action,
                'resource' => [
                    'type' => $log->resource_type,
                    'id' => $log->resource_id,
                ],
                'request' => [
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                ],
                'metadata' => $log->metadata,
            ];
        });
        
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        return \Illuminate\Support\Facades\Response::make(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            200,
            $headers
        );
    }
}
