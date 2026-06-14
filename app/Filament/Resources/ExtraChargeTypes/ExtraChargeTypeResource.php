<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraChargeTypes;

use App\Filament\Resources\ExtraChargeTypes\Pages\CreateExtraChargeType;
use App\Filament\Resources\ExtraChargeTypes\Pages\EditExtraChargeType;
use App\Filament\Resources\ExtraChargeTypes\Pages\ListExtraChargeTypes;
use App\Filament\Resources\ExtraChargeTypes\Pages\ViewExtraChargeType;
use App\Filament\Resources\ExtraChargeTypes\Schemas\ExtraChargeTypeForm;
use App\Filament\Resources\ExtraChargeTypes\Schemas\ExtraChargeTypeInfolist;
use App\Filament\Resources\ExtraChargeTypes\Tables\ExtraChargeTypesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\ExtraChargeType;
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

class ExtraChargeTypeResource extends Resource
{
    protected static ?string $model = ExtraChargeType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ExtraChargeTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExtraChargeTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExtraChargeTypesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.extra_charge_types.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.extra_charge_types.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.extra_charge_types.navigation');
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
     * @return Builder<ExtraChargeType>
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
        return self::currentUser()?->can('viewAny', ExtraChargeType::class) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof ExtraChargeType
            && (self::currentUser()?->can('view', $record) ?? false);
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->can('create', ExtraChargeType::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof ExtraChargeType
            && (self::currentUser()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof ExtraChargeType
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
            'index' => ListExtraChargeTypes::route('/'),
            'create' => CreateExtraChargeType::route('/create'),
            'view' => ViewExtraChargeType::route('/{record}'),
            'edit' => EditExtraChargeType::route('/{record}/edit'),
        ];
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
