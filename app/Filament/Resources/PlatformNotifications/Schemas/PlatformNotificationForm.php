<?php

namespace App\Filament\Resources\PlatformNotifications\Schemas;

use App\Enums\PlatformNotificationSeverity;
use App\Http\Requests\Superadmin\PlatformNotifications\StorePlatformNotificationRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlatformNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Notification')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->rules(StorePlatformNotificationRequest::ruleset()['title']),
                        Textarea::make('body')
                            ->label('Body')
                            ->required()
                            ->rows(5)
                            ->rules(StorePlatformNotificationRequest::ruleset()['body']),
                        Select::make('severity')
                            ->label('Severity')
                            ->options(collect(PlatformNotificationSeverity::cases())
                                ->mapWithKeys(fn (PlatformNotificationSeverity $severity): array => [$severity->value => $severity->label()])
                                ->all())
                            ->default(PlatformNotificationSeverity::INFO->value)
                            ->required()
                            ->rules(StorePlatformNotificationRequest::ruleset()['severity']),
                        Select::make('target_scope')
                            ->label('Recipients')
                            ->options(config('tenanto.notifications.target_scopes', []))
                            ->default('all')
                            ->required()
                            ->rules(StorePlatformNotificationRequest::ruleset()['target_scope']),
                    ])
                    ->columns(2),
            ]);
    }
}
