<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPeriods;

use App\Filament\Resources\BillingPeriods\Pages\CreateBillingPeriod;
use App\Filament\Resources\BillingPeriods\Pages\EditBillingPeriod;
use App\Filament\Resources\BillingPeriods\Pages\ListBillingPeriods;
use App\Filament\Resources\BillingPeriods\Pages\ViewBillingPeriod;
use App\Filament\Resources\BillingPeriods\Schemas\BillingPeriodForm;
use App\Filament\Resources\BillingPeriods\Schemas\BillingPeriodInfolist;
use App\Filament\Resources\BillingPeriods\Tables\BillingPeriodsTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\BillingPeriod;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BillingPeriodResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = BillingPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BillingPeriodForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BillingPeriodInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingPeriodsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.billing_periods.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.billing_periods.plural');
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->can('viewAny', BillingPeriod::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->can('create', BillingPeriod::class) ?? false;
    }

    /**
     * @return Builder<BillingPeriod>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::currentUser();

        return parent::getEloquentQuery()->forWorkspaceIndex(
            isSuperadmin: $user?->isSuperadmin() ?? false,
            organizationId: app(OrganizationContext::class)->currentOrganizationId(),
        );
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof BillingPeriod
            && (self::currentUser()?->can('view', $record) ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof BillingPeriod
            && (self::currentUser()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof BillingPeriod
            && (self::currentUser()?->can('delete', $record) ?? false);
    }

    /**
     * @return array<int, class-string>
     */
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
            'index' => ListBillingPeriods::route('/'),
            'create' => CreateBillingPeriod::route('/create'),
            'view' => ViewBillingPeriod::route('/{record}'),
            'edit' => EditBillingPeriod::route('/{record}/edit'),
        ];
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
