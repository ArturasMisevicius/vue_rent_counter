<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Widgets;

use App\Models\SuperAdminAuditLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

final class RecentActivityWidget extends BaseWidget
{
    protected static ?string $heading = null;
    protected static ?string $pollingInterval = '30s';
    protected int | string | array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('superadmin.dashboard.widgets.recent_activity.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SuperAdminAuditLog::query()
                    ->with(['admin'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label(__('superadmin.audit.fields.action'))
                    ->badge()
                    ->color(fn ($record): string => $record->action->getColor())
                    ->icon(fn ($record): string => $record->action->getIcon())
                    ->formatStateUsing(fn ($record): string => $record->action->getLabel()),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label(__('superadmin.audit.fields.admin_id'))
                    ->default(__('common.unknown'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('target_type')
                    ->label(__('superadmin.audit.fields.target_type'))
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? class_basename($state) : '—'
                    )
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('superadmin.audit.fields.ip_address'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('superadmin.audit.fields.created_at'))
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('superadmin.audit.actions.view'))
                    ->url(fn ($record): string => 
                        route('filament.superadmin.resources.audit-logs.view', $record)
                    ),
            ])
            ->emptyStateHeading(__('superadmin.dashboard.widgets.recent_activity.no_activity'))
            ->emptyStateDescription('')
            ->emptyStateIcon('heroicon-o-clock')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return auth()->user()?->is_super_admin ?? false;
    }
}