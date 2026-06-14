<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListingLeads;

use App\Filament\Resources\ListingLeads\Pages\CreateListingLead;
use App\Filament\Resources\ListingLeads\Pages\EditListingLead;
use App\Filament\Resources\ListingLeads\Pages\ListListingLeads;
use App\Filament\Resources\ListingLeads\Pages\ViewListingLead;
use App\Filament\Resources\ListingLeads\Schemas\ListingLeadForm;
use App\Filament\Resources\ListingLeads\Schemas\ListingLeadInfolist;
use App\Filament\Resources\ListingLeads\Tables\ListingLeadsTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\ListingLead;
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

class ListingLeadResource extends Resource
{
    protected static ?string $model = ListingLead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $recordTitleAttribute = 'listing_title';

    public static function form(Schema $schema): Schema
    {
        return ListingLeadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ListingLeadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ListingLeadsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.leads.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.leads.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.leads.navigation');
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
     * @return Builder<ListingLead>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::currentUser();

        if (! $user instanceof User) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()->forWorkspaceIndex(
            $user,
            app(OrganizationContext::class)->currentOrganizationId(),
        );
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->can('viewAny', ListingLead::class) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof ListingLead
            && (self::currentUser()?->can('view', $record) ?? false);
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->can('create', ListingLead::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof ListingLead
            && (self::currentUser()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof ListingLead
            && (self::currentUser()?->can('delete', $record) ?? false);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListListingLeads::route('/'),
            'create' => CreateListingLead::route('/create'),
            'view' => ViewListingLead::route('/{record}'),
            'edit' => EditListingLead::route('/{record}/edit'),
        ];
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
