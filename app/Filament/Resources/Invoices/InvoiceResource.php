<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Resources\Invoices\Schemas\InvoiceInfolist;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use App\Support\Admin\OrganizationContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldCheckPolicyExistence = false;

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyEuro;

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

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    /**
     * @return Builder<Invoice>
     */
    public static function getEloquentQuery(): Builder
    {
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'due_date',
                'finalized_at',
                'paid_at',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId)
            ->with([
                'property:id,organization_id,building_id,name,unit_number',
                'tenant:id,name,email',
                'invoiceItems:id,invoice_id,description,quantity,unit,total',
                'payments:id,invoice_id,amount,method,paid_at',
                'emailLogs:id,invoice_id,recipient_email,sent_at',
                'reminderLogs:id,invoice_id,recipient_email,sent_at',
            ]);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof Invoice
            && $record->organization_id === app(OrganizationContext::class)->currentOrganizationId()
            && ($user?->isAdmin() || $user?->isManager());
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function getRelations(): array
    {
        return [];
    }

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
