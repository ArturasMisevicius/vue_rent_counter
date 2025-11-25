<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\HasTranslatedValidation;
use App\Filament\Resources\BuildingResource\Pages;
use App\Filament\Resources\BuildingResource\RelationManagers;
use App\Models\Building;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Building Resource for Filament Admin Panel
 *
 * Manages multi-unit residential buildings in the utilities billing platform.
 * Provides role-based CRUD operations with automatic tenant scoping, localized
 * validation, and comprehensive property management through relation managers.
 *
 * ## Key Features
 * - Role-based authorization via BuildingPolicy (superadmin/admin/manager access)
 * - Automatic tenant_id assignment on create (inherits from authenticated user)
 * - Localized form validation using HasTranslatedValidation trait
 * - Properties relation manager for managing building→property relationships
 * - Gyvatukas calculation support (summer average tracking)
 * - Navigation hidden from tenant users (Requirements 9.1, 9.2, 9.3)
 *
 * ## Authorization Matrix
 * | Role       | View Any | View | Create | Update | Delete |
 * |------------|----------|------|--------|--------|--------|
 * | Superadmin | ✅ All   | ✅ All | ✅    | ✅ All  | ✅ All  |
 * | Admin      | ✅ All   | ✅ All | ✅    | ✅ All  | ✅ All  |
 * | Manager    | ✅ Scoped| ✅ Scoped| ✅  | ✅ Scoped| ❌    |
 * | Tenant     | ❌       | ✅ Property's building | ❌ | ❌ | ❌ |
 *
 * ## Form Fields
 * - **Name**: Building identifier (max 255 chars, required)
 * - **Address**: Physical location (max 255 chars, required, full-width)
 * - **Total Apartments**: Capacity for gyvatukas calculations (1-1000, required)
 *
 * ## Table Columns
 * - Name (searchable, sortable)
 * - Address (searchable, sortable, default sort)
 * - Total Apartments (numeric, sortable)
 * - Properties Count (relationship count, sortable)
 * - Created At (datetime, sortable, hidden by default)
 *
 * ## Tenant Scoping
 * Buildings are automatically scoped by tenant_id via the BelongsToTenant trait:
 * - Superadmin: Sees all buildings across all tenants
 * - Admin: Sees all buildings (policy allows cross-tenant access)
 * - Manager: Sees only buildings where tenant_id matches their own
 * - Tenant: Cannot access building list (navigation hidden)
 *
 * ## Localization
 * All UI strings are externalized via Laravel's translation system:
 * - Navigation: `app.nav.buildings`, `app.nav_groups.operations`
 * - Labels: `buildings.labels.*`
 * - Validation: `buildings.validation.*`
 *
 * ## Related Components
 * - Model: {@see \App\Models\Building}
 * - Policy: {@see \App\Policies\BuildingPolicy}
 * - Trait: {@see \App\Filament\Concerns\HasTranslatedValidation}
 * - Relation Manager: {@see \App\Filament\Resources\BuildingResource\RelationManagers\PropertiesRelationManager}
 * - Pages: {@see \App\Filament\Resources\BuildingResource\Pages}
 *
 * ## Usage Example
 * ```php
 * // Manager creates a building
 * // 1. Navigates to /admin/buildings/create
 * // 2. Fills form: name, address, total_apartments
 * // 3. On submit, tenant_id is auto-injected from auth()->user()
 * // 4. BuildingPolicy::create() checks authorization
 * // 5. Record saved with tenant scope
 * // 6. Redirected to edit page with properties relation manager
 * ```
 *
 * ## Testing
 * Comprehensive test coverage in `tests/Feature/Filament/BuildingResourceTest.php`:
 * - 37 tests covering navigation, authorization, configuration, form, table
 * - Role-based access control verification
 * - Tenant scope isolation checks
 * - Localization verification
 *
 * @package App\Filament\Resources
 * @see \App\Models\Building
 * @see \App\Policies\BuildingPolicy
 * @see \App\Filament\Resources\BuildingResource\RelationManagers\PropertiesRelationManager
 */
class BuildingResource extends Resource
{
    use HasTranslatedValidation;

    protected static ?string $model = Building::class;

    protected static string $translationPrefix = 'buildings.validation';

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 4;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationLabel(): string
    {
        return __('app.nav.buildings');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.operations');
    }

