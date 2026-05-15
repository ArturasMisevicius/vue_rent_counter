<?php

namespace App\Filament\Resources\Attachments\Schemas;

use App\Models\Project;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttachmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->label(__('superadmin.relation_resources.attachments.fields.organization'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('attachable_type')
                    ->label(__('superadmin.relation_resources.attachments.fields.attachable_type'))
                    ->options([
                        Project::class => __('superadmin.audit_logs.record_types.project'),
                    ])
                    ->default(Project::class)
                    ->required(),
                Select::make('attachable_id')
                    ->label(__('superadmin.relation_resources.attachments.fields.attachable_id'))
                    ->options(fn (): array => Project::query()
                        ->select(['id', 'name'])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('uploaded_by_user_id')
                    ->label(__('superadmin.relation_resources.attachments.fields.uploader'))
                    ->relationship('uploader', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('filename')
                    ->label(__('superadmin.relation_resources.attachments.fields.filename'))
                    ->required(),
                TextInput::make('original_filename')
                    ->label(__('superadmin.relation_resources.attachments.fields.original_filename'))
                    ->required(),
                TextInput::make('mime_type')
                    ->label(__('superadmin.relation_resources.attachments.fields.mime_type'))
                    ->required(),
                TextInput::make('size')
                    ->label(__('superadmin.relation_resources.attachments.fields.size'))
                    ->required()
                    ->numeric(),
                TextInput::make('disk')
                    ->label(__('superadmin.relation_resources.attachments.fields.disk'))
                    ->required()
                    ->default('local'),
                TextInput::make('path')
                    ->label(__('superadmin.relation_resources.attachments.fields.path'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('superadmin.relation_resources.attachments.fields.description'))
                    ->columnSpanFull(),
                KeyValue::make('metadata')
                    ->label(__('superadmin.relation_resources.attachments.fields.metadata'))
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
