<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserKycProfiles\Schemas;

use App\Filament\Support\Kyc\KycAttachmentRegistry;
use App\Models\Attachment;
use App\Models\UserKycProfile;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserKycProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.user_kyc_profiles.sections.summary'))
                    ->schema([
                        TextEntry::make('user.name')->label(__('superadmin.user_kyc_profiles.fields.user')),
                        TextEntry::make('organization.name')->label(__('superadmin.user_kyc_profiles.fields.organization'))->default('—'),
                        TextEntry::make('verification_status')->label(__('superadmin.user_kyc_profiles.fields.verification_status'))->badge(),
                        TextEntry::make('reviewedBy.name')->label(__('superadmin.user_kyc_profiles.fields.reviewed_by'))->default('—'),
                        TextEntry::make('submitted_at')->label(__('superadmin.user_kyc_profiles.fields.submitted_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('reviewed_at')->label(__('superadmin.user_kyc_profiles.fields.reviewed_at'))->dateTime()->placeholder('—'),
                    ])
                    ->columns(3),
                Section::make(__('superadmin.user_kyc_profiles.sections.identity_verification'))
                    ->schema([
                        TextEntry::make('full_legal_name')->label(__('superadmin.user_kyc_profiles.fields.full_legal_name')),
                        TextEntry::make('birth_date')->label(__('superadmin.user_kyc_profiles.fields.birth_date'))->date()->placeholder('—'),
                        TextEntry::make('nationality')->label(__('superadmin.user_kyc_profiles.fields.nationality'))->default('—'),
                        TextEntry::make('gender')->label(__('superadmin.user_kyc_profiles.fields.gender'))->default('—'),
                        TextEntry::make('marital_status')->label(__('superadmin.user_kyc_profiles.fields.marital_status'))->default('—'),
                        TextEntry::make('tax_id_number')->label(__('superadmin.user_kyc_profiles.fields.tax_id_number'))->default('—'),
                        TextEntry::make('social_security_number')->label(__('superadmin.user_kyc_profiles.fields.social_security_number'))->default('—'),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.documents'))
                    ->schema([
                        ImageEntry::make('document_profile_photo')
                            ->label(__('superadmin.user_kyc_profiles.fields.profile_photo'))
                            ->state(fn (UserKycProfile $record): ?string => self::attachmentFor($record, 'profile_photo')?->path)
                            ->disk(fn (UserKycProfile $record): string => self::attachmentFor($record, 'profile_photo')?->disk ?? 'local')
                            ->visibility('private')
                            ->imageSize(160)
                            ->circular(),
                        ...self::documentEntries('passport_scan'),
                        ...self::documentEntries('national_id_front'),
                        ...self::documentEntries('national_id_back'),
                        ...self::documentEntries('drivers_license'),
                        ...self::documentEntries('employment_verification_letter'),
                        ...self::documentEntries('direct_debit_mandate'),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.emergency_contacts'))
                    ->schema([
                        TextEntry::make('secondary_contact_name')->label(__('superadmin.user_kyc_profiles.fields.secondary_contact_name'))->default('—'),
                        TextEntry::make('secondary_contact_relationship')->label(__('superadmin.user_kyc_profiles.fields.secondary_contact_relationship'))->default('—'),
                        TextEntry::make('secondary_contact_phone')->label(__('superadmin.user_kyc_profiles.fields.secondary_contact_phone'))->default('—'),
                        TextEntry::make('secondary_contact_email')->label(__('superadmin.user_kyc_profiles.fields.secondary_contact_email'))->default('—'),
                        TextEntry::make('tertiary_contact_name')->label(__('superadmin.user_kyc_profiles.fields.tertiary_contact_name'))->default('—'),
                        TextEntry::make('tertiary_contact_relationship')->label(__('superadmin.user_kyc_profiles.fields.tertiary_contact_relationship'))->default('—'),
                        TextEntry::make('tertiary_contact_phone')->label(__('superadmin.user_kyc_profiles.fields.tertiary_contact_phone'))->default('—'),
                        TextEntry::make('tertiary_contact_email')->label(__('superadmin.user_kyc_profiles.fields.tertiary_contact_email'))->default('—'),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.professional_information'))
                    ->schema([
                        TextEntry::make('employer_name')->label(__('superadmin.user_kyc_profiles.fields.employer_name'))->default('—'),
                        TextEntry::make('employment_position')->label(__('superadmin.user_kyc_profiles.fields.employment_position'))->default('—'),
                        TextEntry::make('employment_contract_type')->label(__('superadmin.user_kyc_profiles.fields.employment_contract_type'))->default('—'),
                        TextEntry::make('monthly_income_range')->label(__('superadmin.user_kyc_profiles.fields.monthly_income_range'))->default('—'),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.banking_details'))
                    ->schema([
                        TextEntry::make('iban')->label(__('superadmin.user_kyc_profiles.fields.iban'))->default('—'),
                        TextEntry::make('swift_bic')->label(__('superadmin.user_kyc_profiles.fields.swift_bic'))->default('—'),
                        TextEntry::make('bank_name')->label(__('superadmin.user_kyc_profiles.fields.bank_name'))->default('—'),
                        TextEntry::make('bank_account_holder_name')->label(__('superadmin.user_kyc_profiles.fields.bank_account_holder_name'))->default('—'),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.risk_review'))
                    ->schema([
                        TextEntry::make('facial_recognition_consent')->label(__('superadmin.user_kyc_profiles.fields.facial_recognition_consent'))->badge(),
                        TextEntry::make('blacklist_status')->label(__('superadmin.user_kyc_profiles.fields.blacklist_status'))->badge(),
                        TextEntry::make('payment_history_score')->label(__('superadmin.user_kyc_profiles.fields.payment_history_score'))->default('—'),
                        TextEntry::make('external_credit_bureau_reference')->label(__('superadmin.user_kyc_profiles.fields.external_credit_bureau_reference'))->default('—'),
                        TextEntry::make('internal_credit_score')->label(__('superadmin.user_kyc_profiles.fields.internal_credit_score'))->default('—'),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.user_kyc_profiles.sections.review_notes'))
                    ->schema([
                        TextEntry::make('rejection_reason')->label(__('superadmin.user_kyc_profiles.fields.rejection_reason'))->default('—')->columnSpanFull(),
                    ]),
            ]);
    }

    private static function documentEntries(string $field): array
    {
        $documentType = KycAttachmentRegistry::documentTypeForField($field);
        $label = KycAttachmentRegistry::labelForField($field);

        return [
            TextEntry::make('document_'.$documentType)
                ->label($label)
                ->icon(fn (UserKycProfile $record) => KycAttachmentRegistry::iconForExtension(KycAttachmentRegistry::extensionForAttachment(self::attachmentFor($record, $documentType))))
                ->state(fn (UserKycProfile $record): string => self::attachmentFor($record, $documentType)?->original_filename ?? '—')
                ->url(fn (UserKycProfile $record): ?string => ($attachment = self::attachmentFor($record, $documentType)) !== null
                    ? route('kyc.attachments.show', ['attachment' => $attachment])
                    : null)
                ->openUrlInNewTab(),
            TextEntry::make('document_'.$documentType.'_meta')
                ->label($label.' '.__('superadmin.user_kyc_profiles.fields.document_type_suffix'))
                ->state(fn (UserKycProfile $record): string => strtoupper(KycAttachmentRegistry::extensionForAttachment(self::attachmentFor($record, $documentType)) ?? '—')),
        ];
    }

    private static function attachmentFor(UserKycProfile $record, string $documentType): ?Attachment
    {
        return $record->attachments->firstWhere('document_type', $documentType);
    }
}
