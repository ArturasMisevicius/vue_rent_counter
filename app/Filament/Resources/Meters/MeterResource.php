<?php

namespace App\Filament\Resources\Meters;

use App\Filament\Resources\Meters\Pages\CreateMeter;
use App\Filament\Resources\Meters\Pages\EditMeter;
use App\Filament\Resources\Meters\Pages\ListMeters;
use App\Filament\Resources\Meters\Pages\ViewMeter;
use App\Filament\Resources\Meters\RelationManagers\ReadingHistoryRelationManager;
use App\Filament\Resources\Meters\Schemas\MeterForm;
use App\Filament\Resources\Meters\Schemas\MeterInfolist;
use App\Filament\Resources\Meters\Tables\MetersTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Meter;
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

class MeterResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Meter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MeterForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MeterInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MetersTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.meters.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.meters.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('shell.navigation.groups.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.meters.navigation');
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canViewAny(): bool
    {
        return static::allows('viewAny', Meter::class);
    }

    public static function canCreate(): bool
    {
        return static::allows('create', Meter::class);
    }

    /**
     * @return Builder<Meter>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::currentUser();
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        return parent::getEloquentQuery()
            ->forWorkspaceIndex($user?->isSuperadmin() ?? false, $organizationId);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Meter
            && static::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Meter
            && static::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Meter
            && static::allows('delete', $record);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::guard()->user();

        return $user instanceof User ? $user : null;
    }

    private static function allows(string $ability, Meter|string $subject): bool
    {
        $user = static::currentUser();

        return $user instanceof User
            && Gate::forUser($user)->allows($ability, $subject);
    }

    public static function getRelations(): array
    {
        return [
            ReadingHistoryRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListMeters::route('/'),
            'create' => CreateMeter::route('/create'),
            'view' => ViewMeter::route('/{record}'),
            'edit' => EditMeter::route('/{record}/edit'),
        ];
    }
}
