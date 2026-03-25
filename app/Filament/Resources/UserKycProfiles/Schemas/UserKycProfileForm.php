<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserKycProfiles\Schemas;

use App\Filament\Support\Kyc\KycAttachmentRegistry;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserKycProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.user_kyc_profiles.sections.identity_verification'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('superadmin.user_kyc_profiles.fields.organization'))
                            ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->visible(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                            ->default(fn (): ?int => self::currentUser()?->organization_id)
                            ->live(),
                        Select::make('user_id')
                            ->label(__('superadmin.user_kyc_profiles.fields.user'))
                            ->options(fn (Get $get): array => User::query()
                                ->when(
                                    filled($get('organization_id')),
                                    fn ($query) => $query->where('organization_id', $get('organization_id')),
                                    fn ($query) => self::currentUser()?->isSuperadmin()
                                        ? $query
                                        : $query->where('organization_id', self::currentUser()?->organization_id),
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('full_legal_name')->label(__('superadmin.user_kyc_profiles.fields.full_legal_name'))->required()->maxLength(255),
                        DatePicker::make('birth_date')->label(__('superadmin.user_kyc_profiles.fields.birth_date')),
                        TextInput::make('nationality')->label(__('superadmin.user_kyc_profiles.fields.nationality'))->maxLength(255),
                        TextInput::make('gender')->label(__('superadmin.user_kyc_profiles.fields.gender'))->maxLength(255),
                        TextInput::make('marital_status')->label(__('superadmin.user_kyc_profiles.fields.marital_status'))->maxLength(255),
                        TextInput::make('tax_id_number')->label(__('superadmin.user_kyc_profiles.fields.tax_id_number'))->maxLength(255),
                        TextInput::make('social_security_number')->label(__('superadmin.user_kyc_profiles.fields.social_security_number'))->maxLength(255),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.emergency_contacts'))
                    ->schema([
                        TextInput::make('secondary_contact_name')->label(__('superadmin.user_kyc_profiles.fields.secondary_contact_name'))->maxLength(255),
                        TextInput::make('secondary_contact_relationship')->label(__('superadmin.user_kyc_profiles.fields.secondary_contact_relationship'))->maxLength(255),
                        TextInput::make('secondary_contact_phone')->label(__('superadmin.user_kyc_profiles.fields.secondary_contact_phone'))->maxLength(255),
                        TextInput::make('secondary_contact_email')->label(__('superadmin.user_kyc_profiles.fields.secondary_contact_email'))->email()->maxLength(255),
                        TextInput::make('tertiary_contact_name')->label(__('superadmin.user_kyc_profiles.fields.tertiary_contact_name'))->maxLength(255),
                        TextInput::make('tertiary_contact_relationship')->label(__('superadmin.user_kyc_profiles.fields.tertiary_contact_relationship'))->maxLength(255),
                        TextInput::make('tertiary_contact_phone')->label(__('superadmin.user_kyc_profiles.fields.tertiary_contact_phone'))->maxLength(255),
                        TextInput::make('tertiary_contact_email')->label(__('superadmin.user_kyc_profiles.fields.tertiary_contact_email'))->email()->maxLength(255),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.professional_information'))
                    ->schema([
                        TextInput::make('employer_name')->label(__('superadmin.user_kyc_profiles.fields.employer_name'))->maxLength(255),
                        TextInput::make('employment_position')->label(__('superadmin.user_kyc_profiles.fields.employment_position'))->maxLength(255),
                        TextInput::make('employment_contract_type')->label(__('superadmin.user_kyc_profiles.fields.employment_contract_type'))->maxLength(255),
                        TextInput::make('monthly_income_range')->label(__('superadmin.user_kyc_profiles.fields.monthly_income_range'))->maxLength(255),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.banking_details'))
                    ->schema([
                        TextInput::make('iban')->label(__('superadmin.user_kyc_profiles.fields.iban'))->maxLength(255),
                        TextInput::make('swift_bic')->label(__('superadmin.user_kyc_profiles.fields.swift_bic'))->maxLength(255),
                        TextInput::make('bank_name')->label(__('superadmin.user_kyc_profiles.fields.bank_name'))->maxLength(255),
                        TextInput::make('bank_account_holder_name')->label(__('superadmin.user_kyc_profiles.fields.bank_account_holder_name'))->maxLength(255),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.review'))
                    ->schema([
                        Toggle::make('facial_recognition_consent')->label(__('superadmin.user_kyc_profiles.fields.facial_recognition_consent')),
                        Toggle::make('blacklist_status')->label(__('superadmin.user_kyc_profiles.fields.blacklist_status')),
                        TextInput::make('payment_history_score')->label(__('superadmin.user_kyc_profiles.fields.payment_history_score'))->numeric()->minValue(0)->maxValue(100),
                        TextInput::make('external_credit_bureau_reference')->label(__('superadmin.user_kyc_profiles.fields.external_credit_bureau_reference'))->maxLength(255),
                        TextInput::make('internal_credit_score')->label(__('superadmin.user_kyc_profiles.fields.internal_credit_score'))->numeric()->minValue(0)->maxValue(1000),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.attachments'))
                    ->schema(self::attachmentFields())
                    ->columns(2),
            ]);
    }

    private static function attachmentFields(): array
    {
        return array_map(function (string $field): FileUpload {
            if (KycAttachmentRegistry::isPhotoField($field)) {
                return FileUpload::make($field)
                    ->label(KycAttachmentRegistry::labelForField($field))
                    ->disk('local')
                    ->directory('kyc')
                    ->visibility('private')
                    ->avatar()
                    ->imageEditor()
                    ->imagePreviewHeight('250')
                    ->openable()
                    ->downloadable()
                    ->storeFileNamesIn(KycAttachmentRegistry::fileNamesStatePath($field));
            }

            return FileUpload::make($field)
                ->label(KycAttachmentRegistry::labelForField($field))
                ->disk('local')
                ->directory('kyc')
                ->visibility('private')
                ->acceptedFileTypes(KycAttachmentRegistry::acceptedFileTypes($field))
                ->openable()
                ->downloadable()
                ->storeFileNamesIn(KycAttachmentRegistry::fileNamesStatePath($field));
        }, KycAttachmentRegistry::fieldNames());
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
