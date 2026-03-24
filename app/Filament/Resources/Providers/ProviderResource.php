<?php

namespace App\Filament\Resources\Providers;

use App\Filament\Resources\Providers\Pages\CreateProvider;
use App\Filament\Resources\Providers\Pages\EditProvider;
use App\Filament\Resources\Providers\Pages\ListProviders;
use App\Filament\Resources\Providers\Pages\ViewProvider;
use App\Filament\Resources\Providers\Schemas\ProviderForm;
use App\Filament\Resources\Providers\Schemas\ProviderInfolist;
use App\Filament\Resources\Providers\Tables\ProvidersTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Provider;
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
use Illuminate\Support\Facades\Gate;

class ProviderResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Provider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProviderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProviderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProvidersTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.providers.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.providers.plural');
    }

    public static function canViewAny(): bool
    {
        return static::allows('viewAny', Provider::class);
    }

    public static function canCreate(): bool
    {
        return static::allows('create', Provider::class);
    }

    /**
     * @return Builder<Provider>
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
                    'service_type',
                    'contact_info',
                    'created_at',
                    'updated_at',
                ])
                ->ordered()
                ->withCount(['tariffs', 'serviceConfigurations']);
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()
            ->forOrganization($organizationId)
            ->withCount(['tariffs', 'serviceConfigurations']);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Provider
            && static::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Provider
            && static::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Provider
            && static::allows('delete', $record);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::guard()->user();

        return $user instanceof User ? $user : null;
    }

    private static function allows(string $ability, Provider|string $subject): bool
    {
        $user = static::currentUser();

        return $user instanceof User
            && Gate::forUser($user)->allows($ability, $subject);
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
            'index' => ListProviders::route('/'),
            'create' => CreateProvider::route('/create'),
            'view' => ViewProvider::route('/{record}'),
            'edit' => EditProvider::route('/{record}/edit'),
        ];
    }
}
