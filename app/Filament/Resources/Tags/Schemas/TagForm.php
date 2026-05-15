<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Filament\Support\Localization\LocalizedCodeLabel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->label(__('superadmin.relation_resources.tags.fields.organization'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label(__('superadmin.relation_resources.tags.fields.name'))
                    ->required(),
                TextInput::make('color')
                    ->label(__('superadmin.relation_resources.tags.fields.color')),
                Textarea::make('description')
                    ->label(__('superadmin.relation_resources.tags.fields.description'))
                    ->columnSpanFull(),
                Select::make('type')
                    ->label(__('superadmin.relation_resources.tags.fields.type'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.tags.types', [
                        'maintenance',
                        'project',
                        'priority',
                    ])),
                Toggle::make('is_system')
                    ->label(__('superadmin.relation_resources.tags.fields.is_system'))
                    ->required(),
            ]);
    }
}
