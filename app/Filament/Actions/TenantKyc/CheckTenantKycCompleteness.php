<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Enums\TenantKycDocumentStatus;
use App\Enums\TenantKycDocumentType;
use App\Enums\TenantKycProfileStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\TenantKyc\TenantKycSettings;
use App\Models\TenantKycDocument;
use App\Models\TenantKycProfile;
use App\Models\User;
use App\Notifications\TenantKyc\TenantKycApprovedNotification;
use App\Notifications\TenantKyc\TenantKycRejectedNotification;
use Illuminate\Database\Eloquent\Collection;

class CheckTenantKycCompleteness
{
    public function __construct(
        private readonly TenantKycSettings $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantKycProfile $profile, ?User $actor = null): TenantKycProfile
    {
        $profile = $profile->fresh(['tenant']);
        $before = $profile->getOriginal();
        $requiredTypes = $this->settings->requiredDocumentTypes((int) $profile->organization_id);

        if ($requiredTypes === []) {
            $this->applyStatus($profile, TenantKycProfileStatus::DISABLED, actor: $actor, before: $before);

            return $profile->refresh();
        }

        /** @var Collection<int, TenantKycDocument> $documents */
        $documents = $profile->documents()
            ->activeForChecklist()
            ->latestActivityFirst()
            ->get();

        if ($documents->isEmpty()) {
            $this->applyStatus($profile, TenantKycProfileStatus::NOT_STARTED, actor: $actor, before: $before);

            return $profile->refresh();
        }

        $requiredDocuments = collect($requiredTypes)
            ->mapWithKeys(fn (TenantKycDocumentType $type): array => [
                $type->value => $documents->first(fn (TenantKycDocument $document): bool => $document->document_type === $type),
            ]);

        if ($requiredDocuments->contains(null)) {
            $this->applyStatus($profile, TenantKycProfileStatus::INCOMPLETE, actor: $actor, before: $before);

            return $profile->refresh();
        }

        $expired = $requiredDocuments->first(fn (?TenantKycDocument $document): bool => $document?->isExpired() ?? false);

        if ($expired instanceof TenantKycDocument) {
            $this->applyStatus($profile, TenantKycProfileStatus::EXPIRED, actor: $actor, before: $before);

            return $profile->refresh();
        }

        $rejected = $requiredDocuments->first(
            fn (?TenantKycDocument $document): bool => $document?->status === TenantKycDocumentStatus::REJECTED,
        );

        if ($rejected instanceof TenantKycDocument) {
            $this->applyStatus(
                $profile,
                TenantKycProfileStatus::REJECTED,
                $rejected->rejection_reason,
                $actor,
                $before,
            );

            return $profile->refresh();
        }

        $allApproved = $requiredDocuments->every(
            fn (?TenantKycDocument $document): bool => $document?->status === TenantKycDocumentStatus::APPROVED,
        );

        if (! $allApproved) {
            $this->applyStatus($profile, TenantKycProfileStatus::PENDING_REVIEW, actor: $actor, before: $before);

            return $profile->refresh();
        }

        $expiresAt = $requiredDocuments
            ->filter()
            ->pluck('expires_at')
            ->filter()
            ->sort()
            ->first();

        $this->applyStatus(
            $profile,
            TenantKycProfileStatus::VERIFIED,
            actor: $actor,
            before: $before,
            expiresAt: $expiresAt,
        );

        return $profile->refresh();
    }

    private function applyStatus(
        TenantKycProfile $profile,
        TenantKycProfileStatus $status,
        ?string $rejectionReason = null,
        ?User $actor = null,
        ?array $before = null,
        mixed $expiresAt = null,
    ): void {
        $previousStatus = $profile->status;

        $profile->forceFill([
            'status' => $status,
            'submitted_at' => in_array($status, [
                TenantKycProfileStatus::INCOMPLETE,
                TenantKycProfileStatus::PENDING_REVIEW,
                TenantKycProfileStatus::VERIFIED,
                TenantKycProfileStatus::REJECTED,
                TenantKycProfileStatus::EXPIRED,
            ], true) ? ($profile->submitted_at ?? now()) : $profile->submitted_at,
            'approved_at' => $status === TenantKycProfileStatus::VERIFIED ? ($profile->approved_at ?? now()) : null,
            'rejected_at' => $status === TenantKycProfileStatus::REJECTED ? ($profile->rejected_at ?? now()) : null,
            'rejection_reason' => $status === TenantKycProfileStatus::REJECTED ? $rejectionReason : null,
            'expires_at' => $status === TenantKycProfileStatus::VERIFIED ? $expiresAt : null,
        ])->save();

        if ($previousStatus === $status && $profile->wasChanged() === false) {
            return;
        }

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $profile,
            [
                'before' => $before ?? [],
                'after' => $profile->getAttributes(),
                'context' => ['mutation' => 'tenant_kyc_profile.completeness_checked'],
            ],
            $actor?->id,
            'Tenant KYC completeness checked',
        );

        $fresh = $profile->fresh(['tenant']);

        if (! $fresh->tenant instanceof User || $previousStatus === $status) {
            return;
        }

        if ($status === TenantKycProfileStatus::VERIFIED) {
            $fresh->tenant->notify(new TenantKycApprovedNotification($fresh));
        }

        if ($status === TenantKycProfileStatus::REJECTED) {
            $fresh->tenant->notify(new TenantKycRejectedNotification($fresh));
        }
    }
}
