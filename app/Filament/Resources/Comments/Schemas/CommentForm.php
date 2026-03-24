<?php

namespace App\Filament\Resources\Comments\Schemas;

use App\Models\Project;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CommentForm
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
                Select::make('commentable_type')
                    ->options([
                        Project::class => 'Project',
                    ])
                    ->default(Project::class)
                    ->required(),
                Select::make('commentable_id')
                    ->options(fn (): array => Project::query()
                        ->select(['id', 'name'])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('parent_id')
                    ->relationship('parent', 'id')
                    ->searchable()
                    ->preload(),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_internal')
                    ->required(),
                Toggle::make('is_pinned')
                    ->required(),
                DateTimePicker::make('edited_at'),
            ]);
    }
}