    /**
     * Determine if any buildings can be viewed.
     *
     * Delegates authorization to BuildingPolicy::viewAny() which implements
     * role-based access control:
     * - Superadmin: Can view all buildings across all tenants
     * - Admin: Can view all buildings (cross-tenant access)
     * - Manager: Can view buildings (filtered by tenant scope)
     * - Tenant: Cannot view building list
     *
     * @return bool True if user can view the building list, false otherwise
     *
     * @see \App\Policies\BuildingPolicy::viewAny()
     */
    public static function canViewAny(): bool
    {
        $user = self::getAuthenticatedUser();

        return $user?->can('viewAny', Building::class) ?? false;
    }

    /**
     * Determine if a new building can be created.
     *
     * Delegates authorization to BuildingPolicy::create() which allows:
     * - Superadmin: Can create buildings
     * - Admin: Can create buildings
     * - Manager: Can create buildings (auto-scoped to their tenant)
     * - Tenant: Cannot create buildings
     *
     * Note: tenant_id is automatically assigned in CreateBuilding page via
     * mutateFormDataBeforeCreate() to ensure proper tenant scoping.
     *
     * @return bool True if user can create buildings, false otherwise
     *
     * @see \App\Policies\BuildingPolicy::create()
     * @see \App\Filament\Resources\BuildingResource\Pages\CreateBuilding::mutateFormDataBeforeCreate()
     */
    public static function canCreate(): bool
    {
        $user = self::getAuthenticatedUser();

        return $user?->can('create', Building::class) ?? false;
    }

    /**
     * Determine if a building can be edited.
     *
     * Delegates authorization to BuildingPolicy::update() which implements
     * tenant-aware access control:
     * - Superadmin: Can edit any building
     * - Admin: Can edit any building (cross-tenant access)
     * - Manager: Can edit buildings where tenant_id matches their own
     * - Tenant: Cannot edit buildings
     *
     * @param Building $record The building to check edit permissions for
     * @return bool True if user can edit the building, false otherwise
     *
     * @see \App\Policies\BuildingPolicy::update()
     */
    public static function canEdit($record): bool
    {
        $user = self::getAuthenticatedUser();

        return $user?->can('update', $record) ?? false;
    }

    /**
     * Determine if a building can be deleted.
     *
     * Delegates authorization to BuildingPolicy::delete() which restricts
     * deletion to superadmin and admin roles only:
     * - Superadmin: Can delete any building
     * - Admin: Can delete any building (cross-tenant access)
     * - Manager: Cannot delete buildings (Requirements 4.5, 13.3)
     * - Tenant: Cannot delete buildings
     *
     * Note: Deletion may cascade to related properties depending on foreign
     * key constraints. Consider soft deletes for data retention.
     *
     * @param Building $record The building to check delete permissions for
     * @return bool True if user can delete the building, false otherwise
     *
     * @see \App\Policies\BuildingPolicy::delete()
     */
    public static function canDelete($record): bool
    {
        $user = self::getAuthenticatedUser();

        return $user?->can('delete', $record) ?? false;
    }

