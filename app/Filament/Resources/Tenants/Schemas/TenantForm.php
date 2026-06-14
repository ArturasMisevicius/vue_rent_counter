<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Schemas;

use App\Enums\BillingReadinessStatus;
use App\Enums\PropertyAssignmentStatus;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Tenants\CheckTenantBillingReadiness;
use App\Filament\Support\Tenants\TenantBillingReadinessResult;
use App\Filament\Support\Tenants\TenantLeaseAgreement;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make(self::wizardSteps())
                    ->visible(fn (?User $record): bool => $record === null)
                    ->columnSpanFull(),
                ...self::editSections(),
            ]);
    }

    /**
     * @return array<int, Step>
     */
    public static function wizardSteps(): array
    {
        return [
            Step::make(__('admin.tenants.wizard.steps.tenant_details'))
                ->schema([
                    Section::make(__('admin.tenants.sections.personal_information'))
                        ->description(__('admin.tenants.messages.invitation_onboarding_hint'))
                        ->schema([
                            self::organizationSelect(),
                            Hidden::make('name')
                                ->default(fn (Get $get): string => self::nameFromParts($get)),
                            TextInput::make('first_name')
                                ->label(__('admin.tenants.fields.first_name'))
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, Get $get): mixed => $set('name', self::nameFromParts($get))),
                            TextInput::make('last_name')
                                ->label(__('admin.tenants.fields.last_name'))
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, Get $get): mixed => $set('name', self::nameFromParts($get))),
                            TextInput::make('email')
                                ->label(__('admin.tenants.fields.email_address'))
                                ->email()
                                ->required()
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->label(__('admin.tenants.fields.phone_number'))
                                ->maxLength(255),
                            Select::make('locale')
                                ->label(__('admin.tenants.fields.preferred_language'))
                                ->options(config('tenanto.locales', []))
                                ->default(app()->getLocale())
                                ->required(),
                            Textarea::make('internal_note')
                                ->label(__('admin.tenants.fields.internal_note'))
                                ->rows(3)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ]),
            Step::make(__('admin.tenants.wizard.steps.property_assignment'))
                ->schema([
                    Section::make(__('admin.tenants.sections.property_assignment'))
                        ->schema([
                            Select::make('building_id')
                                ->label(__('admin.tenants.fields.building'))
                                ->options(fn (Get $get): array => self::buildingOptions($get('organization_id')))
                                ->default(fn (): ?int => self::defaultBuildingIdFromQuery())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('property_id', null);
                                    $set('unit_area_sqm', null);
                                }),
                            Select::make('property_id')
                                ->label(__('admin.tenants.fields.property'))
                                ->placeholder(__('admin.tenants.empty.no_assignment_yet'))
                                ->options(fn (Get $get): array => self::propertyOptions($get('organization_id'), $get('building_id')))
                                ->default(fn (): ?int => self::defaultPropertyIdFromQuery())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
                                    $property = self::findProperty($state, $get('organization_id'));

                                    if ($property === null) {
                                        $set('unit_area_sqm', null);

                                        return;
                                    }

                                    $set('unit_area_sqm', $property->floor_area_sqm !== null
                                        ? (float) $property->floor_area_sqm
                                        : null);
                                }),
                            DatePicker::make('move_in_date')
                                ->label(__('admin.tenants.fields.move_in_date'))
                                ->default(today()->toDateString())
                                ->required(fn (Get $get): bool => filled($get('property_id')) && $get('assignment_status') === PropertyAssignmentStatus::ACTIVE->value),
                            DatePicker::make('move_out_date')
                                ->label(__('admin.tenants.fields.move_out_date'))
                                ->minDate(fn (Get $get): mixed => $get('move_in_date')),
                            Select::make('assignment_status')
                                ->label(__('admin.tenants.fields.assignment_status'))
                                ->options(PropertyAssignmentStatus::options())
                                ->default(PropertyAssignmentStatus::ACTIVE->value)
                                ->required()
                                ->live(),
                            Toggle::make('is_primary')
                                ->label(__('admin.tenants.fields.primary_tenant'))
                                ->default(true)
                                ->helperText(__('admin.tenants.messages.primary_tenant_first_version')),
                            TextInput::make('occupants_count')
                                ->label(__('admin.tenants.fields.occupants_count'))
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(50),
                            TextInput::make('unit_area_sqm')
                                ->label(__('admin.tenants.fields.unit_area_sqm'))
                                ->numeric()
                                ->minValue(0)
                                ->default(fn (): ?float => self::defaultUnitAreaFromQuery())
                                ->helperText(fn (Get $get): ?string => self::unitAreaHelperText(
                                    $get('property_id'),
                                    $get('organization_id'),
                                )),
                        ])
                        ->columns(2),
                ]),
            Step::make(__('admin.tenants.wizard.steps.portal_access'))
                ->schema([
                    Section::make(__('admin.tenants.sections.portal_access'))
                        ->schema([
                            Toggle::make('create_portal_access')
                                ->label(__('admin.tenants.fields.create_portal_access'))
                                ->default(true)
                                ->live()
                                ->afterStateUpdated(function (bool $state, Set $set): void {
                                    if (! $state) {
                                        $set('send_invitation_now', false);
                                    }
                                }),
                            Toggle::make('send_invitation_now')
                                ->label(__('admin.tenants.fields.send_invitation_now'))
                                ->default(true)
                                ->live()
                                ->disabled(fn (Get $get): bool => ! (bool) $get('create_portal_access')),
                            TextInput::make('invitation_expiration_days')
                                ->label(__('admin.tenants.fields.invitation_expiration_days'))
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(60)
                                ->default(7)
                                ->required()
                                ->visible(fn (Get $get): bool => (bool) $get('create_portal_access') && (bool) $get('send_invitation_now')),
                        ])
                        ->columns(3),
                ]),
            Step::make(__('admin.tenants.wizard.steps.billing_setup'))
                ->schema([
                    Section::make(__('admin.tenants.sections.billing_readiness'))
                        ->schema([
                            Placeholder::make('billing_readiness_preview')
                                ->label(__('admin.tenants.billing_readiness.preview_title'))
                                ->content(fn (Get $get): HtmlString => self::billingReadinessContent($get))
                                ->columnSpanFull(),
                        ]),
                ]),
            Step::make(__('admin.tenants.wizard.steps.documents'))
                ->schema([
                    Section::make(__('admin.tenants.sections.lease_agreement'))
                        ->description(__('admin.tenants.messages.lease_agreement_hint'))
                        ->schema([
                            self::leaseAgreementUpload(),
                        ]),
                ]),
            Step::make(__('admin.tenants.wizard.steps.review'))
                ->schema([
                    Section::make(__('admin.tenants.wizard.review.title'))
                        ->schema([
                            Placeholder::make('review_summary')
                                ->label(__('admin.tenants.wizard.review.summary'))
                                ->content(fn (Get $get): HtmlString => self::reviewContent($get))
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }

    /**
     * @return array<int, Section>
     */
    private static function editSections(): array
    {
        return [
            Section::make(__('admin.tenants.sections.personal_information'))
                ->description(__('admin.tenants.messages.invitation_onboarding_hint'))
                ->schema([
                    self::organizationSelect(),
                    TextInput::make('name')
                        ->label(__('admin.tenants.fields.full_name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label(__('admin.tenants.fields.email_address'))
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label(__('admin.tenants.fields.phone_number'))
                        ->maxLength(255),
                    Select::make('locale')
                        ->label(__('admin.tenants.fields.preferred_language'))
                        ->options(config('tenanto.locales', []))
                        ->required(),
                ])
                ->columns(2)
                ->visible(fn (?User $record): bool => $record !== null),
            Section::make(__('admin.tenants.sections.property_assignment'))
                ->schema([
                    Select::make('property_id')
                        ->label(__('admin.tenants.fields.property'))
                        ->placeholder(__('admin.tenants.empty.no_assignment_yet'))
                        ->options(fn (Get $get): array => self::propertyOptions($get('organization_id')))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
                            $property = self::findProperty($state, $get('organization_id'));

                            if ($property === null) {
                                $set('unit_area_sqm', null);

                                return;
                            }

                            $set('unit_area_sqm', $property->floor_area_sqm !== null
                                ? (float) $property->floor_area_sqm
                                : null);
                        })
                        ->default(fn (?User $record): ?int => $record?->currentPropertyAssignment?->property_id),
                    TextInput::make('unit_area_sqm')
                        ->label(__('admin.tenants.fields.unit_area_sqm'))
                        ->numeric()
                        ->minValue(0)
                        ->helperText(fn (Get $get): ?string => self::unitAreaHelperText(
                            $get('property_id'),
                            $get('organization_id'),
                        ))
                        ->default(fn (?User $record): mixed => $record?->currentPropertyAssignment?->unit_area_sqm),
                ])
                ->columns(2)
                ->visible(fn (?User $record): bool => $record !== null),
            Section::make(__('admin.tenants.sections.lease_agreement'))
                ->description(__('admin.tenants.messages.lease_agreement_hint'))
                ->schema([
                    self::leaseAgreementUpload(),
                ])
                ->visible(fn (?User $record): bool => $record !== null),
        ];
    }

    private static function organizationSelect(): Select
    {
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
            ->live()
            ->dehydratedWhenHidden()
            ->required(fn (): bool => self::showsOrganizationSelector())
            ->visible(fn (): bool => self::showsOrganizationSelector())
            ->afterStateUpdated(function (Set $set): void {
                $set('building_id', null);
                $set('property_id', null);
                $set('unit_area_sqm', null);
            });
    }

    private static function leaseAgreementUpload(): FileUpload
    {
        return FileUpload::make(TenantLeaseAgreement::FIELD)
            ->label(__('admin.tenants.fields.lease_agreement'))
            ->disk(TenantLeaseAgreement::DISK)
            ->directory(TenantLeaseAgreement::DIRECTORY)
            ->visibility('private')
            ->acceptedFileTypes(TenantLeaseAgreement::acceptedFileTypes())
            ->maxSize(TenantLeaseAgreement::MAX_SIZE_KB)
            ->openable()
            ->downloadable()
            ->storeFileNamesIn(TenantLeaseAgreement::fileNamesStatePath());
    }

    /**
     * @return array<int, string>
     */
    private static function buildingOptions(mixed $selectedOrganizationId = null): array
    {
        $organizationId = self::resolveOrganizationId(self::currentTenant(), $selectedOrganizationId);

        if ($organizationId === null) {
            return [];
        }

        return Building::query()
            ->select(['id', 'organization_id', 'name'])
            ->forOrganization($organizationId)
            ->ordered()
            ->get()
            ->mapWithKeys(fn (Building $building): array => [
                $building->id => $building->displayName(),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function propertyOptions(mixed $selectedOrganizationId = null, mixed $selectedBuildingId = null): array
    {
        $tenant = self::currentTenant();
        $organizationId = self::resolveOrganizationId($tenant, $selectedOrganizationId);

        if ($organizationId === null) {
            return [];
        }

        return Property::query()
            ->select([
                'id',
                'organization_id',
                'building_id',
                'name',
                'floor',
                'unit_number',
                'type',
                'floor_area_sqm',
            ])
            ->availableForTenantAssignment($organizationId, $tenant?->id)
            ->when(
                filled($selectedBuildingId),
                fn ($query) => $query->where('building_id', (int) $selectedBuildingId),
            )
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                $property->id => $property->tenantAssignmentLabel(),
            ])
            ->all();
    }

    private static function currentTenant(): ?User
    {
        $record = request()->route('record');

        if ($record instanceof User) {
            return $record;
        }

        if (! is_scalar($record) || blank($record)) {
            return null;
        }

        return User::query()
            ->select(['id', 'organization_id'])
            ->find($record);
    }

    private static function findProperty(mixed $propertyId, mixed $selectedOrganizationId = null): ?Property
    {
        if (blank($propertyId)) {
            return null;
        }

        $tenant = self::currentTenant();
        $organizationId = self::resolveOrganizationId($tenant, $selectedOrganizationId);

        if ($organizationId === null) {
            return null;
        }

        return Property::query()
            ->select([
                'id',
                'organization_id',
                'building_id',
                'name',
                'floor',
                'unit_number',
                'type',
                'floor_area_sqm',
            ])
            ->availableForTenantAssignment($organizationId, $tenant?->id)
            ->find($propertyId);
    }

    private static function unitAreaHelperText(mixed $propertyId, mixed $selectedOrganizationId = null): ?string
    {
        $property = self::findProperty($propertyId, $selectedOrganizationId);

        if ($property?->floor_area_sqm === null) {
            return null;
        }

        return __('admin.tenants.messages.unit_area_defaults_to_property', [
            'area' => $property->areaDisplay(),
        ]);
    }

    private static function resolveOrganizationId(?User $tenant, mixed $selectedOrganizationId = null): ?int
    {
        if ($tenant?->organization_id !== null) {
            return $tenant->organization_id;
        }

        $currentOrganizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($currentOrganizationId !== null) {
            return $currentOrganizationId;
        }

        if (blank($selectedOrganizationId)) {
            return null;
        }

        return (int) $selectedOrganizationId;
    }

    private static function showsOrganizationSelector(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->isSuperadmin()
            && self::currentTenant() === null
            && app(OrganizationContext::class)->currentOrganizationId() === null;
    }

    private static function defaultPropertyIdFromQuery(): ?int
    {
        $propertyId = request()->integer('property_id');

        return $propertyId > 0 ? $propertyId : null;
    }

    private static function defaultBuildingIdFromQuery(): ?int
    {
        $property = self::findProperty(self::defaultPropertyIdFromQuery());

        return $property?->building_id;
    }

    private static function defaultUnitAreaFromQuery(): ?float
    {
        $property = self::findProperty(self::defaultPropertyIdFromQuery());

        return $property?->floor_area_sqm !== null ? (float) $property->floor_area_sqm : null;
    }

    private static function nameFromParts(Get $get): string
    {
        $name = $get('name');

        if (filled($name)) {
            return (string) $name;
        }

        return trim(collect([$get('first_name'), $get('last_name')])
            ->filter(fn (mixed $part): bool => filled($part))
            ->implode(' '));
    }

    private static function billingReadinessContent(Get $get): HtmlString
    {
        $result = self::billingReadinessResult($get);
        $toneClass = match ($result->status) {
            BillingReadinessStatus::READY => 'text-emerald-700',
            BillingReadinessStatus::WARNING => 'text-amber-700',
            BillingReadinessStatus::BLOCKED => 'text-rose-700',
            BillingReadinessStatus::NOT_CONFIGURED => 'text-slate-600',
        };

        $items = collect($result->checks)
            ->map(function (array $check): string {
                $message = $check['message'] !== null ? '<span class="text-slate-500">'.e($check['message']).'</span>' : '';

                return '<li><span class="font-medium text-slate-800">'.e($check['label']).':</span> '.$message.'</li>';
            })
            ->implode('');

        $warnings = collect([...$result->blockingErrors, ...$result->warnings])
            ->map(fn (string $warning): string => '<li>'.e($warning).'</li>')
            ->implode('');

        return new HtmlString(
            '<div class="space-y-3 text-sm">'.
            '<p class="font-semibold '.$toneClass.'">'.e(__('admin.tenants.billing_readiness.status_label', [
                'status' => $result->status->getLabel(),
            ])).'</p>'.
            ($items !== '' ? '<ul class="list-disc space-y-1 pl-5">'.$items.'</ul>' : '').
            ($warnings !== '' ? '<div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-800"><ul class="list-disc space-y-1 pl-5">'.$warnings.'</ul></div>' : '').
            '</div>',
        );
    }

    private static function reviewContent(Get $get): HtmlString
    {
        $property = self::findProperty($get('property_id'), $get('organization_id'));
        $readiness = self::billingReadinessResult($get);
        $name = self::nameFromParts($get);
        $invitation = (bool) $get('send_invitation_now')
            ? __('admin.tenants.wizard.review.invitation_will_be_sent')
            : __('admin.tenants.wizard.review.invitation_not_sent');

        $rows = [
            __('admin.tenants.wizard.review.tenant', ['tenant' => $name !== '' ? $name : '—']),
            __('admin.tenants.wizard.review.email', ['email' => (string) ($get('email') ?: '—')]),
            __('admin.tenants.wizard.review.property', ['property' => $property?->tenantAssignmentLabel() ?? __('admin.tenants.empty.unassigned')]),
            __('admin.tenants.wizard.review.move_in_date', ['date' => (string) ($get('move_in_date') ?: '—')]),
            __('admin.tenants.wizard.review.portal_invitation', ['status' => $invitation]),
            __('admin.tenants.wizard.review.billing_readiness', ['status' => (string) $readiness->status->getLabel()]),
        ];

        $items = collect($rows)
            ->map(fn (string $row): string => '<li>'.e($row).'</li>')
            ->implode('');

        return new HtmlString('<ul class="list-disc space-y-2 pl-5 text-sm text-slate-700">'.$items.'</ul>');
    }

    private static function billingReadinessResult(Get $get): TenantBillingReadinessResult
    {
        $property = self::findProperty($get('property_id'), $get('organization_id'));
        $organizationId = self::resolveOrganizationId(null, $get('organization_id'));

        if ($organizationId === null) {
            return new TenantBillingReadinessResult(
                status: BillingReadinessStatus::NOT_CONFIGURED,
                blockingErrors: [__('admin.tenants.billing_readiness.errors.no_organization')],
                nextSteps: ['select_organization'],
                checks: [],
            );
        }

        $organization = Organization::query()
            ->select(['id'])
            ->find($organizationId);

        if (! $organization instanceof Organization) {
            return new TenantBillingReadinessResult(
                status: BillingReadinessStatus::NOT_CONFIGURED,
                blockingErrors: [__('admin.tenants.billing_readiness.errors.no_organization')],
                nextSteps: ['select_organization'],
                checks: [],
            );
        }

        return app(CheckTenantBillingReadiness::class)->forProspectiveAssignment(
            organization: $organization,
            property: $property,
            tenantHasPortalAccess: (bool) $get('create_portal_access') && (bool) $get('send_invitation_now'),
        );
    }
}
