<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraChargeTypes\Schemas;

use App\Enums\ExtraChargeTypeCode;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ExtraChargeTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.extra_charge_types.sections.details'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('superadmin.organizations.singular'))
                            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
                            ->options(fn (): array => Organization::query()
                                ->select(['id', 'name'])
                                ->ordered()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(fn (): bool => self::requiresOrganizationSelection())
                            ->visible(fn (): bool => self::requiresOrganizationSelection())
                            ->dehydratedWhenHidden(),
                        TextInput::make('name')
                            ->label(__('admin.extra_charge_types.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('admin.extra_charge_types.fields.type'))
                            ->options(ExtraChargeTypeCode::options())
                            ->required(),
                        TextInput::make('default_amount')
                            ->label(__('admin.extra_charge_types.fields.default_amount'))
                            ->numeric()
                            ->required()
                            ->default('0.00'),
                        TextInput::make('currency')
                            ->label(__('admin.extra_charge_types.fields.currency'))
                            ->required()
                            ->default('EUR')
                            ->minLength(3)
                            ->maxLength(3),
                    ])
                    ->columns(2),
                Section::make(__('admin.extra_charge_types.sections.rules'))
                    ->schema([
                        Toggle::make('is_recurring')
                            ->label(__('admin.extra_charge_types.fields.is_recurring'))
                            ->default(false),
                        Toggle::make('is_taxable')
                            ->label(__('admin.extra_charge_types.fields.is_taxable'))
                            ->default(false),
                        Toggle::make('tenant_visible_by_default')
                            ->label(__('admin.extra_charge_types.fields.tenant_visible_by_default'))
                            ->default(true),
                        Toggle::make('requires_comment')
                            ->label(__('admin.extra_charge_types.fields.requires_comment'))
                            ->default(false),
                        Toggle::make('requires_attachment')
                            ->label(__('admin.extra_charge_types.fields.requires_attachment'))
                            ->default(false),
                        Toggle::make('is_active')
                            ->label(__('admin.extra_charge_types.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }

    private static function requiresOrganizationSelection(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->isSuperadmin()
            && app(OrganizationContext::class)->currentOrganizationId() === null;
    }
}
