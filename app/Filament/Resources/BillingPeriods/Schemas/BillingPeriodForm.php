<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPeriods\Schemas;

use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class BillingPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.billing_periods.sections.period'))
                    ->schema([
                        self::organizationField(),
                        TextInput::make('name')
                            ->label(__('admin.billing_periods.fields.name'))
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('starts_at')
                            ->label(__('admin.billing_periods.fields.starts_at'))
                            ->required()
                            ->live(),
                        DatePicker::make('ends_at')
                            ->label(__('admin.billing_periods.fields.ends_at'))
                            ->required()
                            ->rules(['after_or_equal:starts_at']),
                    ])
                    ->columns(2),
                Section::make(__('admin.billing_periods.sections.schedule'))
                    ->schema([
                        DatePicker::make('reading_submission_deadline')
                            ->label(__('admin.billing_periods.fields.reading_submission_deadline'))
                            ->required()
                            ->rules(['after_or_equal:starts_at']),
                        DatePicker::make('invoice_generation_date')
                            ->label(__('admin.billing_periods.fields.invoice_generation_date'))
                            ->required()
                            ->rules(['after_or_equal:ends_at']),
                        DatePicker::make('payment_due_date')
                            ->label(__('admin.billing_periods.fields.payment_due_date'))
                            ->required()
                            ->rules(['after_or_equal:invoice_generation_date']),
                    ])
                    ->columns(3),
            ]);
    }

    private static function organizationField(): Select|Hidden
    {
        if (! self::requiresOrganizationSelection()) {
            return Hidden::make('organization_id')
                ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
                ->dehydrated();
        }

        return Select::make('organization_id')
            ->label(__('superadmin.organizations.singular'))
            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
            ->options(fn (): array => Organization::query()
                ->select(['id', 'name'])
                ->ordered()
                ->pluck('name', 'id')
                ->all())
            ->searchable()
            ->preload()
            ->required();
    }

    private static function requiresOrganizationSelection(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->isSuperadmin()
            && app(OrganizationContext::class)->currentOrganizationId() === null;
    }
}
