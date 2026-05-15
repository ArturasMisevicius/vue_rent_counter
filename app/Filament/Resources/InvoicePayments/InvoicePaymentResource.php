<?php

namespace App\Filament\Resources\InvoicePayments;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\InvoicePayments\Pages\CreateInvoicePayment;
use App\Filament\Resources\InvoicePayments\Pages\EditInvoicePayment;
use App\Filament\Resources\InvoicePayments\Pages\ListInvoicePayments;
use App\Filament\Resources\InvoicePayments\Pages\ViewInvoicePayment;
use App\Filament\Resources\InvoicePayments\Schemas\InvoicePaymentForm;
use App\Filament\Resources\InvoicePayments\Schemas\InvoicePaymentInfolist;
use App\Filament\Resources\InvoicePayments\Tables\InvoicePaymentsTable;
use App\Models\InvoicePayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicePaymentResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static ?string $model = InvoicePayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InvoicePaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoicePaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicePaymentsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.relation_resources.invoice_payments.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.relation_resources.invoice_payments.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forSuperadminIndex();
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
            'index' => ListInvoicePayments::route('/'),
            'create' => CreateInvoicePayment::route('/create'),
            'view' => ViewInvoicePayment::route('/{record}'),
            'edit' => EditInvoicePayment::route('/{record}/edit'),
        ];
    }
}
