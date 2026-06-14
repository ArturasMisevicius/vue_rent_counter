<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadOutreachTemplates;

use App\Filament\Resources\LeadOutreachTemplates\Pages\CreateLeadOutreachTemplate;
use App\Filament\Resources\LeadOutreachTemplates\Pages\EditLeadOutreachTemplate;
use App\Filament\Resources\LeadOutreachTemplates\Pages\ListLeadOutreachTemplates;
use App\Filament\Resources\LeadOutreachTemplates\Schemas\LeadOutreachTemplateForm;
use App\Filament\Resources\LeadOutreachTemplates\Tables\LeadOutreachTemplatesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\LeadOutreachTemplate;
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

class LeadOutreachTemplateResource extends Resource
{
    protected static ?string $model = LeadOutreachTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return LeadOutreachTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadOutreachTemplatesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.lead_outreach_templates.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.lead_outreach_templates.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.lead_outreach_templates.navigation');
    }

    /**
     * @return Builder<LeadOutreachTemplate>
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
        return Auth::user()?->can('viewAny', LeadOutreachTemplate::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('create', LeadOutreachTemplate::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof LeadOutreachTemplate
            && (Auth::user()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof LeadOutreachTemplate
            && (Auth::user()?->can('delete', $record) ?? false);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListLeadOutreachTemplates::route('/'),
            'create' => CreateLeadOutreachTemplate::route('/create'),
            'edit' => EditLeadOutreachTemplate::route('/{record}/edit'),
        ];
    }
}
