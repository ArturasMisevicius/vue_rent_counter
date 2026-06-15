<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantKycProfiles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantKycProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tenant_kyc.sections.summary'))
                    ->schema([
                        TextEntry::make('tenant.name')->label(__('admin.tenant_kyc.fields.tenant')),
                        TextEntry::make('organization.name')->label(__('admin.tenant_kyc.fields.organization'))->default('—'),
                        TextEntry::make('status')->label(__('admin.tenant_kyc.fields.status'))->badge(),
                        TextEntry::make('documents_count')->label(__('admin.tenant_kyc.fields.documents')),
                        TextEntry::make('submitted_at')->label(__('admin.tenant_kyc.fields.submitted_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('reviewedBy.name')->label(__('admin.tenant_kyc.fields.reviewed_by'))->default('—'),
                        TextEntry::make('reviewed_at')->label(__('admin.tenant_kyc.fields.reviewed_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('approved_at')->label(__('admin.tenant_kyc.fields.approved_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('rejected_at')->label(__('admin.tenant_kyc.fields.rejected_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('expires_at')->label(__('admin.tenant_kyc.fields.expires_at'))->date()->placeholder('—'),
                    ])
                    ->columns(3),
                Section::make(__('admin.tenant_kyc.sections.review'))
                    ->schema([
                        TextEntry::make('rejection_reason')
                            ->label(__('admin.tenant_kyc.fields.rejection_reason'))
                            ->default('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
