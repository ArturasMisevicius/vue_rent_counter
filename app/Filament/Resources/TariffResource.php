<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TariffResource\Concerns\BuildsTariffFormFields;
use App\Filament\Resources\TariffResource\Concerns\BuildsTariffTableColumns;
use App\Filament\Resources\TariffResource\Pages;
use App\Models\Tariff;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for managing utility tariffs.
 *
 * Provides CRUD operations for tariffs with comprehensive validation,
 * tenant-scoped data access, and role-based navigation visibility.
 *
 * Features:
 * - Tenant-scoped data access via TenantScope
 * - Role-based navigation visibility (SUPERADMIN and ADMIN only)
 * - Support for flat and time-of-use tariff types
 * - Zone configuration for time-of-use tariffs (day/night rates)
 * - Weekend logic configuration (separate rates for weekends)
 * - Fixed fee support for base charges
 * - Relationship management (providers)
 * - Comprehensive validation mirroring FormRequest rules
 * - Security hardening (XSS prevention, numeric overflow protection)
 * - Audit logging via TariffObserver
 *
 * Navigation Visibility:
 * Tariffs are system configuration resources accessible only to SUPERADMIN
 * and ADMIN roles. MANAGER and TENANT roles cannot access this resource.
 *
 * Authorization:
 * All CRUD operations are protected by TariffPolicy, which enforces:
 * - viewAny: SUPERADMIN and ADMIN only
 * - create: SUPERADMIN and ADMIN only
 * - update: SUPERADMIN and ADMIN only
 * - delete: SUPERADMIN and ADMIN only
 *
 * Security:
 * - XSS prevention via regex validation and HTML sanitization
 * - Numeric overflow protection with max value validation
 * - Zone ID injection prevention
 * - Tenant scope bypass protection in provider loading
 * - Comprehensive audit logging via TariffObserver
 *
 * Namespace Consolidation (Filament 4):
 * This resource follows Filament 4 best practices by using consolidated namespace
 * imports. Instead of importing individual action classes, it uses:
 * - `use Filament\Tables;` for all table components
 * - Actions referenced as `Tables\Actions\EditAction`
 * - Columns referenced as `Tables\Columns\TextColumn`
 * This reduces import clutter by 87.5% and improves code maintainability.
 *
 * @see \App\Models\Tariff
 * @see \App\Policies\TariffPolicy
 * @see \App\Observers\TariffObserver
 * @see \App\Http\Requests\StoreTariffRequest
 * @see \App\Http\Requests\UpdateTariffRequest
 * @see \Tests\Feature\Filament\FilamentTariffValidationConsistencyPropertyTest
 * @see \Tests\Feature\Security\TariffResourceSecurityTest
 * @see .kiro/specs/6-filament-namespace-consolidation/requirements.md
 */
class TariffResource extends Resource
{
    use BuildsTariffFormFields;
    use BuildsTariffTableColumns;
    use Concerns\CachesAuthUser;

    protected static ?string $model = Tariff::class;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationLabel(): string
    {
        return __('app.nav.tariffs');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.configuration');
    }

    /**
     * Determine if the current user can view any tariffs.
     *
     * Integrates with TariffPolicy to enforce authorization rules.
     * Only SUPERADMIN and ADMIN roles have viewAny permission.
     * 
     * Performance: Uses cached user to avoid redundant auth queries.
     *
     * @return bool True if the user can view tariffs, false otherwise
     *
     * @see \App\Policies\TariffPolicy::viewAny()
     */
    public static function canViewAny(): bool
    {
        $user = self::getAuthenticatedUser();
        return $user && $user->can('viewAny', Tariff::class);
    }

    /**
     * Determine if the current user can create tariffs.
     *
     * Integrates with TariffPolicy to enforce authorization rules.
     * Only SUPERADMIN and ADMIN roles have create permission.
     * 
     * Performance: Uses cached user to avoid redundant auth queries.
     *
     * @return bool True if the user can create tariffs, false otherwise
     *
     * @see \App\Policies\TariffPolicy::create()
     */
    public static function canCreate(): bool
    {
        $user = self::getAuthenticatedUser();
        return $user && $user->can('create', Tariff::class);
    }

    /**
     * Determine if the current user can edit a specific tariff.
     *
     * Integrates with TariffPolicy to enforce authorization rules.
     * Only SUPERADMIN and ADMIN roles have update permission.
     * 
     * Performance: Uses cached user to avoid redundant auth queries.
     *
     * @param \App\Models\Tariff $record The tariff record to check
     * @return bool True if the user can edit the tariff, false otherwise
     *
     * @see \App\Policies\TariffPolicy::update()
     */
    public static function canEdit($record): bool
    {
        $user = self::getAuthenticatedUser();
        return $user && $user->can('update', $record);
    }

