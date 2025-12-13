<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Services\DashboardCacheService;
use App\Models\OrganizationActivityLog;

/**
 * Recent Activity Widget with optimized loading
 * 
 * Shows recent superadmin and organization actions with performance optimizations
 */
class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 6;
    
    // Enable lazy loading for better performance
    protected static bool $isLazy = true;
    
    // Polling interval - refresh every 2 minutes
    protected static ?string $pollingInterval = '120s';
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Optimized query with eager loading
                OrganizationActivityLog::query()
                    ->with(['organization:id,name,slug', 'user:id,name,email'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                    
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->limit(20)
                    ->tooltip(function (OrganizationActivityLog $record): ?string {
                        return $record->organization?->name;
                    })
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->limit(15)
                    ->tooltip(function (OrganizationActivityLog $record): ?string {
                        return $record->user?->name . ' (' . $record->user?->email . ')';
                    })
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                    
                Tables\Columns\BadgeColumn::make('action')
                    ->label('Action')
                    ->colors([
                        'success' => ['created', 'updated', 'activated'],
                        'warning' => ['suspended', 'deactivated'],
                        'danger' => ['deleted', 'failed'],
                        'primary' => ['login', 'logout'],
                    ])
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                    
                Tables\Columns\TextColumn::make('resource_type')
                    ->label('Resource')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Activity Details')
                    ->modalContent(function (OrganizationActivityLog $record): string {
                        return view('filament.widgets.activity-details', [
                            'record' => $record
                        ])->render();
                    }),
            ])
            ->heading('Recent Activity')
            ->description('Latest 10 activities across all organizations')
            ->emptyStateHeading('No recent activity')
            ->emptyStateDescription('No activities have been recorded recently.')
            ->defaultPaginationPageOption(10)
            ->poll('120s'); // Refresh every 2 minutes
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}