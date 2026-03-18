<?php

namespace App\Filament\Resources\Subscriptions;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\Subscriptions\Pages\CreateSubscription;
use App\Filament\Resources\Subscriptions\Pages\EditSubscription;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Subscriptions\Pages\ViewSubscription;
use App\Models\Subscription;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
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
        return $schema->components([
            Section::make('Subscription Details')
                ->schema([
                    Select::make('organization_id')
                        ->label('Organization')
                        ->relationship('organization', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('plan')
                        ->label('Plan')
                        ->options(SubscriptionPlan::options())
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options(SubscriptionStatus::options())
                        ->required(),
                    DatePicker::make('starts_at')
                        ->label('Starts At'),
                    DatePicker::make('expires_at')
                        ->label('Expires At'),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Subscription Details')
                ->schema([
                    TextEntry::make('organization.name')
                        ->label('Organization'),
                    TextEntry::make('plan')
                        ->label('Plan')
                        ->badge(),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),
                    TextEntry::make('expires_at')
                        ->label('Expires At')
                        ->date(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable(),
                TextColumn::make('plan')
                    ->label('Plan')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('expires_at');
    }

    public static function getModelLabel(): string
    {
        return 'Subscription';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Subscriptions';
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
