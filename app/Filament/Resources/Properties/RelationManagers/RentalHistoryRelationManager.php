<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\RelationManagers\RentalContractsRelationManager;
use App\Models\Property;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RentalHistoryRelationManager extends RentalContractsRelationManager
{
    protected static string $relationship = 'rentalContracts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return PropertyResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.properties.tabs.rental_history');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('rental_contracts_count');

        return (string) ($count ?? $ownerRecord->rentalContracts()->count());
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->heading(__('admin.properties.tabs.rental_history'));
    }

    protected function property(): Property
    {
        $owner = $this->getOwnerRecord();

        abort_unless($owner instanceof Property, 404);

        return $owner;
    }
}
