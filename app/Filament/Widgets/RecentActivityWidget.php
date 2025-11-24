<?php

namespace App\Filament\Widgets;

use App\Models\OrganizationActivityLog;
use Filament\Tables;
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
                    ->label('Time')
                    ->dateTime('M d, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->default('System'),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->default('N/A'),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
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
                    ->label('Resource')
                    ->formatStateUsing(fn ($state) => class_basename($state)),

                Tables\Columns\TextColumn::make('resource_id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Activity Details')
                    ->modalContent(fn (OrganizationActivityLog $record): \Illuminate\Contracts\View\View => view(
                        'filament.widgets.activity-details',
                        ['record' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->heading('Recent Activity')
            ->description('Last 10 actions across all organizations')
            ->emptyStateHeading('No recent activity')
            ->emptyStateDescription('Activity logs will appear here')
            ->emptyStateIcon('heroicon-o-clock')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
