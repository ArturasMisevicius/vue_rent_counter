<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Resources\Invoices\Schemas\InvoiceInfolist;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\Invoice;
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

class InvoiceResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.invoices.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.invoices.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::currentUser()?->isTenant()
            ? __('shell.navigation.groups.my_home')
            : __('shell.navigation.groups.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.invoices.navigation');
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
     * @return Builder<Invoice>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = static::currentUser();

        if ($user?->isTenant()) {
            $workspace = app(WorkspaceResolver::class)->resolveFor($user);

            if ($workspace->organizationId === null || $workspace->propertyId === null) {
                return parent::getEloquentQuery()->whereKey(-1);
            }

            return parent::getEloquentQuery()
                ->forTenantWorkspace($workspace->organizationId, $workspace->userId, $workspace->propertyId);
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        return parent::getEloquentQuery()
            ->forWorkspaceIndex($user?->isSuperadmin() ?? false, $organizationId);
    }

    public static function canViewAny(): bool
    {
        return static::allows('viewAny', Invoice::class);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Invoice
            && static::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Invoice
            && static::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Invoice
            && static::allows('delete', $record);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::guard()->user();

        return $user instanceof User ? $user : null;
    }

    private static function allows(string $ability, Invoice|string $subject): bool
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
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
