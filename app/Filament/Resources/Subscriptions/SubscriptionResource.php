<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\Subscriptions\Pages\CreateSubscription;
use App\Filament\Resources\Subscriptions\Pages\EditSubscription;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Subscriptions\Pages\ViewSubscription;
use App\Filament\Resources\Subscriptions\Schemas\SubscriptionForm;
use App\Filament\Resources\Subscriptions\Schemas\SubscriptionInfolist;
use App\Filament\Resources\Subscriptions\Schemas\SubscriptionTable;
use App\Models\Subscription;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function form(Schema $schema): Schema
    {
        return SubscriptionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SubscriptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.subscriptions_resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.subscriptions_resource.plural');
    }

    /**
     * @return Builder<Subscription>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forSuperadminControlPlane();
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
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
            'view' => ViewSubscription::route('/{record}'),
            'edit' => EditSubscription::route('/{record}/edit'),
        ];
    }
}
