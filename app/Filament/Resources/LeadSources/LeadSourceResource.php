<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadSources;

use App\Filament\Resources\LeadSources\Pages\CreateLeadSource;
use App\Filament\Resources\LeadSources\Pages\EditLeadSource;
use App\Filament\Resources\LeadSources\Pages\ListLeadSources;
use App\Filament\Resources\LeadSources\Schemas\LeadSourceForm;
use App\Filament\Resources\LeadSources\Tables\LeadSourcesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\LeadSource;
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

class LeadSourceResource extends Resource
{
    protected static ?string $model = LeadSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    public static function form(Schema $schema): Schema
    {
        return LeadSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadSourcesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.lead_sources.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.lead_sources.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.lead_sources.navigation');
    }

    /**
     * @return Builder<LeadSource>
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
        return Auth::user()?->can('viewAny', LeadSource::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('create', LeadSource::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof LeadSource
            && (Auth::user()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof LeadSource
            && (Auth::user()?->can('delete', $record) ?? false);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListLeadSources::route('/'),
            'create' => CreateLeadSource::route('/create'),
            'edit' => EditLeadSource::route('/{record}/edit'),
        ];
    }
}
