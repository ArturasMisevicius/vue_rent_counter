<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformNotificationResource\RelationManagers;

use App\Models\PlatformNotificationRecipient;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('delivery_status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'pending',
                        'success' => 'sent',
                        'danger' => 'failed',
                        'info' => 'read',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not sent'),

                Tables\Columns\TextColumn::make('read_at')
                    ->label('Read At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not read'),

                Tables\Columns\TextColumn::make('failure_reason')
                    ->label('Failure Reason')
                    ->limit(50)
                    ->tooltip(function (PlatformNotificationRecipient $record): ?string {
                        return $record->failure_reason;
                    })
                    ->visible(fn (PlatformNotificationRecipient $record) => $record->isFailed()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('delivery_status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                        'read' => 'Read',
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