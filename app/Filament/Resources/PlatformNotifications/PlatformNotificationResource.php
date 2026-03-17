<?php

namespace App\Filament\Resources\PlatformNotifications;

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use App\Filament\Resources\PlatformNotifications\Pages\CreatePlatformNotification;
use App\Filament\Resources\PlatformNotifications\Pages\EditPlatformNotification;
use App\Filament\Resources\PlatformNotifications\Pages\ListPlatformNotifications;
use App\Filament\Resources\PlatformNotifications\Pages\ViewPlatformNotification;
use App\Models\PlatformNotification;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformNotificationResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = PlatformNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Notification Details')
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('body')
                        ->label('Message')
                        ->required()
                        ->rows(5),
                    Select::make('severity')
                        ->label('Severity')
                        ->options(PlatformNotificationSeverity::options())
                        ->default(PlatformNotificationSeverity::INFO->value)
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options(PlatformNotificationStatus::options())
                        ->default(PlatformNotificationStatus::DRAFT->value)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Notification Details')
                ->schema([
                    TextEntry::make('title')
                        ->label('Title'),
                    TextEntry::make('body')
                        ->label('Message'),
                    TextEntry::make('severity')
                        ->label('Severity')
                        ->badge(),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getModelLabel(): string
    {
        return 'Platform Notification';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Platform Notifications';
    }

    /**
     * @return Builder<PlatformNotification>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'id',
                'title',
                'body',
                'severity',
                'status',
                'scheduled_for',
                'sent_at',
                'created_at',
                'updated_at',
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPlatformNotifications::route('/'),
            'create' => CreatePlatformNotification::route('/create'),
            'view' => ViewPlatformNotification::route('/{record}'),
            'edit' => EditPlatformNotification::route('/{record}/edit'),
        ];
    }
}
