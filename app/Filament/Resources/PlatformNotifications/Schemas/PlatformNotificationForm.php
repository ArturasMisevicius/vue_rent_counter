<?php

namespace App\Filament\Resources\PlatformNotifications\Schemas;

use App\Enums\PlatformNotificationSeverity;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlatformNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Message')
                    ->schema([
                        TextInput::make('title')->label('Title')->required()->maxLength(255),
                        RichEditor::make('body')->label('Message')->required(),
                        Select::make('severity')
                            ->label('Severity')
                            ->options(collect(PlatformNotificationSeverity::cases())->mapWithKeys(fn (PlatformNotificationSeverity $severity): array => [$severity->value => ucfirst($severity->value)])->all())
                            ->required(),
                    ]),
            ]);
    }
}
