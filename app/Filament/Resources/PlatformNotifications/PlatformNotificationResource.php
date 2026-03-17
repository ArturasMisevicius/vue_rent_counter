<?php

namespace App\Filament\Resources\PlatformNotifications;

use App\Filament\Resources\PlatformNotifications\Pages\CreatePlatformNotification;
use App\Filament\Resources\PlatformNotifications\Pages\EditPlatformNotification;
use App\Filament\Resources\PlatformNotifications\Pages\ListPlatformNotifications;
use App\Filament\Resources\PlatformNotifications\Pages\ViewPlatformNotification;
use App\Filament\Resources\PlatformNotifications\Schemas\PlatformNotificationForm;
use App\Filament\Resources\PlatformNotifications\Tables\PlatformNotificationsTable;
use App\Models\PlatformNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PlatformNotificationResource extends Resource
{
    protected static ?string $model = PlatformNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return PlatformNotificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformNotificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return PlatformNotification::query()->forSuperadminResource();
    }

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
