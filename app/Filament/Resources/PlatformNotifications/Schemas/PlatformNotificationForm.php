<?php

namespace App\Filament\Resources\PlatformNotifications\Schemas;

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
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
                            ->options(PlatformNotificationSeverity::options())
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(PlatformNotificationStatus::options())
                            ->required(),
                    ]),
            ]);
    }
}
