<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Actions\SendPlatformNotificationAction;
use App\Filament\Resources\PlatformNotificationResource\Pages;
use App\Models\PlatformNotification;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformNotificationResource extends Resource
{
    protected static ?string $model = PlatformNotification::class;

    protected static ?string $navigationLabel = 'Platform Notifications';

    protected static ?string $modelLabel = 'Platform Notification';

    protected static ?string $pluralModelLabel = 'Platform Notifications';

    protected static ?int $navigationSort = 5;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system_management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Notification Details')
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

                Forms\Components\Section::make('Targeting')
                    ->schema([
                        Forms\Components\Select::make('target_type')
                            ->label('Target Audience')
                            ->required()
                            ->options([
                                'all' => 'All Organizations',
                                'plan' => 'Specific Plans',
                                'organization' => 'Individual Organizations',
                            ])
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('target_criteria', null)),

                        Forms\Components\Select::make('target_criteria')
                            ->label('Target Selection')
                            ->multiple()
                            ->searchable()
                            ->options(function (callable $get) {
                                $targetType = $get('target_type');
                                
                                return match ($targetType) {
                                    'plan' => [
                                        'basic' => 'Basic Plan',
                                        'professional' => 'Professional Plan',
                                        'enterprise' => 'Enterprise Plan',
                                    ],
                                    'organization' => \App\Models\Organization::active()
                                        ->pluck('name', 'id')
                                        ->toArray(),
                                    default => [],
                                };
                            })
                            ->visible(fn (callable $get) => in_array($get('target_type'), ['plan', 'organization']))
                            ->required(fn (callable $get) => in_array($get('target_type'), ['plan', 'organization'])),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'scheduled' => 'Scheduled',
                                'sent' => 'Sent',
                                'failed' => 'Failed',
                            ])
                            ->default('draft')
                            ->live(),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Schedule Date & Time')
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

                Tables\Columns\TextColumn::make('delivery_rate')
                    ->label('Delivery Rate')
                    ->getStateUsing(function (PlatformNotification $record) {
                        if ($record->status !== 'sent') {
                            return '-';
                        }
                        return number_format($record->getDeliveryRate(), 1) . '%';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (PlatformNotification $record) => $record->isDraft()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (PlatformNotification $record) => $record->isDraft()),
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