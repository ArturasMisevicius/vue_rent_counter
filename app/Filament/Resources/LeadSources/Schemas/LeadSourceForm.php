<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadSources\Schemas;

use App\Enums\LeadSourceType;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class LeadSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.lead_sources.sections.details'))
                ->schema([
                    self::organizationField(),
                    TextInput::make('name')
                        ->label(__('admin.lead_sources.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->label(__('admin.lead_sources.fields.type'))
                        ->options(LeadSourceType::options())
                        ->default(LeadSourceType::ARUODAS_CSV->value)
                        ->required(),
                    TextInput::make('source_url')
                        ->label(__('admin.lead_sources.fields.source_url'))
                        ->url()
                        ->maxLength(255),
                    TextInput::make('retention_days')
                        ->label(__('admin.lead_sources.fields.retention_days'))
                        ->integer()
                        ->minValue(1)
                        ->default(180),
                    Textarea::make('description')
                        ->label(__('admin.lead_sources.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('privacy_note')
                        ->label(__('admin.lead_sources.fields.privacy_note'))
                        ->rows(3)
                        ->required()
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
