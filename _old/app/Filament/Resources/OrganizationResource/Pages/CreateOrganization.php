<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Actions\CreateOrganizationAction;
use App\Enums\SubscriptionPlanType;
use App\Filament\Resources\OrganizationResource;
use App\Models\User;
use App\Support\Organizations\OrganizationSubscriptionTerm;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'New Organization';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->model(OrganizationResource::getModel())
            ->statePath('data')
            ->operation('create')
            ->schema([
                Forms\Components\Hidden::make('existing_owner_user_id')
                    ->dehydrated(false),

                Forms\Components\Section::make('Organization Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Organization Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                                if (($get('slug') ?? '') !== Str::slug((string) $old)) {
                                    return;
                                }

                                $set('slug', Str::slug((string) $state));
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'organizations', column: 'slug')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state)))
                            ->helperText('The slug is used in web addresses and cannot be changed after the organization is created.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Owner Account')
                    ->schema([
                        Forms\Components\TextInput::make('owner_email')
                            ->label('Owner Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $existingOwnerId = User::query()
                                    ->select(['id'])
                                    ->where('email', trim((string) $state))
                                    ->value('id');

                                $set('existing_owner_user_id', $existingOwnerId);
                            })
                            ->helperText(function (Get $get): string {
                                if (filled($get('existing_owner_user_id'))) {
                                    return 'This email already belongs to an existing user. That user will be assigned as the owner of the new organization.';
                                }

                                return 'The system will send an invitation to this email address.';
                            }),
                    ]),

                Forms\Components\Section::make('Initial Subscription')
                    ->schema([
                        Forms\Components\Select::make('plan')
                            ->label('Plan')
                            ->required()
                            ->default(SubscriptionPlanType::BASIC->value)
                            ->options([
                                SubscriptionPlanType::BASIC->value => 'Basic',
                                SubscriptionPlanType::PROFESSIONAL->value => 'Professional',
                                SubscriptionPlanType::ENTERPRISE->value => 'Enterprise',
                            ])
                            ->live(),

                        Forms\Components\ToggleButtons::make('subscription_duration_months')
                            ->label('Duration')
                            ->required()
                            ->default(1)
                            ->options(OrganizationSubscriptionTerm::options())
                            ->grouped()
                            ->inline()
                            ->live(),

                        Forms\Components\Placeholder::make('subscription_expiry_preview')
                            ->label('')
                            ->columnSpanFull()
                            ->content(fn (Get $get): string => OrganizationSubscriptionTerm::previewText(
                                $get('subscription_duration_months'),
                            )),
                    ])
                    ->columns(2),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(CreateOrganizationAction::class)->handle(
            organizationName: (string) $data['name'],
            slug: (string) $data['slug'],
            ownerEmail: trim((string) $data['owner_email']),
            plan: SubscriptionPlanType::from((string) $data['plan']),
            durationInMonths: (int) $data['subscription_duration_months'],
            actor: $actor,
        );
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Save Organization');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
