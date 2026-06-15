<?php

declare(strict_types=1);

namespace App\Filament\Support\Tenant\Portal;

use App\Enums\TenantKycDocumentStatus;
use App\Enums\TenantKycDocumentType;
use App\Enums\TenantKycProfileStatus;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\TenantKyc\TenantKycSettings;
use App\Models\TenantKycDocument;
use App\Models\TenantKycProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class TenantKycPresenter
{
    public function __construct(
        private readonly TenantKycSettings $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(User $tenant): array
    {
        $profile = TenantKycProfile::query()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'status',
                'submitted_at',
                'approved_at',
                'rejected_at',
                'rejection_reason',
                'expires_at',
            ])
            ->forOrganization((int) $tenant->organization_id)
            ->forTenant((int) $tenant->id)
            ->with([
                'documents' => fn ($query) => $query
                    ->activeForChecklist()
                    ->withReviewRelations()
                    ->latestActivityFirst(),
            ])
            ->first();

        $requiredTypes = $this->settings->requiredDocumentTypes((int) $tenant->organization_id);
        $documents = $profile?->documents ?? collect();

        return [
            'profile_id' => $profile?->id,
            'status' => $profile?->status ?? TenantKycProfileStatus::NOT_STARTED,
            'status_label' => ($profile?->status ?? TenantKycProfileStatus::NOT_STARTED)->label(),
            'rejection_reason' => $profile?->rejection_reason,
            'expires_at' => $this->formatDate($profile?->expires_at),
            'is_required' => $requiredTypes !== [],
            'required_count' => count($requiredTypes),
            'checklist' => $this->checklist($requiredTypes, $documents),
            'documents' => $this->documents($documents),
            'document_type_options' => $this->documentTypeOptions($requiredTypes),
        ];
    }

    /**
     * @param  list<TenantKycDocumentType>  $requiredTypes
     * @param  Collection<int, TenantKycDocument>  $documents
     * @return list<array<string, mixed>>
     */
    private function checklist(array $requiredTypes, Collection $documents): array
    {
        return collect($requiredTypes)
            ->map(function (TenantKycDocumentType $type) use ($documents): array {
                $document = $documents->first(fn (TenantKycDocument $candidate): bool => $candidate->document_type === $type);

                return [
                    'type' => $type->value,
                    'type_label' => $type->label(),
                    'document_id' => $document?->id,
                    'status' => $document?->status ?? TenantKycDocumentStatus::DRAFT,
                    'status_label' => ($document?->status ?? TenantKycDocumentStatus::DRAFT)->label(),
                    'rejection_reason' => $document?->rejection_reason,
                    'expires_at' => $this->formatDate($document?->expires_at),
                    'is_expired' => $document?->isExpired() ?? false,
                    'download_url' => $document !== null ? route('tenant.kyc-documents.download', $document) : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TenantKycDocument>  $documents
     * @return list<array<string, mixed>>
     */
    private function documents(Collection $documents): array
    {
        return $documents
            ->map(fn (TenantKycDocument $document): array => [
                'id' => $document->id,
                'type_label' => $document->document_type?->label() ?? __('tenant.pages.verification.document'),
                'status' => $document->status,
                'status_label' => $document->status?->label() ?? __('dashboard.not_available'),
                'file_name' => $document->fileDocument?->original_filename ?? __('dashboard.not_available'),
                'file_size' => $document->fileDocument !== null ? Number::fileSize((int) $document->fileDocument->size) : null,
                'expires_at' => $this->formatDate($document->expires_at),
                'is_expired' => $document->isExpired(),
                'rejection_reason' => $document->rejection_reason,
                'download_url' => route('tenant.kyc-documents.download', $document),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<TenantKycDocumentType>  $requiredTypes
     * @return array<string, string>
     */
    private function documentTypeOptions(array $requiredTypes): array
    {
        $types = $requiredTypes === [] ? TenantKycDocumentType::cases() : $requiredTypes;

        return collect($types)
            ->mapWithKeys(fn (TenantKycDocumentType $type): array => [$type->value => $type->label()])
            ->all();
    }

    private function formatDate(mixed $date): ?string
    {
        return $date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat());
    }
}
