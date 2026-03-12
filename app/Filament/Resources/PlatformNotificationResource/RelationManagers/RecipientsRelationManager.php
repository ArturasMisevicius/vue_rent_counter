<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformNotificationResource\RelationManagers;

use App\Models\PlatformNotificationRecipient;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $recordTitleAttribute = 'email';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                Tables\Columns\TextColumn::make('organization.name')
                    ->label(__('common.organization'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('delivery_status')
                    ->label(__('platform_notifications.labels.status'))
                    ->colors([
                        'secondary' => 'pending',
                        'success' => 'sent',
                        'danger' => 'failed',
                        'info' => 'read',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label(__('platform_notifications.labels.sent_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('platform_notifications.placeholders.not_sent')),

                Tables\Columns\TextColumn::make('read_at')
                    ->label(__('platform_notifications.labels.read_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('platform_notifications.placeholders.not_read')),

                Tables\Columns\TextColumn::make('failure_reason')
                    ->label(__('platform_notifications.labels.failure_reason'))
                    ->limit(50)
                    ->tooltip(function (PlatformNotificationRecipient $record): ?string {
                        return $record->failure_reason;
                    })
                    ->visible(fn (PlatformNotificationRecipient $record) => $record->isFailed()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('delivery_status')
                    ->options([
                        'pending' => __('platform_notifications.values.delivery_status.pending'),
                        'sent' => __('platform_notifications.values.delivery_status.sent'),
                        'failed' => __('platform_notifications.values.delivery_status.failed'),
                        'read' => __('platform_notifications.values.delivery_status.read'),
                    ]),

                Tables\Filters\SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                // No create action needed - recipients are auto-generated
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No bulk actions needed for recipients
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
