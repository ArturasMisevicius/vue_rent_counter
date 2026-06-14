<?php

declare(strict_types=1);

namespace App\Filament\Support\Tenant\Portal;

use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\TenantDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;

class TenantDocumentPresenter
{
    /**
     * @param  Collection<int, TenantDocument>  $documents
     * @return list<array<string, mixed>>
     */
    public function presentMany(Collection $documents): array
    {
        return $documents
            ->map(fn (TenantDocument $document): array => $this->present($document))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function present(TenantDocument $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description_for_tenant,
            'document_type' => $document->document_type?->value,
            'document_type_label' => $document->document_type?->label() ?? __('dashboard.not_available'),
            'status' => $document->status,
            'status_label' => $document->status?->label() ?? __('dashboard.not_available'),
            'property' => $document->property?->tenantAssignmentLabel() ?? __('tenant.pages.documents.all_properties'),
            'file_name' => $document->original_filename,
            'file_size' => Number::fileSize((int) $document->size),
            'uploaded_at' => $document->created_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? __('dashboard.not_available'),
            'expires_at' => $document->expires_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
            'is_expired' => $document->isExpired(),
            'is_rejected_kyc' => $document->isKycDocument() && $document->status === TenantDocumentStatus::REJECTED,
            'rejection_reason' => $document->isKycDocument() ? $document->rejection_reason : null,
            'download_url' => route('tenant.documents.download', $document),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, array{label: string, count: int, icon: string}>
     */
    public function filters(array $counts): array
    {
        $filters = [
            'all' => [
                'label' => __('tenant.pages.documents.filters.all'),
                'count' => $counts['all'] ?? 0,
                'icon' => 'heroicon-m-rectangle-stack',
            ],
        ];

        foreach (TenantDocumentType::cases() as $type) {
            $filters[$type->value] = [
                'label' => $type->label(),
                'count' => $counts[$type->value] ?? 0,
                'icon' => $this->iconFor($type),
            ];
        }

        return $filters;
    }

    private function iconFor(TenantDocumentType $type): string
    {
        return match ($type) {
            TenantDocumentType::INVOICE, TenantDocumentType::INVOICE_PDF, TenantDocumentType::PAYMENT_RECEIPT => 'heroicon-m-document-currency-euro',
            TenantDocumentType::RENTAL_CONTRACT, TenantDocumentType::CONTRACT_APPENDIX => 'heroicon-m-document-check',
            TenantDocumentType::KYC_IDENTITY, TenantDocumentType::KYC_ADDRESS => 'heroicon-m-identification',
            TenantDocumentType::METER_PHOTO => 'heroicon-m-camera',
            TenantDocumentType::PROPERTY_DOCUMENT => 'heroicon-m-home-modern',
            default => 'heroicon-m-document-text',
        };
    }
}
