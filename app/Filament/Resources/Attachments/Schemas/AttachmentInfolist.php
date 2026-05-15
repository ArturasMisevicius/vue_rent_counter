<?php

namespace App\Filament\Resources\Attachments\Schemas;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\Attachment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttachmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                TextEntry::make('attachable_type')
                    ->label(__('superadmin.relation_resources.attachments.fields.attachable_type'))
                    ->state(fn (Attachment $record): string => $record->attachableTypeLabel()),
                TextEntry::make('attachable_id')
                    ->label(__('superadmin.relation_resources.attachments.fields.attachable_id'))
                    ->numeric(),
                TextEntry::make('uploaded_by_user_id')
                    ->label(__('superadmin.relation_resources.attachments.fields.uploader'))
                    ->numeric(),
                TextEntry::make('filename')
                    ->label(__('superadmin.relation_resources.attachments.fields.filename')),
                TextEntry::make('original_filename')
                    ->label(__('superadmin.relation_resources.attachments.fields.original_filename')),
                TextEntry::make('mime_type')
                    ->label(__('superadmin.relation_resources.attachments.fields.mime_type')),
                TextEntry::make('size')
                    ->label(__('superadmin.relation_resources.attachments.fields.size'))
                    ->numeric(),
                TextEntry::make('disk')
                    ->label(__('superadmin.relation_resources.attachments.fields.disk')),
                TextEntry::make('path')
                    ->label(__('superadmin.relation_resources.attachments.fields.path')),
                TextEntry::make('description')
                    ->label(__('superadmin.relation_resources.attachments.fields.description'))
                    ->state(fn (Attachment $record): ?string => app(DatabaseContentLocalizer::class)->attachmentDescription($record->description))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('metadata')
                    ->label(__('superadmin.relation_resources.attachments.fields.metadata'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->label(__('superadmin.relation_resources.shared.fields.deleted_at'))
                    ->dateTime()
                    ->visible(fn (Attachment $record): bool => $record->trashed()),
            ]);
    }
}
