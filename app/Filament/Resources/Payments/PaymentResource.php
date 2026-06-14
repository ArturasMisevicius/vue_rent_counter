<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\Schemas\PaymentInfolist;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\InvoicePayment;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends Resource
{
    protected static ?string $model = InvoicePayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static bool $shouldRegisterNavigation = false;

    public static function infolist(Schema $schema): Schema
    {
        return PaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.payments.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.payments.plural');
    }

    public static function canAccess(): bool
    {
        return static::currentUser()?->isAdminLike() ?? false;
    }

    /**
     * @return Builder<InvoicePayment>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = static::currentUser();

        if ($user?->isSuperadmin()) {
            return parent::getEloquentQuery()->forSuperadminIndex();
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId() ?? $user?->organization_id;

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()->forAdminPaymentIndex($organizationId);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'view' => ViewPayment::route('/{record}'),
        ];
    }

    public static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
