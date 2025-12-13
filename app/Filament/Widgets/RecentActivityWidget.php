<?php

namespace App\Filament\Widgets;

use App\Models\OrganizationActivityLog;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrganizationActivityLog::query()
                    ->with(['organization', 'user'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('superadmin.dashboard.recent_activity_widget.columns.time'))
                    ->dateTime('M d, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('superadmin.dashboard.recent_activity_widget.columns.user'))
                    ->searchable()
                    ->default(__('superadmin.dashboard.recent_activity_widget.default_system', [], false) ?? 'System'),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label(__('superadmin.dashboard.recent_activity_widget.columns.organization'))
                    ->searchable()
                    ->default(__('app.common.na')),

                Tables\Columns\TextColumn::make('action')
                    ->label(__('superadmin.dashboard.recent_activity_widget.columns.action'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'suspended' => 'warning',
                        'reactivated' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('resource_type')
                    ->label(__('superadmin.dashboard.recent_activity_widget.columns.resource'))
                    ->formatStateUsing(fn ($state) => class_basename($state)),

                Tables\Columns\TextColumn::make('resource_id')
                    ->label(__('superadmin.dashboard.recent_activity_widget.columns.id'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('superadmin.dashboard.recent_activity_widget.columns.details'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('superadmin.dashboard.recent_activity_widget.modal_heading'))
                    ->modalContent(fn (OrganizationActivityLog $record): \Illuminate\Contracts\View\View => view(
                        'filament.widgets.activity-details',
                        ['record' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('subscriptions.actions.close')),
            ])
            ->heading(__('superadmin.dashboard.recent_activity_widget.heading'))
            ->description(__('superadmin.dashboard.recent_activity_widget.description'))
            ->emptyStateHeading(__('superadmin.dashboard.recent_activity_widget.empty_heading'))
            ->emptyStateDescription(__('superadmin.dashboard.recent_activity_widget.empty_description'))
            ->emptyStateIcon('heroicon-o-clock')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
