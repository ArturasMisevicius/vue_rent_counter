<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserKycProfiles;

use App\Filament\Resources\UserKycProfiles\Pages\CreateUserKycProfile;
use App\Filament\Resources\UserKycProfiles\Pages\EditUserKycProfile;
use App\Filament\Resources\UserKycProfiles\Pages\ListUserKycProfiles;
use App\Filament\Resources\UserKycProfiles\Pages\ViewUserKycProfile;
use App\Filament\Resources\UserKycProfiles\Schemas\UserKycProfileForm;
use App\Filament\Resources\UserKycProfiles\Schemas\UserKycProfileInfolist;
use App\Filament\Resources\UserKycProfiles\Tables\UserKycProfilesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\User;
use App\Models\UserKycProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserKycProfileResource extends Resource
{
    protected static ?string $model = UserKycProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'full_legal_name';

    public static function form(Schema $schema): Schema
    {
        return UserKycProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserKycProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserKycProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.user_kyc_profiles.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.user_kyc_profiles.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('superadmin.user_kyc_profiles.navigation');
    }

    public static function canAccess(): bool
    {
        $user = self::currentUser();

        return $user?->isSuperadmin() || $user?->isAdmin() || $user?->isManager();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        $user = self::currentUser();

        if (! $record instanceof UserKycProfile || $user === null) {
            return false;
        }

        if ($user->isSuperadmin()) {
            return true;
        }

        return $record->organization_id === self::currentOrganizationId();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'organization:id,name',
                'user:id,name,organization_id',
                'attachments:id,attachable_id,attachable_type,document_type,filename,original_filename,mime_type,size,disk,path',
            ]);

        $user = self::currentUser();

        if ($user?->isSuperadmin()) {
            return $query;
        }

        $organizationId = self::currentOrganizationId();

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query->where('organization_id', $organizationId);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserKycProfiles::route('/'),
            'create' => CreateUserKycProfile::route('/create'),
            'view' => ViewUserKycProfile::route('/{record}'),
            'edit' => EditUserKycProfile::route('/{record}/edit'),
        ];
    }

    private static function currentOrganizationId(): ?int
    {
        return app(OrganizationContext::class)->currentOrganizationId() ?? self::currentUser()?->organization_id;
    }

    private static function currentUser(): ?User
    {
        $user = Auth::guard()->user();

        return $user instanceof User ? $user : null;
    }
}
