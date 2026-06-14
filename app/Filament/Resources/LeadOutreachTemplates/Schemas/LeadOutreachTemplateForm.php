<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadOutreachTemplates\Schemas;

use App\Enums\LeadOutreachChannel;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class LeadOutreachTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.lead_outreach_templates.sections.details'))
                ->schema([
                    self::organizationField(),
                    TextInput::make('name')
                        ->label(__('admin.lead_outreach_templates.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('channel')
                        ->label(__('admin.lead_outreach_templates.fields.channel'))
                        ->options(LeadOutreachChannel::options())
                        ->default(LeadOutreachChannel::EMAIL->value)
                        ->required(),
                    TextInput::make('locale')
                        ->label(__('admin.lead_outreach_templates.fields.locale'))
                        ->default('en')
                        ->required()
                        ->maxLength(10),
                    TextInput::make('subject')
                        ->label(__('admin.lead_outreach_templates.fields.subject'))
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->label(__('admin.lead_outreach_templates.fields.is_active'))
                        ->default(true),
                    Textarea::make('body')
                        ->label(__('admin.lead_outreach_templates.fields.body'))
                        ->required()
                        ->rows(8)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    private static function organizationField(): Select|Hidden
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isSuperadmin() && app(OrganizationContext::class)->currentOrganizationId() === null) {
            return Select::make('organization_id')
                ->label(__('superadmin.organizations.singular'))
                ->options(fn (): array => Organization::query()
                    ->select(['id', 'name'])
                    ->ordered()
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->required();
        }

        return Hidden::make('organization_id')
            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId());
    }
}
