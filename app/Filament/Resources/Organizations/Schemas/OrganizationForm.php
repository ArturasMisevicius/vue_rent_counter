<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Organization Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation): void {
                                if ($operation !== 'create') {
                                    return;
                                }

                                if ($get('slug_locked')) {
                                    return;
                                }

                                $set('slug', Str::slug((string) $state));
                            }),
                        TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->alphaDash()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->helperText('The slug is used in web addresses and cannot be changed after the organization is created.')
                            ->visibleOn('create')
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation): void {
                                if ($operation !== 'create') {
                                    return;
                                }

                                $set('slug_locked', filled($state) && $state !== Str::slug((string) $get('name')));
                            }),
                        Hidden::make('slug_locked')
                            ->default(false)
                            ->dehydrated(false)
                            ->visibleOn('create'),
                    ])
                    ->columns(2),
                Section::make('Owner Account')
                    ->schema([
                        TextInput::make('owner_email')
                            ->label('Owner Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->helperText(fn (Get $get, string $operation): ?string => self::ownerEmailHelperText(
                                email: $get('owner_email'),
                                operation: $operation,
                            )),
                    ]),
                Section::make('Initial Subscription')
                    ->schema([
                        Select::make('plan')
                            ->label('Plan')
                            ->options(SubscriptionPlan::options())
                            ->default(SubscriptionPlan::BASIC->value)
                            ->required()
                            ->live(),
                        ToggleButtons::make('duration')
                            ->label('Duration')
                            ->options(SubscriptionDuration::options())
                            ->default(SubscriptionDuration::MONTHLY->value)
                            ->required()
                            ->grouped()
                            ->inline()
                            ->live()
                            ->visibleOn('create'),
                        DatePicker::make('expires_at')
                            ->label('Expiry Date')
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
            return 'The system will send an invitation to this email address.';
        }

        $owner = User::query()
            ->select(['id', 'organization_id', 'email'])
            ->where('email', $email)
            ->first();

        if ($owner !== null && $owner->organization_id === null) {
            return 'This email already belongs to an existing user. This user will be assigned as the organization owner.';
        }

        return 'The system will send an invitation to this email address.';
    }

    private static function subscriptionExpiryPreview(?string $duration): string
    {
        $selectedDuration = filled($duration)
            ? SubscriptionDuration::from($duration)
            : SubscriptionDuration::MONTHLY;

        return 'Subscription will expire on '.CarbonImmutable::now()
            ->startOfDay()
            ->addMonths($selectedDuration->months())
            ->format('F j, Y');
    }

    private static function planLimitsNote(?string $plan): string
    {
        if (blank($plan)) {
            return '';
        }

        $selectedPlan = SubscriptionPlan::from($plan);
        $limits = $selectedPlan->limits();

        return "Changing to {$selectedPlan->label()} will allow up to {$limits['properties']} properties and {$limits['tenants']} tenants.";
    }
}
