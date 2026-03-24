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
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('attachable_type')
                    ->options([
                        Project::class => 'Project',
                    ])
                    ->default(Project::class)
                    ->required(),
                Select::make('attachable_id')
                    ->options(fn (): array => Project::query()
                        ->select(['id', 'name'])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('uploaded_by_user_id')
                    ->relationship('uploader', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('filename')
                    ->required(),
                TextInput::make('original_filename')
                    ->required(),
                TextInput::make('mime_type')
                    ->required(),
                TextInput::make('size')
                    ->required()
                    ->numeric(),
                TextInput::make('disk')
                    ->required()
                    ->default('local'),
                TextInput::make('path')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                KeyValue::make('metadata')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
