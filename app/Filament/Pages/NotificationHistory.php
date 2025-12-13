<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;

class NotificationHistory extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clock';
    }

    protected static ?string $navigationLabel = 'Notification History';

    protected string $view = 'filament.pages.notification-history';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PlatformNotification::query())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (PlatformNotification $record): string => 
                        \Illuminate\Support\Str::limit(strip_tags($record->message), 100)),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'scheduled',
                        'success' => 'sent',
                        'danger' => 'failed',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('target_type')
                    ->label('Target')
                    ->formatStateUsing(function (string $state, PlatformNotification $record) {
                        return match ($state) {
                            'all' => 'All Organizations',
                            'plan' => 'Plans: ' . implode(', ', $record->target_criteria ?? []),
                            'organization' => count($record->target_criteria ?? []) . ' Organizations',
                            default => $state,
                        };
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('recipients_count')
                    ->label('Recipients')
                    ->getStateUsing(fn (PlatformNotification $record) => $record->getTotalRecipients())
                    ->sortable(false),

                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Sent')
                    ->getStateUsing(fn (PlatformNotification $record) => $record->getSentCount())
                    ->color('success')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->getStateUsing(fn (PlatformNotification $record) => $record->getFailedCount())
                    ->color('danger')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('read_count')
                    ->label('Read')
                    ->getStateUsing(fn (PlatformNotification $record) => $record->getReadCount())
                    ->color('info')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('delivery_rate')
                    ->label('Delivery %')
                    ->getStateUsing(function (PlatformNotification $record) {
                        if ($record->status !== 'sent') {
                            return '-';
                        }
                        return number_format($record->getDeliveryRate(), 1) . '%';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('read_rate')
                    ->label('Read %')
                    ->getStateUsing(function (PlatformNotification $record) {
                        if ($record->status !== 'sent') {
                            return '-';
                        }
                        return number_format($record->getReadRate(), 1) . '%';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('target_type')
                    ->label('Target Type')
                    ->options([
                        'all' => 'All Organizations',
                        'plan' => 'Specific Plans',
                        'organization' => 'Individual Organizations',
                    ]),

                Tables\Filters\SelectFilter::make('creator')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('sent_at')
                    ->form([
                        Forms\Components\DatePicker::make('sent_from')
                            ->label('Sent From'),
                        Forms\Components\DatePicker::make('sent_until')
                            ->label('Sent Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sent_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '>=', $date),
                            )
                            ->when(
                                $data['sent_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
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
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (PlatformNotification $record): string => 
                        route('filament.admin.resources.platform-notifications.view', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function (PlatformNotification $record) {
                                if ($record->isDraft()) {
                                    $record->delete();
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // We can add notification statistics widgets here
        ];
    }

    public function getTitle(): string
    {
        return 'Notification History';
    }

    public function getHeading(): string
    {
        return 'Platform Notification History';
    }

    public function getSubheading(): ?string
    {
        return 'View all sent notifications with delivery status and read receipts';
    }
}