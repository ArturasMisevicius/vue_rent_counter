<?php

namespace App\Filament\Resources\UtilityServices;

use App\Filament\Resources\UtilityServices\Pages\CreateUtilityService;
use App\Filament\Resources\UtilityServices\Pages\EditUtilityService;
use App\Filament\Resources\UtilityServices\Pages\ListUtilityServices;
use App\Filament\Resources\UtilityServices\Pages\ViewUtilityService;
use App\Filament\Resources\UtilityServices\Schemas\UtilityServiceForm;
use App\Filament\Resources\UtilityServices\Schemas\UtilityServiceInfolist;
use App\Filament\Resources\UtilityServices\Tables\UtilityServicesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\User;
use App\Models\UtilityService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UtilityServiceResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = UtilityService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function form(Schema $schema): Schema
    {
        return UtilityServiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UtilityServiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UtilityServicesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.utility_services.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.utility_services.plural');
    }

    public static function canViewAny(): bool
    {
        return static::allows('viewAny', UtilityService::class);
    }

    public static function canCreate(): bool
    {
        return static::allows('create', UtilityService::class);
    }

    /**
     * @return Builder<UtilityService>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::currentUser();

        if ($user?->isSuperadmin()) {
            return parent::getEloquentQuery()
                ->select([
                    'id',
                    'organization_id',
                    'name',
                    'slug',
                    'unit_of_measurement',
                    'default_pricing_model',
                    'is_global_template',
                    'service_type_bridge',
                    'description',
                    'is_active',
                    'created_at',
                    'updated_at',
                ])
                ->ordered()
                ->withCount('serviceConfigurations');
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'name',
                'slug',
                'unit_of_measurement',
                'default_pricing_model',
                'is_global_template',
                'service_type_bridge',
                'description',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->where(function (Builder $query) use ($organizationId): void {
                $query
                    ->where('organization_id', $organizationId)
                    ->orWhere('is_global_template', true);
            })
            ->withCount('serviceConfigurations');
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof UtilityService
            && static::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof UtilityService
            && static::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof UtilityService
            && static::allows('delete', $record);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::guard()->user();

        return $user instanceof User ? $user : null;
    }

    private static function allows(string $ability, UtilityService|string $subject): bool
    {
        $user = static::currentUser();

        return $user instanceof User
            && Gate::forUser($user)->allows($ability, $subject);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUtilityServices::route('/'),
            'create' => CreateUtilityService::route('/create'),
            'view' => ViewUtilityService::route('/{record}'),
            'edit' => EditUtilityService::route('/{record}/edit'),
        ];
    }
}