    /**
     * Determine if navigation should be registered.
     *
     * Hides the building navigation item from tenant users to prevent
     * unauthorized access attempts. Tenants can only view their property's
     * building through the property detail page.
     *
     * Navigation visibility by role:
     * - Superadmin: ✅ Visible
     * - Admin: ✅ Visible
     * - Manager: ✅ Visible
     * - Tenant: ❌ Hidden (Requirements 9.1, 9.2, 9.3)
     * - Guest: ❌ Hidden
     *
     * @return bool True if navigation should be visible, false otherwise
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = self::getAuthenticatedUser();

        return $user !== null && $user->role !== UserRole::TENANT;
    }

    /**
     * Get the authenticated user.
     *
     * Helper method to safely retrieve the authenticated user with proper
     * type checking. Returns null if no user is authenticated or if the
     * authenticated user is not an instance of the User model.
     *
     * @return User|null The authenticated user or null
     */
    private static function getAuthenticatedUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                self::buildNameField(),
                self::buildAddressField(),
                self::buildTotalApartmentsField(),
            ]);
    }

    /**
     * Build the name input field.
     *
     * Creates a text input for the building name with validation. The name
     * serves as a human-friendly identifier for the building. If empty, the
     * Building model's display_name attribute falls back to the address.
     *
     * Validation Rules:
     * - Required
     * - Max length: 255 characters
     * - Localized error messages via HasTranslatedValidation trait
     *
     * @return Forms\Components\TextInput Configured name input field
     *
     * @see \App\Models\Building::getDisplayNameAttribute()
     * @see \App\Filament\Concerns\HasTranslatedValidation::getValidationMessages()
     */
    private static function buildNameField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('name')
            ->label(__('buildings.labels.name'))
            ->required()
            ->maxLength(255)
            ->validationAttribute('name')
            ->validationMessages(self::getValidationMessages('name'));
    }

    /**
     * Build the address input field.
     *
     * Creates a text input for the building's physical address. The field
     * spans the full form width to accommodate long addresses. This field
     * is used as the default sort column in the table view.
     *
     * Validation Rules:
     * - Required
     * - Max length: 255 characters
     * - Full-width column span for better UX
     * - Localized error messages via HasTranslatedValidation trait
     *
     * @return Forms\Components\TextInput Configured address input field
     *
     * @see \App\Filament\Concerns\HasTranslatedValidation::getValidationMessages()
     */
    private static function buildAddressField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('address')
            ->label(__('buildings.labels.address'))
            ->required()
            ->maxLength(255)
            ->columnSpanFull()
            ->validationAttribute('address')
            ->validationMessages(self::getValidationMessages('address'));
    }

    /**
     * Build the total apartments input field.
     *
     * Creates a numeric input for the total number of apartments in the
     * building. This value is critical for gyvatukas (circulation fee)
     * calculations, which distribute heating costs across all units.
     *
     * Validation Rules:
     * - Required
     * - Numeric (integer only)
     * - Min value: 1 (at least one apartment)
     * - Max value: 1000 (reasonable upper limit)
     * - Localized error messages via HasTranslatedValidation trait
     *
     * Business Context:
     * Used by GyvatukasCalculator to determine per-apartment circulation
     * fees during the heating season (October-April in Lithuania).
     *
     * @return Forms\Components\TextInput Configured total apartments input field
     *
     * @see \App\Services\GyvatukasCalculator
     * @see \App\Models\Building::calculateSummerAverage()
     * @see \App\Filament\Concerns\HasTranslatedValidation::getValidationMessages()
     */
    private static function buildTotalApartmentsField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('total_apartments')
            ->label(__('buildings.labels.total_apartments'))
            ->required()
            ->numeric()
            ->minValue(1)
            ->maxValue(1000)
            ->integer()
            ->validationAttribute('total_apartments')
            ->validationMessages(self::getValidationMessages('total_apartments'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('properties'))
            ->columns(self::getTableColumns())
            ->filters([
                //
            ])
            ->recordActions([
                // Table row actions removed - use page header actions instead
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('address', 'asc');
    }

    /**
     * Cached translations to avoid repeated __() calls during table rendering.
     *
     * @var array<string, string>|null
     */
    private static ?array $cachedTranslations = null;

    /**
     * Get cached translations for table columns.
     *
     * Caches translation strings on first access to improve performance when
     * rendering tables multiple times (pagination, filtering, sorting).
     *
     * @return array<string, string> Cached translation strings
     */
    private static function getCachedTranslations(): array
    {
        return self::$cachedTranslations ??= [
            'name' => __('buildings.labels.name'),
            'address' => __('buildings.labels.address'),
            'total_apartments' => __('buildings.labels.total_apartments'),
            'property_count' => __('buildings.labels.property_count'),
            'created_at' => __('buildings.labels.created_at'),
        ];
    }

    /**
     * Get table columns configuration.
     *
     * Defines the columns displayed in the building list table. All columns
     * are localized and most are searchable/sortable for easy navigation.
     *
     * Columns:
     * 1. **Name**: Building identifier (searchable, sortable)
     * 2. **Address**: Physical location (searchable, sortable, default sort)
     * 3. **Total Apartments**: Capacity (numeric display, sortable)
     * 4. **Properties Count**: Number of properties (relationship count, sortable)
     * 5. **Created At**: Timestamp (datetime, sortable, hidden by default)
     *
     * Default Sort: Address ascending (alphabetical order)
     *
     * Performance Notes:
     * - Properties count uses withCount() in modifyQueryUsing to avoid N+1 queries
     * - Translations are cached to avoid repeated __() calls
     * - Created At is toggleable to reduce visual clutter
     *
     * @return array<Tables\Columns\Column> Array of configured table columns
     *
     * @see \Filament\Tables\Columns\TextColumn
     */
    private static function getTableColumns(): array
    {
        $translations = self::getCachedTranslations();

        return [
            Tables\Columns\TextColumn::make('name')
                ->label($translations['name'])
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('address')
                ->label($translations['address'])
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('total_apartments')
                ->label($translations['total_apartments'])
                ->numeric()
                ->sortable(),

            Tables\Columns\TextColumn::make('properties_count')
                ->label($translations['property_count'])
                ->counts('properties')
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label($translations['created_at'])
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PropertiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBuildings::route('/'),
            'create' => Pages\CreateBuilding::route('/create'),
            'edit' => Pages\EditBuilding::route('/{record}/edit'),
        ];
    }
}
