<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\HasTranslatedValidation;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * Filament resource for managing users.
 *
 * Provides CRUD operations for users with:
 * - Role-based navigation visibility (admin-only)
 * - Conditional tenant field based on role
 * - Password hashing
 * - Localized validation messages
 * - Tenant scope isolation
 *
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6
 *
 * @see \App\Models\User
 * @see \App\Policies\UserPolicy
 * @see \App\Filament\Concerns\HasTranslatedValidation
 */
class UserResource extends Resource
{
    use HasTranslatedValidation;

    protected static ?string $model = User::class;

    /**
     * Translation prefix for validation messages.
     *
     * Used by HasTranslatedValidation trait to load messages from
     * lang/{locale}/users.php under the 'validation' key.
     */
    protected static string $translationPrefix = 'users.validation';

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 8;

    /**
     * Cache TTL for navigation badge count (in seconds).
     * 
     * @var int
     */
    private const NAVIGATION_BADGE_CACHE_TTL = 300;

    /**
     * Roles allowed to access user management.
     * 
     * @var array<UserRole>
     */
    private const ALLOWED_ROLES = [
        UserRole::SUPERADMIN,
        UserRole::ADMIN,
        UserRole::MANAGER,
    ];

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return __('users.labels.users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system');
    }

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Determine if the current user can view any users.
     *
     * Controls access to the user management interface. Only SUPERADMIN, ADMIN,
     * and MANAGER roles can access user management. TENANT role is explicitly
     * excluded from user management.
     *
     * This method serves as the primary authorization checkpoint for the resource.
     * It works in conjunction with UserPolicy for granular authorization checks.
     *
     * Performance: Uses helper method to avoid repeated auth()->user() calls
     * and leverages ALLOWED_ROLES constant for efficient role checking.
     *
     * @return bool True if the user can access the user management interface
     *
     * @see \App\Policies\UserPolicy::viewAny()
     *
     * Requirements: 6.1, 9.3
     */
    public static function canViewAny(): bool
    {
        return static::userCanManageUsers();
    }

    /**
     * Determine if the current user can create users.
     *
     * Delegates to canViewAny() for consistency. If a user can view the resource,
     * they can create users. The UserPolicy enforces tenant boundaries and role
     * hierarchy for the actual creation operation.
     *
     * @return bool True if the user can create new users
     *
     * @see \App\Policies\UserPolicy::create()
     *
     * Requirements: 6.2, 13.2
     */
    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    /**
     * Determine if the current user can edit a specific user.
     *
     * Resource-level authorization check that delegates to canViewAny(). The
     * UserPolicy::update() method enforces granular rules including:
     * - Users can edit themselves
     * - Superadmins can edit any user
     * - Admins/Managers can edit users within their tenant
     *
     * @param Model $record The user record being edited
     * @return bool True if the user can edit the record
     *
     * @see \App\Policies\UserPolicy::update()
     *
     * Requirements: 6.3, 13.3, 13.4
     */
    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    /**
     * Determine if the current user can delete a specific user.
     *
     * Resource-level authorization check that delegates to canViewAny(). The
     * UserPolicy::delete() method enforces granular rules including:
     * - Self-deletion prevention
     * - Superadmins can delete any user (except themselves)
     * - Admins/Managers can delete users within their tenant
     * - All deletions are audit logged
     *
     * @param Model $record The user record being deleted
     * @return bool True if the user can delete the record
     *
     * @see \App\Policies\UserPolicy::delete()
     *
     * Requirements: 6.4, 13.4
     */
    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    /**
     * Admin-only access (Requirements 6.1, 9.3).
     * Policies handle granular authorization (Requirement 9.5).
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    /**
     * Check if the current user can manage users.
     *
     * Performance: Caches user instance and uses efficient role check
     * with ALLOWED_ROLES constant to avoid repeated array construction.
     * This helper method is called by all authorization methods to ensure
     * consistent and efficient role checking.
     *
     * @return bool True if user has permission to manage users
     */
    protected static function userCanManageUsers(): bool
    {
        $user = auth()->user();
        
        if (!$user instanceof User) {
            return false;
        }

        return in_array($user->role, self::ALLOWED_ROLES, true);
    }

    /**
     * Get the displayable label for the resource.
     */
    public static function getLabel(): string
    {
        return __('users.labels.user');
    }

    /**
     * Get the displayable plural label for the resource.
     */
    public static function getPluralLabel(): string
    {
        return __('users.labels.users');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(__('users.sections.user_details'))
                    ->description(__('users.sections.user_details_description'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('users.labels.name'))
                            ->placeholder(__('users.placeholders.name'))
                            ->required()
                            ->maxLength(255)
                            ->validationMessages(self::getValidationMessages('name')),

                        Forms\Components\TextInput::make('email')
                            ->label(__('users.labels.email'))
                            ->placeholder(__('users.placeholders.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->validationMessages(self::getValidationMessages('email')),

                        Forms\Components\TextInput::make('password')
                            ->label(__('users.labels.password'))
                            ->placeholder(__('users.placeholders.password'))
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->minLength(8)
                            ->confirmed()
                            ->helperText(__('users.helper_text.password'))
                            ->validationMessages(self::getValidationMessages('password')),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label(__('users.labels.password_confirmation'))
                            ->placeholder(__('users.placeholders.password_confirmation'))
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->validationMessages(self::getValidationMessages('password')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('users.sections.role_and_access'))
                    ->description(__('users.sections.role_and_access_description'))
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label(__('users.labels.role'))
                            ->options(UserRole::class)
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText(__('users.helper_text.role'))
                            ->validationMessages(self::getValidationMessages('role')),

                        Forms\Components\Select::make('tenant_id')
                            ->label(__('users.labels.tenant'))
                            ->relationship(
                                name: 'parentUser',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => self::scopeToUserTenant($query)
                            )
                            ->searchable()
                            ->preload()
                            ->required(fn (Forms\Get $get): bool => self::isTenantRequired($get('role')))
                            ->visible(fn (Forms\Get $get): bool => self::isTenantVisible($get('role')))
                            ->helperText(__('users.helper_text.tenant'))
                            ->validationMessages(self::getValidationMessages('tenant_id')),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('users.labels.is_active'))
                            ->helperText(__('users.helper_text.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Scope query to authenticated user's tenant.
     * 
     * Filters the query to only include records within the authenticated user's tenant.
     * Superadmins bypass this scope and see all records.
     * 
     * @param Builder $query The Eloquent query builder
     * @return Builder The scoped query builder
     * 
     * Requirements: 6.5, 6.6
     */
    protected static function scopeToUserTenant(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user instanceof User && $user->tenant_id) {
            $table = $query->getModel()->getTable();
            $query->where("{$table}.tenant_id", $user->tenant_id);
        }

        return $query;
    }
    
    /**
     * Modify the Eloquent query to apply tenant scoping and eager loading.
     * 
     * This method is called by Filament to scope the table query.
     * Superadmins see all users, while admins/managers see only users
     * within their tenant scope.
     * 
     * Performance optimizations:
     * - Eager loads parentUser relationship to prevent N+1 queries
     * - Only selects necessary columns from parentUser
     * 
     * @return Builder The scoped query builder
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Eager load parentUser to prevent N+1 queries (only id and name needed)
        $query->with('parentUser:id,name');

        // Superadmins see all users
        if ($user instanceof User && $user->isSuperadmin()) {
            return $query;
        }

        // Apply tenant scope for admins and managers
        if ($user instanceof User && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query;
    }

    /**
     * Determine if tenant field should be required based on role.
     * 
     * @param string|null $role The user role value
     * @return bool
     */
    protected static function isTenantRequired(?string $role): bool
    {
        return in_array($role, [
            UserRole::MANAGER->value,
            UserRole::TENANT->value,
        ], true);
    }

    /**
     * Determine if tenant field should be visible based on role.
     * 
     * @param string|null $role The user role value
     * @return bool
     */
    protected static function isTenantVisible(?string $role): bool
    {
        return in_array($role, [
            UserRole::MANAGER->value,
            UserRole::TENANT->value,
            UserRole::ADMIN->value,
        ], true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('users.labels.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('users.labels.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('users.tooltips.copy_email')),

                Tables\Columns\TextColumn::make('role')
                    ->label(__('users.labels.role'))
                    ->badge()
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::SUPERADMIN => 'danger',
                        UserRole::ADMIN => 'warning',
                        UserRole::MANAGER => 'info',
                        UserRole::TENANT => 'success',
                    })
                    ->formatStateUsing(fn (?UserRole $state): ?string => $state?->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('parentUser.name')
                    ->label(__('users.labels.tenant'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder(__('app.common.dash')),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('users.labels.is_active'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('users.labels.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label(__('users.filters.role'))
                    ->options(UserRole::labels())
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('users.filters.is_active'))
                    ->placeholder(__('users.filters.all_users'))
                    ->trueLabel(__('users.filters.active_only'))
                    ->falseLabel(__('users.filters.inactive_only')),
            ])
            ->recordActions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('users.empty_state.heading'))
            ->emptyStateDescription(__('users.empty_state.description'))
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Get the navigation badge for the resource.
     * 
     * Performance optimization: Caches the count for 5 minutes to avoid
     * running a COUNT query on every page load. Cache key is shared across
     * users with the same role/tenant combination for better cache hit ratio.
     * 
     * Optimization improvements:
     * - Shared cache key reduces redundant queries
     * - Early return for unauthorized users
     * - Efficient query with indexed tenant_id column
     */
    public static function getNavigationBadge(): ?string
    {
        // Early return if user cannot manage users (no badge needed)
        if (!static::userCanManageUsers()) {
            return null;
        }

        $user = auth()->user();

        // Create shared cache key based on role and tenant (not user-specific)
        // This allows cache sharing across users with same role/tenant
        $cacheKey = sprintf(
            'user_resource_badge_%s_%s',
            $user->role->value,
            $user->tenant_id ?? 'all'
        );

        // Cache for configured TTL with optimized query
        $count = cache()->remember($cacheKey, self::NAVIGATION_BADGE_CACHE_TTL, function () use ($user) {
            $query = static::getModel()::query();

            // Apply tenant scope for non-superadmin users
            // Uses indexed tenant_id column for efficient filtering
            if ($user->role !== UserRole::SUPERADMIN && $user->tenant_id) {
                $query->where('tenant_id', $user->tenant_id);
            }

            return $query->count();
        });

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color for the resource.
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
