<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingGenerationLogs;

use App\Filament\Resources\BillingGenerationLogs\Pages\ListBillingGenerationLogs;
use App\Filament\Resources\BillingGenerationLogs\Pages\ViewBillingGenerationLog;
use App\Filament\Resources\BillingGenerationLogs\Schemas\BillingGenerationLogInfolist;
use App\Filament\Resources\BillingGenerationLogs\Tables\BillingGenerationLogsTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\BillingGenerationLog;
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

class BillingGenerationLogResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = BillingGenerationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    public static function infolist(Schema $schema): Schema
    {
        return BillingGenerationLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingGenerationLogsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.billing_generation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.billing_generation.plural');
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->can('viewAny', BillingGenerationLog::class) ?? false;
    }

    /**
     * @return Builder<BillingGenerationLog>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::currentUser();

        return parent::getEloquentQuery()->forWorkspaceIndex(
            isSuperadmin: $user?->isSuperadmin() ?? false,
            organizationId: app(OrganizationContext::class)->currentOrganizationId(),
        );
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof BillingGenerationLog
            && (self::currentUser()?->can('view', $record) ?? false);
    }

    /**
     * @return array<int, class-string>
     */
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
            'index' => ListBillingGenerationLogs::route('/'),
            'view' => ViewBillingGenerationLog::route('/{record}'),
        ];
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
