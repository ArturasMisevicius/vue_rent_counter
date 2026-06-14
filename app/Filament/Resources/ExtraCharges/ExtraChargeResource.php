<?php

namespace App\Filament\Resources\ExtraCharges;

use App\Filament\Resources\ExtraCharges\Pages\CreateExtraCharge;
use App\Filament\Resources\ExtraCharges\Pages\EditExtraCharge;
use App\Filament\Resources\ExtraCharges\Pages\ListExtraCharges;
use App\Filament\Resources\ExtraCharges\Pages\ViewExtraCharge;
use App\Filament\Resources\ExtraCharges\Schemas\ExtraChargeForm;
use App\Filament\Resources\ExtraCharges\Schemas\ExtraChargeInfolist;
use App\Filament\Resources\ExtraCharges\Tables\ExtraChargesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\ExtraCharge;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExtraChargeResource extends Resource
{
    protected static ?string $model = ExtraCharge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ExtraChargeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExtraChargeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExtraChargesTable::configure($table);
    }

    /**
     * @return Builder<ExtraCharge>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::guard()->user();

        if ($user instanceof User && $user->isSuperadmin()) {
            return parent::getEloquentQuery();
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()->where('organization_id', $organizationId);
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
            'index' => ListExtraCharges::route('/'),
            'create' => CreateExtraCharge::route('/create'),
            'view' => ViewExtraCharge::route('/{record}'),
            'edit' => EditExtraCharge::route('/{record}/edit'),
        ];
    }
}
