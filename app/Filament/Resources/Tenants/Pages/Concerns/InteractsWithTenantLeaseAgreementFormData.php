<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Pages\Concerns;

use App\Filament\Actions\Admin\Tenants\SyncTenantLeaseAgreementAction;
use App\Filament\Support\Tenants\TenantLeaseAgreement;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

trait InteractsWithTenantLeaseAgreementFormData
{
    /**
     * @var array<string, mixed>
     */
    protected array $leaseAgreementFormData = [];

    /**
     * @return array<string, mixed>
     */
    protected function leaseAgreementFormDataForRecord(User $record): array
    {
        $attachment = $record->leaseAgreement;

        $data = [
            TenantLeaseAgreement::FIELD => $attachment?->path,
        ];

        if ($attachment !== null) {
            Arr::set($data, TenantLeaseAgreement::fileNamesStatePath(), $attachment->original_filename);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function extractLeaseAgreementFormData(array $data): array
    {
        $attachmentData = [
            TenantLeaseAgreement::FILE_NAMES_FIELD => $data[TenantLeaseAgreement::FILE_NAMES_FIELD] ?? [],
            TenantLeaseAgreement::FIELD => $data[TenantLeaseAgreement::FIELD] ?? null,
        ];

        unset($data[TenantLeaseAgreement::FILE_NAMES_FIELD], $data[TenantLeaseAgreement::FIELD]);

        return [$data, $attachmentData];
    }

    protected function syncTenantLeaseAgreement(User $record): void
    {
        app(SyncTenantLeaseAgreementAction::class)->handle($record, $this->authenticatedUser(), $this->leaseAgreementFormData);
    }

    private function authenticatedUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
