<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Actions\SendPlatformNotificationAction;
use App\Filament\Resources\PlatformNotificationResource\Pages;
use App\Models\Organization;
use App\Models\PlatformNotification;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformNotificationResource extends Resource
{
    protected static ?string $model = PlatformNotification::class;

    protected static ?int $navigationSort = 5;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('platform_notifications.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('platform_notifications.models.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('platform_notifications.models.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(__('platform_notifications.headings.notification_details'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('message')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'link',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                            ]),
                    ]),

                Forms\Components\Section::make(__('platform_notifications.headings.targeting'))
                    ->schema([
                        Forms\Components\Select::make('target_type')
                            ->label(__('platform_notifications.labels.target_audience'))
                            ->required()
                            ->options([
                                'all' => __('platform_notifications.values.target_type.all'),
                                'plan' => __('platform_notifications.values.target_type.plan'),
                                'organization' => __('platform_notifications.values.target_type.organization'),
                            ])
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('target_criteria', null)),

                        Forms\Components\Select::make('target_criteria')
                            ->label(__('platform_notifications.labels.target_selection'))
                            ->multiple()
                            ->searchable()
                            ->options(function (callable $get) {
                                $targetType = $get('target_type');

                                return match ($targetType) {
                                    'plan' => [
                                        'basic' => __('platform_notifications.values.plan.basic'),
                                        'professional' => __('platform_notifications.values.plan.professional'),
                                        'enterprise' => __('platform_notifications.values.plan.enterprise'),
                                    ],
                                    'organization' => Organization::active()
                                        ->pluck('name', 'id')
                                        ->toArray(),
                                    default => [],
                                };
                            })
                            ->visible(fn (callable $get) => in_array($get('target_type'), ['plan', 'organization']))
                            ->required(fn (callable $get) => in_array($get('target_type'), ['plan', 'organization'])),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('platform_notifications.headings.scheduling'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'draft' => __('platform_notifications.values.status.draft'),
                                'scheduled' => __('platform_notifications.values.status.scheduled'),
                                'sent' => __('platform_notifications.values.status.sent'),
                                'failed' => __('platform_notifications.values.status.failed'),
                            ])
                            ->default('draft')
                            ->live(),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label(__('platform_notifications.labels.schedule_date_time'))
                            ->visible(fn (callable $get) => $get('status') === 'scheduled')
                            ->required(fn (callable $get) => $get('status') === 'scheduled')
                            ->minDate(now())
                            ->seconds(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

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

                Tables\Columns\TextColumn::make('delivery_rate')
                    ->label(__('platform_notifications.labels.delivery_rate'))
                    ->getStateUsing(function (PlatformNotification $record) {
                        if ($record->status !== 'sent') {
                            return __('platform_notifications.placeholders.empty_rate');
                        }

                        return number_format($record->getDeliveryRate(), 1).'%';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('platform_notifications.labels.created_by'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label(__('platform_notifications.labels.scheduled'))
                    ->dateTime()
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

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
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
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make()
                    ->visible(fn (PlatformNotification $record) => $record->isDraft()),
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn (PlatformNotification $record) => $record->isDraft()),
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
            ->headerActions([
                SendPlatformNotificationAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PlatformNotificationResource\RelationManagers\RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatformNotifications::route('/'),
            'create' => Pages\CreatePlatformNotification::route('/create'),
            'view' => Pages\ViewPlatformNotification::route('/{record}'),
            'edit' => Pages\EditPlatformNotification::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }
}
