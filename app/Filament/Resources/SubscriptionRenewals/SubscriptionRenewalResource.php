<?php

namespace App\Filament\Resources\SubscriptionRenewals;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\SubscriptionRenewals\Pages\CreateSubscriptionRenewal;
use App\Filament\Resources\SubscriptionRenewals\Pages\EditSubscriptionRenewal;
use App\Filament\Resources\SubscriptionRenewals\Pages\ListSubscriptionRenewals;
use App\Filament\Resources\SubscriptionRenewals\Pages\ViewSubscriptionRenewal;
use App\Filament\Resources\SubscriptionRenewals\Schemas\SubscriptionRenewalForm;
use App\Filament\Resources\SubscriptionRenewals\Schemas\SubscriptionRenewalInfolist;
use App\Filament\Resources\SubscriptionRenewals\Tables\SubscriptionRenewalsTable;
use App\Models\SubscriptionRenewal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionRenewalResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static ?string $model = SubscriptionRenewal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SubscriptionRenewalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SubscriptionRenewalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionRenewalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionRenewals::route('/'),
            'create' => CreateSubscriptionRenewal::route('/create'),
            'view' => ViewSubscriptionRenewal::route('/{record}'),
            'edit' => EditSubscriptionRenewal::route('/{record}/edit'),
        ];
    }
}
