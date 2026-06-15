<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Filament\Resources\TenantKycProfiles\RelationManagers\TenantKycDocumentsRelationManager as KycDocumentsTable;
use App\Filament\Resources\Tenants\TenantResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TenantKycDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'tenantKycDocuments';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenant_kyc.documents.title');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('tenant_kyc_documents_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withReviewRelations()->latestActivityFirst())
            ->columns(KycDocumentsTable::columns())
            ->recordActions(KycDocumentsTable::recordActions())
            ->defaultSort('updated_at', 'desc');
    }
}