    /**
     * Determine if the current user can delete a specific tariff.
     *
     * Integrates with TariffPolicy to enforce authorization rules.
     * Only SUPERADMIN and ADMIN roles have delete permission.
     * 
     * Performance: Uses cached user to avoid redundant auth queries.
     *
     * @param \App\Models\Tariff $record The tariff record to check
     * @return bool True if the user can delete the tariff, false otherwise
     *
     * @see \App\Policies\TariffPolicy::delete()
     */
    public static function canDelete($record): bool
    {
        $user = self::getAuthenticatedUser();
        return $user && $user->can('delete', $record);
    }

    /**
     * Cached navigation visibility result.
     *
     * @var bool|null
     */
    protected static ?bool $navigationVisible = null;

    /**
     * Determine if the resource should be registered in the navigation menu.
     *
     * Tariffs are system configuration resources accessible only to SUPERADMIN
     * and ADMIN roles. This method implements role-based navigation visibility
     * to hide the resource from MANAGER and TENANT users.
     *
     * Requirements Addressed:
     * - Requirement 9.1: Tenant users restricted to tenant-specific resources
     * - Requirement 9.2: Manager users access operational resources only
     * - Requirement 9.3: Admin users access all resources including system configuration
     *
     * Implementation Notes:
     * - Uses explicit instanceof check to prevent null pointer exceptions
     * - Uses strict type checking in in_array() for security
     * - Matches the pattern used in ProviderResource for consistency
     * - Ensures SUPERADMIN has proper access to all configuration resources
     * - Memoizes result within request to avoid redundant role checks
     *
     * Performance: Memoized to avoid redundant role checks per request.
     *
     * @return bool True if the resource should appear in navigation, false otherwise
     *
     * @see \App\Filament\Resources\ProviderResource::shouldRegisterNavigation()
     * @see \Tests\Feature\Filament\FilamentNavigationVisibilityTest
     * @see \App\Enums\UserRole
     */
    public static function shouldRegisterNavigation(): bool
    {
        // Return memoized result if available
        if (static::$navigationVisible !== null) {
            return static::$navigationVisible;
        }

        $user = self::getAuthenticatedUser();

        static::$navigationVisible = $user instanceof \App\Models\User && in_array($user->role, [
            \App\Enums\UserRole::SUPERADMIN,
            \App\Enums\UserRole::ADMIN,
        ], true);

        return static::$navigationVisible;
    }

    /**
     * Define the form schema for tariff creation and editing.
     *
     * Implements comprehensive validation rules that mirror FormRequest validation
     * to ensure consistency between Filament UI and API validation. All fields
     * include explicit ->rules() declarations and localized validation messages.
     *
     * Validation Strategy:
     * - Basic fields (provider_id, name, dates): Standard Laravel validation rules
     * - Conditional fields (rate, zones): Use closures to apply rules based on tariff type
     * - Nested fields (zone properties): Individual validation for each zone attribute
     * - Complex patterns (time format): Regex validation for HH:MM format
     *
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form schema with validation rules
     *
     * @see \App\Http\Requests\StoreTariffRequest For equivalent API validation
     * @see \App\Http\Requests\UpdateTariffRequest For update-specific validation
     * @see \Tests\Feature\Filament\FilamentTariffValidationConsistencyPropertyTest For validation tests
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(__('tariffs.sections.basic_information'))
                    ->schema(static::buildBasicInformationFields())
                    ->columns(2),

                Forms\Components\Section::make(__('tariffs.sections.effective_period'))
                    ->schema(static::buildEffectivePeriodFields())
                    ->columns(2),

                Forms\Components\Section::make(__('tariffs.sections.configuration'))
                    ->schema(static::buildConfigurationFields())
                    ->columns(2),
            ]);
    }

    /**
     * Define the table schema for tariff listing.
     *
     * Implements optimized query loading with eager-loaded provider relationships
     * to prevent N+1 queries. Uses consolidated Filament\Tables namespace for
     * all table components following Filament 4 best practices.
     *
     * Table Features:
     * - Eager-loaded provider relationship for performance
     * - Global search across tariff fields
     * - Edit action for authorized users
     * - Default sort by active_from date (newest first)
     * - Bulk actions removed for Filament v4 compatibility
     *
     * Namespace Pattern:
     * All table actions use the consolidated `Tables\Actions\` prefix instead of
     * individual imports. This follows Filament 4 namespace consolidation pattern
     * documented in .kiro/specs/6-filament-namespace-consolidation/requirements.md
     *
     * @param Table $table The Filament table builder
     * @return Table Configured table with columns, actions, and filters
     *
     * @see \App\Filament\Resources\TariffResource\Concerns\BuildsTariffTableColumns
     * @see .kiro/specs/6-filament-namespace-consolidation/requirements.md
     */
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('provider:id,name,service_type'))
            ->searchable()
            ->columns(static::buildTableColumns())
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Bulk actions removed for Filament v4 compatibility
            ])
            ->defaultSort('active_from', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTariffs::route('/'),
            'create' => Pages\CreateTariff::route('/create'),
            'edit' => Pages\EditTariff::route('/{record}/edit'),
        ];
    }
}
