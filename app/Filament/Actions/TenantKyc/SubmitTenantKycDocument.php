<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Enums\TenantKycDocumentStatus;
use App\Enums\TenantKycDocumentType;
use App\Enums\TenantKycProfileStatus;
use App\Filament\Actions\Admin\TenantDocuments\UploadTenantDocument;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\TenantDocuments\TenantDocumentFile;
use App\Filament\Support\TenantKyc\TenantKycSettings;
use App\Http\Requests\TenantKyc\SubmitTenantKycDocumentRequest;
use App\Models\Organization;
use App\Models\TenantKycDocument;
use App\Models\TenantKycProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmitTenantKycDocument
{
    public function __construct(
        private readonly UploadTenantDocument $uploadTenantDocument,
        private readonly CheckTenantKycCompleteness $checkTenantKycCompleteness,
        private readonly TenantKycSettings $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|string|UploadedFile  $fileState
     */
    public function handle(User $actor, array $data, array|string|UploadedFile $fileState): TenantKycDocument
    {
        Gate::forUser($actor)->authorize('create', TenantKycDocument::class);

        $data = $this->normalizeActorScope($actor, $data);
        $organizationId = (int) $data['organization_id'];

        $this->validateUpload($fileState);

        if (isset($data['document_number']) && ! isset($data['document_number_encrypted'])) {
            $data['document_number_encrypted'] = $data['document_number'];
        }

        $validated = (new SubmitTenantKycDocumentRequest)
            ->forOrganization($organizationId)
            ->validatePayload($data, $actor);

        if ($this->settings->requiresExpiryDate($organizationId) && blank($validated['expires_at'] ?? null)) {
            throw ValidationException::withMessages([
                'expires_at' => __('validation.required', [
                    'attribute' => __('requests.attributes.expires_at'),
                ]),
            ]);
        }

        $documentType = TenantKycDocumentType::from((string) $validated['document_type']);

        return DB::transaction(function () use ($actor, $documentType, $fileState, $organizationId, $validated): TenantKycDocument {
            $profile = TenantKycProfile::query()->firstOrCreate([
                'organization_id' => $organizationId,
                'tenant_id' => (int) $validated['tenant_id'],
            ], [
                'status' => TenantKycProfileStatus::NOT_STARTED,
            ]);

            $fileDocument = $this->uploadTenantDocument->handle($actor, [
                'organization_id' => $organizationId,
                'tenant_id' => (int) $validated['tenant_id'],
                'document_type' => $documentType->tenantDocumentType(),
                'title' => $documentType->label(),
                'description_for_tenant' => __('tenant.pages.verification.file_description', [
                    'type' => $documentType->label(),
                ]),
                'internal_note' => $validated['internal_note'] ?? null,
                'status' => TenantDocumentStatus::PENDING_REVIEW,
                'tenant_visible' => true,
                'expires_at' => $validated['expires_at'] ?? null,
            ], $fileState);

            $kycDocument = TenantKycDocument::query()->create([
                ...Arr::only($validated, [
                    'organization_id',
                    'tenant_id',
                    'document_type',
                    'document_number_encrypted',
                    'issued_country',
                    'issued_at',
                    'expires_at',
                    'internal_note',
                ]),
                'kyc_profile_id' => $profile->id,
                'status' => TenantKycDocumentStatus::PENDING_REVIEW,
                'file_document_id' => $fileDocument->id,
                'submitted_by_user_id' => $actor->id,
                'submitted_at' => now(),
            ]);

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $kycDocument,
                [
                    'after' => Arr::except($kycDocument->getAttributes(), ['document_number_encrypted']),
                    'context' => ['mutation' => 'tenant_kyc_document.submitted'],
                ],
                $actor->id,
                'Tenant KYC document submitted',
            );

            $this->checkTenantKycCompleteness->handle($profile->refresh(), $actor);

            return $kycDocument->fresh(['profile', 'fileDocument']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeActorScope(User $actor, array $data): array
    {
        if ($actor->isTenant()) {
            if (isset($data['tenant_id']) && (int) $data['tenant_id'] !== (int) $actor->id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('tenant.pages.verification.validation.tenant_mismatch'),
                ]);
            }

            if (isset($data['organization_id']) && (int) $data['organization_id'] !== (int) $actor->organization_id) {
                throw ValidationException::withMessages([
                    'organization_id' => __('tenant.pages.verification.validation.organization_mismatch'),
                ]);
            }

            return [
                ...$data,
                'organization_id' => (int) $actor->organization_id,
                'tenant_id' => (int) $actor->id,
            ];
        }

        $organization = $actor->currentOrganization();
        $organizationId = $actor->isSuperadmin()
            ? ($data['organization_id'] ?? null)
            : $organization?->id;

        if ($organizationId === null) {
            throw ValidationException::withMessages([
                'organization_id' => __('validation.required', ['attribute' => 'organization']),
            ]);
        }

        if (! $actor->isSuperadmin() && isset($data['organization_id']) && (int) $data['organization_id'] !== (int) $organizationId) {
            throw ValidationException::withMessages([
                'organization_id' => __('tenant.pages.verification.validation.organization_mismatch'),
            ]);
        }

        if (! Organization::query()->whereKey((int) $organizationId)->exists()) {
            throw ValidationException::withMessages([
                'organization_id' => __('validation.exists', ['attribute' => 'organization']),
            ]);
        }

        return [
            ...$data,
            'organization_id' => (int) $organizationId,
        ];
    }

    private function validateUpload(array|string|UploadedFile $fileState): void
    {
        if (! $fileState instanceof UploadedFile) {
            return;
        }

        Validator::make([
            TenantDocumentFile::FIELD => $fileState,
        ], [
            TenantDocumentFile::FIELD => [
                'required',
                'file',
                'mimetypes:'.implode(',', TenantDocumentFile::acceptedFileTypes()),
                'max:'.TenantDocumentFile::MAX_SIZE_KB,
            ],
        ])->validate();
    }
}
