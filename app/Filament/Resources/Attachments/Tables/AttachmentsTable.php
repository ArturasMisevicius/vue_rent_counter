<?php

namespace App\Filament\Resources\Attachments\Tables;

use App\Models\Attachment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AttachmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.relation_resources.attachments.fields.organization'))
                    ->searchable(),
                TextColumn::make('attachable_type')
                    ->label(__('superadmin.relation_resources.attachments.fields.attachable_type'))
                    ->state(fn (Attachment $record): string => $record->attachableTypeLabel())
                    ->searchable(),
                TextColumn::make('attachable_id')
                    ->label(__('superadmin.relation_resources.attachments.fields.attachable_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('uploader.name')
                    ->label(__('superadmin.relation_resources.attachments.fields.uploader'))
                    ->sortable(),
                TextColumn::make('filename')
                    ->label(__('superadmin.relation_resources.attachments.fields.filename'))
                    ->searchable(),
                TextColumn::make('original_filename')
                    ->label(__('superadmin.relation_resources.attachments.fields.original_filename'))
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->label(__('superadmin.relation_resources.attachments.fields.mime_type'))
                    ->searchable(),
                TextColumn::make('size')
                    ->label(__('superadmin.relation_resources.attachments.fields.size'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('disk')
                    ->label(__('superadmin.relation_resources.attachments.fields.disk'))
                    ->searchable(),
                TextColumn::make('path')
                    ->label(__('superadmin.relation_resources.attachments.fields.path'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('superadmin.relation_resources.shared.fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
