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
                    ->label(__('superadmin.comments_resource.fields.organization'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('commentable_type')
                    ->label(__('superadmin.comments_resource.fields.commentable_type'))
                    ->options([
                        Project::class => __('superadmin.audit_logs.record_types.project'),
                    ])
                    ->default(Project::class)
                    ->required(),
                Select::make('commentable_id')
                    ->label(__('superadmin.comments_resource.fields.commentable_id'))
                    ->options(fn (): array => Project::query()
                        ->select(['id', 'name'])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->label(__('superadmin.comments_resource.fields.user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('parent_id')
                    ->label(__('superadmin.comments_resource.fields.parent'))
                    ->relationship('parent', 'id')
                    ->searchable()
                    ->preload(),
                Textarea::make('body')
                    ->label(__('superadmin.comments_resource.fields.body'))
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_internal')
                    ->label(__('superadmin.comments_resource.fields.is_internal'))
                    ->required(),
                Toggle::make('is_pinned')
                    ->label(__('superadmin.comments_resource.fields.is_pinned'))
                    ->required(),
                DateTimePicker::make('edited_at')
                    ->label(__('superadmin.comments_resource.fields.edited_at')),
            ]);
    }
}
