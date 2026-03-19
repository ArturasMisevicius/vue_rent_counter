<?php

declare(strict_types=1);

namespace App\Filament\Superadmin\Widgets;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Recent Users Widget
 *
 * Displays recently registered users across all organizations
 * for superadmin monitoring and management.
 */
final class RecentUsersWidget extends BaseWidget
{
    /**
     * Widget heading.
     */
    protected static ?string $heading = null;

    /**
     * Widget column span configuration.
     *
     * @var string
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * Widget sort order.
     */
    protected static ?int $sort = 2;

    /**
     * Get the widget heading.
     */
    public function getHeading(): string
    {
        return __('app.widgets.recent_users');
    }

    /**
     * Configure the table for the widget.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('app.labels.email'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('role')
                    ->label(__('app.labels.role'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'superadmin' => 'danger',
                        'admin' => 'warning',
                        'manager' => 'info',
                        'tenant' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label(__('app.labels.registered'))
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record): string => $record->created_at->format('l, F j, Y')
                    ),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('app.actions.view'))
                    ->icon('heroicon-m-eye')
                    ->action(function (User $record) {
                        // For now, just show a notification
                        Notification::make()
                            ->title('User Details')
                            ->body("Viewing user: {$record->name} ({$record->email})")
                            ->info()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }

    /**
     * Get the base query for the table.
     *
     * @return Builder<User>
     */
    protected function getTableQuery(): Builder
    {
        return User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->latest('created_at');
    }
}
