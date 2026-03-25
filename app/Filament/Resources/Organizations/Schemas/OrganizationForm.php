<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.organizations.form.sections.details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('superadmin.organizations.form.fields.organization_name'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.organizations.form.sections.owner'))
                    ->schema([
                        TextInput::make('owner_email')
                            ->label(__('superadmin.organizations.form.fields.owner_email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->helperText(fn (Get $get, string $operation): ?string => self::ownerEmailHelperText(
                                email: $get('owner_email'),
                                operation: $operation,
                            )),
                    ]),
                Section::make(__('superadmin.organizations.form.sections.subscription'))
                    ->schema([
                        Select::make('plan')
                            ->label(__('superadmin.organizations.form.fields.plan'))
                            ->options(SubscriptionPlan::options())
                            ->default(SubscriptionPlan::BASIC->value)
                            ->required()
                            ->live(),
                        ToggleButtons::make('duration')
                            ->label(__('superadmin.organizations.form.fields.duration'))
                            ->options(SubscriptionDuration::options())
                            ->default(SubscriptionDuration::MONTHLY->value)
                            ->required()
                            ->grouped()
                            ->inline()
                            ->live()
                            ->visibleOn('create'),
                        DatePicker::make('expires_at')
                            ->label(__('superadmin.organizations.form.fields.expiry_date'))
                            ->required()
                            ->live()
                            ->visibleOn('edit'),
                        Text::make(fn (Get $get): string => self::subscriptionExpiryPreview($get('duration')))
                            ->size('sm')
                            ->color('gray')
                            ->columnSpanFull()
                            ->visibleOn('create'),
                        Text::make(fn (Get $get): string => self::planLimitsNote($get('plan')))
                            ->size('sm')
                            ->color('gray')
                            ->columnSpanFull()
                            ->visibleOn('edit'),
                    ])
                    ->columns(2),
            ]);
    }

    private static function ownerEmailHelperText(?string $email, string $operation): ?string
    {
        if ($operation !== 'create') {
            return null;
        }

        if (blank($email)) {
            return __('superadmin.organizations.form.helper.owner_invitation');
        }

        $owner = User::query()
            ->select(['id', 'organization_id', 'email'])
            ->where('email', $email)
            ->first();

        if ($owner !== null && $owner->organization_id === null) {
            return __('superadmin.organizations.form.helper.owner_existing');
        }

        return __('superadmin.organizations.form.helper.owner_invitation');
    }

    private static function subscriptionExpiryPreview(?string $duration): string
    {
        $selectedDuration = filled($duration)
            ? SubscriptionDuration::from($duration)
            : SubscriptionDuration::MONTHLY;

        return __('superadmin.organizations.form.preview.subscription_expires', [
            'date' => CarbonImmutable::now()
                ->startOfDay()
                ->addMonths($selectedDuration->months())
                ->locale(app()->getLocale())
                ->isoFormat('ll'),
        ]);
    }

    private static function planLimitsNote(?string $plan): string
    {
        if (blank($plan)) {
            return '';
        }

        $selectedPlan = SubscriptionPlan::from($plan);
        $limits = $selectedPlan->limits();

        return __('superadmin.organizations.form.preview.plan_limits', [
            'plan' => $selectedPlan->label(),
            'properties' => $limits['properties'],
            'tenants' => $limits['tenants'],
        ]);
    }
}
