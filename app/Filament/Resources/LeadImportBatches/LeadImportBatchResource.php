<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadImportBatches;

use App\Filament\Resources\LeadImportBatches\Pages\ListLeadImportBatches;
use App\Filament\Resources\LeadImportBatches\Pages\ViewLeadImportBatch;
use App\Filament\Resources\LeadImportBatches\Schemas\LeadImportBatchInfolist;
use App\Filament\Resources\LeadImportBatches\Tables\LeadImportBatchesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\LeadImportBatch;
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

class LeadImportBatchResource extends Resource
{
    protected static ?string $model = LeadImportBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    public static function infolist(Schema $schema): Schema
    {
        return LeadImportBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadImportBatchesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.lead_import_batches.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.lead_import_batches.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.lead_import_batches.navigation');
    }

    /**
     * @return Builder<LeadImportBatch>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        return parent::getEloquentQuery()->forWorkspaceIndex(
            isSuperadmin: $user instanceof User && $user->isSuperadmin(),
            organizationId: app(OrganizationContext::class)->currentOrganizationId(),
        );
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('viewAny', LeadImportBatch::class) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof LeadImportBatch
            && (Auth::user()?->can('view', $record) ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListLeadImportBatches::route('/'),
            'view' => ViewLeadImportBatch::route('/{record}'),
        ];
    }
}
