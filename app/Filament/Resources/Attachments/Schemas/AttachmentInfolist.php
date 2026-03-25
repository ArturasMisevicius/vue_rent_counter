<?php

namespace App\Filament\Resources\Attachments\Schemas;

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
                TextEntry::make('attachable_type'),
                TextEntry::make('attachable_id')
                    ->numeric(),
                TextEntry::make('uploaded_by_user_id')
                    ->numeric(),
                TextEntry::make('filename'),
                TextEntry::make('original_filename'),
                TextEntry::make('mime_type'),
                TextEntry::make('size')
                    ->numeric(),
                TextEntry::make('disk'),
                TextEntry::make('path'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('metadata')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Attachment $record): bool => $record->trashed()),
            ]);
    }
}
