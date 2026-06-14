<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraCharges;

use App\Filament\Resources\ExtraCharges\Pages\CreateExtraCharge;
use App\Filament\Resources\ExtraCharges\Pages\EditExtraCharge;
use App\Filament\Resources\ExtraCharges\Pages\ListExtraCharges;
use App\Filament\Resources\ExtraCharges\Pages\PendingExtraChargeApprovals;
use App\Filament\Resources\ExtraCharges\Pages\ViewExtraCharge;
use App\Filament\Resources\ExtraCharges\Schemas\ExtraChargeForm;
use App\Filament\Resources\ExtraCharges\Schemas\ExtraChargeInfolist;
use App\Filament\Resources\ExtraCharges\Tables\ExtraChargesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\ExtraCharge;
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

class ExtraChargeResource extends Resource
{
    protected static ?string $model = ExtraCharge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ExtraChargeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExtraChargeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExtraChargesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.extra_charges.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.extra_charges.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.extra_charges.navigation');
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return Builder<ExtraCharge>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::currentUser();

        return parent::getEloquentQuery()->forWorkspaceIndex(
            isSuperadmin: $user?->isSuperadmin() ?? false,
            organizationId: app(OrganizationContext::class)->currentOrganizationId(),
        );
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->can('viewAny', ExtraCharge::class) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof ExtraCharge
            && (self::currentUser()?->can('view', $record) ?? false);
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->can('create', ExtraCharge::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof ExtraCharge
            && (self::currentUser()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof ExtraCharge
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
            'index' => ListExtraCharges::route('/'),
            'create' => CreateExtraCharge::route('/create'),
            'pending-approvals' => PendingExtraChargeApprovals::route('/pending-approvals'),
            'view' => ViewExtraCharge::route('/{record}'),
            'edit' => EditExtraCharge::route('/{record}/edit'),
        ];
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
