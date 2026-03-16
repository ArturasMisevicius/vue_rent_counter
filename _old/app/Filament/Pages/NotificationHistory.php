<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\PlatformNotification;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NotificationHistory extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clock';
    }

    protected string $view = 'filament.pages.notification-history';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('platform_notifications.navigation.history');
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
                    ->description(fn (PlatformNotification $record): string => Str::limit(strip_tags($record->message), 100)),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'scheduled',
                        'success' => 'sent',
                        'danger' => 'failed',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('target_type')
                    ->label(__('platform_notifications.labels.target'))
                    ->formatStateUsing(function (string $state, PlatformNotification $record) {
                        return match ($state) {
                            'all' => __('platform_notifications.values.target_type.all'),
                            'plan' => __('platform_notifications.messages.plans', ['plans' => implode(', ', $record->target_criteria ?? [])]),
                            'organization' => __('platform_notifications.messages.organizations_count', ['count' => count($record->target_criteria ?? [])]),
                            default => $state,
                        };
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('recipients_count')
                    ->label(__('platform_notifications.labels.recipients'))
                    ->getStateUsing(fn (PlatformNotification $record) => $record->getTotalRecipients())
                    ->sortable(false),

                Tables\Columns\TextColumn::make('sent_count')
                    ->label(__('platform_notifications.labels.sent'))
                    ->getStateUsing(fn (PlatformNotification $record) => $record->getSentCount())
                    ->color('success')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label(__('platform_notifications.labels.failed'))
                    ->getStateUsing(fn (PlatformNotification $record) => $record->getFailedCount())
                    ->color('danger')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('read_count')
                    ->label(__('platform_notifications.labels.read'))
                    ->getStateUsing(fn (PlatformNotification $record) => $record->getReadCount())
                    ->color('info')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('delivery_rate')
                    ->label(__('platform_notifications.labels.delivery_percent'))
                    ->getStateUsing(function (PlatformNotification $record) {
                        if ($record->status !== 'sent') {
                            return __('platform_notifications.placeholders.empty_rate');
                        }

                        return number_format($record->getDeliveryRate(), 1).'%';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('read_rate')
                    ->label(__('platform_notifications.labels.read_percent'))
                    ->getStateUsing(function (PlatformNotification $record) {
                        if ($record->status !== 'sent') {
                            return __('platform_notifications.placeholders.empty_rate');
                        }

                        return number_format($record->getReadRate(), 1).'%';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('platform_notifications.labels.created_by'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label(__('platform_notifications.labels.sent'))
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
                        'draft' => __('platform_notifications.values.status.draft'),
                        'scheduled' => __('platform_notifications.values.status.scheduled'),
                        'sent' => __('platform_notifications.values.status.sent'),
                        'failed' => __('platform_notifications.values.status.failed'),
                    ]),

                Tables\Filters\SelectFilter::make('target_type')
                    ->label(__('platform_notifications.labels.target_type'))
                    ->options([
                        'all' => __('platform_notifications.values.target_type.all'),
                        'plan' => __('platform_notifications.values.target_type.plan'),
                        'organization' => __('platform_notifications.values.target_type.organization'),
                    ]),

                Tables\Filters\SelectFilter::make('creator')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('sent_at')
                    ->form([
                        Forms\Components\DatePicker::make('sent_from')
                            ->label(__('platform_notifications.labels.sent_from')),
                        Forms\Components\DatePicker::make('sent_until')
                            ->label(__('platform_notifications.labels.sent_until')),
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
                            ->label(__('platform_notifications.labels.created_from')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('platform_notifications.labels.created_until')),
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
                \Filament\Actions\ViewAction::make()
                    ->url(fn (PlatformNotification $record): string => route('filament.admin.resources.platform-notifications.view', $record)),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
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
            ->poll('60s'); // Auto-refresh every 60 seconds
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // We can add notification statistics widgets here
        ];
    }

    public function getTitle(): string
    {
        return __('platform_notifications.navigation.history');
    }

    public function getHeading(): string
    {
        return __('platform_notifications.headings.history');
    }

    public function getSubheading(): ?string
    {
        return __('platform_notifications.descriptions.history');
    }
}
